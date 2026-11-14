<?php

declare(strict_types=1);

namespace WV;

/**
 * Gegevenslaag. Elke rij heeft een versleutelde JSON-kolom "data"; het adres
 * van de rij (tabel:id) is de geauthenticeerde context van die versleuteling.
 * Zoeken gaat uitsluitend via HMAC-indexen (e-mail, tokens).
 */
final class Store
{
    public function __construct(public readonly Db $db, public readonly Crypto $crypto)
    {
    }

    // ---------------------------------------------------------------- helpers

    private function newId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function seal(string $table, string $id, array $data): string
    {
        return $this->crypto->sealJson($data, $table . ':' . $id);
    }

    private function unseal(string $table, array $row): array
    {
        return ['id' => $row['id']] + $this->crypto->openJson($row['data'], $table . ':' . $row['id']);
    }

    public function emailIndex(string $email): string
    {
        return $this->crypto->index('email', mb_strtolower(trim($email)));
    }

    public function tokenIndex(string $token): string
    {
        return $this->crypto->index('token', $token);
    }

    // ------------------------------------------------------------------ users

    public function userById(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }
        $row = $this->db->one('SELECT id, practice_id, data FROM users WHERE id = ?', [$id]);
        return $row ? $this->hydrateUser($row) : null;
    }

    private function hydrateUser(array $row): array
    {
        return $this->unseal('users', $row) + ['practice_id' => $row['practice_id'] ?? null];
    }

    public function isArts(?array $user): bool
    {
        return $user !== null && ($user['role'] ?? 'patient') === 'arts';
    }

    public function userByEmail(string $email): ?array
    {
        $row = $this->db->one('SELECT id, practice_id, data FROM users WHERE email_idx = ?', [$this->emailIndex($email)]);
        return $row ? $this->hydrateUser($row) : null;
    }

    /** @return array|null null als het e-mailadres al bestaat */
    public function createUser(string $name, string $email, string $passwordHash): ?array
    {
        $id = $this->newId();
        $data = [
            'name' => $name,
            'email' => mb_strtolower(trim($email)),
            'password' => $passwordHash,
            'created_at' => time(),
            'role' => 'patient',
            'recovery_codes' => [],
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => 0,
        ];
        try {
            $this->db->run(
                'INSERT INTO users (id, email_idx, data) VALUES (?, ?, ?)',
                [$id, $this->emailIndex($email), $this->seal('users', $id, $data)]
            );
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'UNIQUE')) {
                return null;
            }
            throw $e;
        }
        return ['id' => $id, 'practice_id' => null] + $data;
    }

    // ------------------------------------------------------------------ demo

    public function isDemo(?array $user): bool
    {
        return $user !== null && !empty($user['demo']);
    }

    /** Aantal demo-accounts dat nog niet is verlopen. */
    public function activeDemoCount(): int
    {
        return (int) $this->db->one('SELECT COUNT(*) AS n FROM users WHERE demo_until IS NOT NULL AND demo_until >= ?', [time()])['n'];
    }

    /**
     * Maakt een tijdelijke patiënt voor de demo: eigen account per bezoeker (dus niemand ziet gegevens van een ander),
     * zonder bruikbaar wachtwoord en zonder tweestapsverificatie, automatisch verwijderd na $ttlSeconds.
     */
    public function createDemoPatient(int $ttlSeconds): array
    {
        $id = $this->newId();
        $until = time() + $ttlSeconds;
        $data = [
            'name' => 'Demo-patiënt',
            'email' => 'demo-' . bin2hex(random_bytes(8)) . '@demo.invalid',
            'password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_ARGON2ID) ?: '',
            'created_at' => time(),
            'role' => 'patient',
            'demo' => true,
            'demo_until' => $until,
            'consent_at' => time(),
            'consent_version' => 'demo',
            'recovery_codes' => [],
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => 0,
        ];
        $this->db->run(
            'INSERT INTO users (id, email_idx, data, demo_until) VALUES (?, ?, ?, ?)',
            [$id, $this->emailIndex($data['email']), $this->seal('users', $id, $data), $until]
        );
        return ['id' => $id, 'practice_id' => null] + $data;
    }

    /** Verwijdert verlopen demo-accounts met alles wat eraan hangt. @return int aantal */
    public function purgeDemoUsers(): int
    {
        $n = 0;
        foreach ($this->db->all('SELECT id FROM users WHERE demo_until IS NOT NULL AND demo_until < ?', [time()]) as $row) {
            $this->deleteUser($row['id']);
            $n++;
        }
        return $n;
    }

    public function saveUser(array $user): void
    {
        $id = $user['id'];
        unset($user['id'], $user['practice_id']);
        $this->db->run('UPDATE users SET data = ? WHERE id = ?', [$this->seal('users', $id, $user), $id]);
    }

    // -------------------------------------------------------------- praktijken

    public function createPractice(string $name): array
    {
        $id = $this->newId();
        $data = ['name' => $name, 'created_at' => time()];
        $this->db->run('INSERT INTO practices (id, data) VALUES (?, ?)', [$id, $this->seal('practices', $id, $data)]);
        return ['id' => $id] + $data;
    }

    public function practiceById(?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }
        $row = $this->db->one('SELECT id, data FROM practices WHERE id = ?', [$id]);
        return $row ? $this->unseal('practices', $row) : null;
    }

    /** @return list<array> */
    public function practices(): array
    {
        return array_map(fn($r) => $this->unseal('practices', $r), $this->db->all('SELECT id, data FROM practices'));
    }

    public function practiceByName(string $name): ?array
    {
        foreach ($this->practices() as $p) {
            if (mb_strtolower($p['name']) === mb_strtolower(trim($name))) {
                return $p;
            }
        }
        return null;
    }

    /** De praktijk van een arts (altijd aanwezig; een arts zonder praktijk krijgt een eigen praktijk). */
    public function practiceOf(array &$arts): array
    {
        if (empty($arts['practice_id'])) {
            $arts['practice_id'] = $this->userById($arts['id'])['practice_id'] ?? null; // al eerder in dit verzoek aangemaakt?
        }
        $p = $this->practiceById($arts['practice_id'] ?? null);
        if ($p === null) {
            $p = $this->createPractice('Praktijk van ' . $arts['name']);
            $this->db->run('UPDATE users SET practice_id = ? WHERE id = ?', [$p['id'], $arts['id']]);
            $arts['practice_id'] = $p['id'];
            $arts['is_admin'] = true;
            $this->saveUser($arts);
        }
        return $p;
    }

    /** Maakt een huisartsaccount in een praktijk. @return array|null null als het e-mailadres al bestaat */
    public function createArts(string $practiceId, string $name, string $email, string $passwordHash, bool $admin, bool $mustChangePassword): ?array
    {
        $user = $this->createUser($name, $email, $passwordHash);
        if ($user === null) {
            return null;
        }
        $this->db->run('UPDATE users SET practice_id = ? WHERE id = ?', [$practiceId, $user['id']]);
        $user['practice_id'] = $practiceId;
        $user['role'] = 'arts';
        $user['is_admin'] = $admin;
        $user['must_change_password'] = $mustChangePassword;
        $this->saveUser($user);
        return $user;
    }

    /** @return list<array> artsen van deze praktijk, op naam gesorteerd */
    public function artsInPractice(string $practiceId): array
    {
        $rows = $this->db->all('SELECT id, practice_id, data FROM users WHERE practice_id = ?', [$practiceId]);
        $list = array_map(fn($r) => $this->hydrateUser($r), $rows);
        usort($list, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return $list;
    }

    /**
     * Verwijdert een arts. Zijn patiënten blijven bij de praktijk staan (zonder arts) en dat wordt in hun historie vastgelegd.
     * De identiteitsbevestiging (naam van de arts die de patiënt zag) blijft ongewijzigd bij de verificatie bewaard.
     */
    public function removeArts(string $artsId, string $byName): void
    {
        $arts = $this->userById($artsId);
        if (!$this->isArts($arts)) {
            return;
        }
        $this->db->transaction(function () use ($artsId, $arts, $byName) {
            foreach ($this->db->all('SELECT id, practice_id, arts_id, patient_user_id, data FROM verifications WHERE arts_id = ?', [$artsId]) as $row) {
                $v = $this->hydrateVerification($row);
                $v['history'][] = ['at' => time(), 'action' => 'arts_verwijderd', 'from' => $arts['name'], 'by' => $byName];
                $v['arts_id'] = null;
                $this->saveVerification($v);
            }
            $this->db->run('DELETE FROM sessions WHERE user_idx = ?', [$this->crypto->index('session-user', $artsId)]);
            $this->db->run('DELETE FROM users WHERE id = ?', [$artsId]);
        });
    }

    // ------------------------------------------------------------ wachtwoord vergeten

    private const RESET_LIFETIME = 3600;

    /** Maakt een herstelcode voor het wachtwoord (vervangt eerdere). Alleen de HMAC wordt bewaard. */
    public function createPasswordReset(string $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db->run('DELETE FROM password_resets WHERE user_id = ?', [$userId]);
        $this->db->run(
            'INSERT INTO password_resets (token_idx, user_id, expires_at) VALUES (?, ?, ?)',
            [$this->crypto->index('pwreset', $token), $userId, time() + self::RESET_LIFETIME]
        );
        return $token;
    }

    /** De gebruiker bij een geldige, niet verlopen herstelcode; anders null. */
    public function passwordResetUser(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = $this->db->one('SELECT user_id, expires_at FROM password_resets WHERE token_idx = ?', [$this->crypto->index('pwreset', $token)]);
        return ($row && (int) $row['expires_at'] >= time()) ? $this->userById($row['user_id']) : null;
    }

    public function clearPasswordResets(string $userId): void
    {
        $this->db->run('DELETE FROM password_resets WHERE user_id = ?', [$userId]);
    }

    public function endSessions(string $userId): void
    {
        $this->db->run('DELETE FROM sessions WHERE user_idx = ?', [$this->crypto->index('session-user', $userId)]);
    }

    // --------------------------------------------------------------- herstelcodes

    private const RECOVERY_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private function recoveryMac(string $userId, string $code): string
    {
        return $this->crypto->mac('recovery', $userId . '|' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? ''));
    }

    /**
     * Maakt 10 nieuwe herstelcodes en vervangt de oude. De codes zelf staan alleen in het antwoord; bewaard wordt een HMAC.
     * @return list<string> bijvoorbeeld "K7QM2-X9PDA"
     */
    public function newRecoveryCodes(array &$user): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $c = '';
            for ($j = 0; $j < 10; $j++) {
                $c .= self::RECOVERY_ALPHABET[random_int(0, 31)];
            }
            $codes[] = substr($c, 0, 5) . '-' . substr($c, 5);
        }
        $user['recovery_codes'] = array_map(fn($c) => $this->recoveryMac($user['id'], $c), $codes);
        $this->saveUser($user);
        return $codes;
    }

    /** Gebruikt een herstelcode (eenmalig). Geeft true als de code klopte. */
    public function useRecoveryCode(array &$user, string $code): bool
    {
        $mac = $this->recoveryMac($user['id'], $code);
        foreach ($user['recovery_codes'] ?? [] as $i => $stored) {
            if (hash_equals($stored, $mac)) {
                unset($user['recovery_codes'][$i]);
                $user['recovery_codes'] = array_values($user['recovery_codes']);
                $this->saveUser($user);
                return true;
            }
        }
        return false;
    }

    /** Verwijdert gebruiker en (via ON DELETE CASCADE) alle bijbehorende gegevens + sessies. */
    public function deleteUser(string $id): void
    {
        $this->db->transaction(function () use ($id) {
            $this->db->run('DELETE FROM sessions WHERE user_idx = ?', [$this->crypto->index('session-user', $id)]);
            // persoonsgegevens in verificaties van een verwijderde patiënt mogen niet achterblijven
            $this->db->run('DELETE FROM verifications WHERE patient_user_id = ?', [$id]);
            $this->db->run('DELETE FROM users WHERE id = ?', [$id]);
        });
    }

    // ----------------------------------------------------------- declarations

    private function hydrateDeclaration(array $row): array
    {
        $d = $this->unseal('declarations', $row);
        $d['user_id'] = $row['user_id'];
        $d['token_idx'] = $row['token_idx'];
        return $d + [
            'token_expires_at' => null,
            'notification_email' => null,
            'current_version_id' => null,
            'is_published' => false,
            'draft' => null,
        ];
    }

    /** Geeft de verklaring van de gebruiker; maakt hem aan als hij er nog niet is. */
    public function declarationForUser(string $userId): array
    {
        $row = $this->db->one('SELECT id, user_id, token_idx, data FROM declarations WHERE user_id = ?', [$userId]);
        if (!$row) {
            $id = $this->newId();
            $data = [
                'is_published' => false,
                'current_version_id' => null,
                'notification_email' => null,
                'token_expires_at' => null,
                'draft' => null,
                'created_at' => time(),
            ];
            try {
                $this->db->run(
                    'INSERT INTO declarations (id, user_id, token_idx, data) VALUES (?, ?, NULL, ?)',
                    [$id, $userId, $this->seal('declarations', $id, $data)]
                );
            } catch (\PDOException) {
                // gelijktijdig aangemaakt door een ander verzoek
            }
            $row = $this->db->one('SELECT id, user_id, token_idx, data FROM declarations WHERE user_id = ?', [$userId]);
        }
        return $this->hydrateDeclaration($row);
    }

    public function declarationByToken(string $token): ?array
    {
        $row = $this->db->one(
            'SELECT id, user_id, token_idx, data FROM declarations WHERE token_idx = ?',
            [$this->tokenIndex($token)]
        );
        return $row ? $this->hydrateDeclaration($row) : null;
    }

    public function saveDeclaration(array $d): void
    {
        $id = $d['id'];
        $tokenIdx = $d['token_idx'] ?? null;
        unset($d['id'], $d['user_id'], $d['token_idx']);
        $this->db->run(
            'UPDATE declarations SET token_idx = ?, data = ? WHERE id = ?',
            [$tokenIdx, $this->seal('declarations', $id, $d), $id]
        );
    }

    public function tokenIsActive(array $d): bool
    {
        if (empty($d['token_idx'])) {
            return false;
        }
        return !($d['token_expires_at'] && $d['token_expires_at'] < time());
    }

    // --------------------------------------------------------------- versions

    public function addVersion(string $declarationId, array $content): array
    {
        $id = $this->newId();
        $data = ['content' => $content, 'created_at' => time()];
        $this->db->run(
            'INSERT INTO versions (id, declaration_id, data) VALUES (?, ?, ?)',
            [$id, $declarationId, $this->seal('versions', $id, $data)]
        );
        return ['id' => $id] + $data;
    }

    /** Oudste eerst. @return list<array> */
    public function versions(string $declarationId): array
    {
        $rows = $this->db->all('SELECT id, data FROM versions WHERE declaration_id = ? ORDER BY seq ASC', [$declarationId]);
        return array_map(fn($r) => $this->unseal('versions', $r), $rows);
    }

    public function version(?string $id, string $declarationId): ?array
    {
        if (!$id) {
            return null;
        }
        $row = $this->db->one('SELECT id, data FROM versions WHERE id = ? AND declaration_id = ?', [$id, $declarationId]);
        return $row ? $this->unseal('versions', $row) : null;
    }

    public function versionCount(string $declarationId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM versions WHERE declaration_id = ?', [$declarationId]);
    }

    // ----------------------------------------------------------------- shares

    /** @return array{share: array, token: string} */
    public function addShare(string $declarationId, string $email, ?string $name, string $role): array
    {
        $id = $this->newId();
        $token = bin2hex(random_bytes(32));
        $data = [
            'email' => $email,
            'name' => $name,
            'role' => $role,
            'accepted_at' => null,
            'revoked_at' => null,
            'created_at' => time(),
        ];
        $this->db->run(
            'INSERT INTO shares (id, declaration_id, token_idx, data) VALUES (?, ?, ?, ?)',
            [$id, $declarationId, $this->tokenIndex($token), $this->seal('shares', $id, $data)]
        );
        return ['share' => ['id' => $id] + $data, 'token' => $token];
    }

    /** Nieuwste eerst. @return list<array> */
    public function shares(string $declarationId): array
    {
        $rows = $this->db->all('SELECT id, data FROM shares WHERE declaration_id = ? ORDER BY seq DESC', [$declarationId]);
        return array_map(fn($r) => $this->unseal('shares', $r), $rows);
    }

    public function shareById(string $id, string $declarationId): ?array
    {
        $row = $this->db->one('SELECT id, declaration_id, data FROM shares WHERE id = ? AND declaration_id = ?', [$id, $declarationId]);
        return $row ? $this->unseal('shares', $row) + ['declaration_id' => $row['declaration_id']] : null;
    }

    public function shareByToken(string $token): ?array
    {
        $row = $this->db->one('SELECT id, declaration_id, data FROM shares WHERE token_idx = ?', [$this->tokenIndex($token)]);
        return $row ? $this->unseal('shares', $row) + ['declaration_id' => $row['declaration_id']] : null;
    }

    public function saveShare(array $share): void
    {
        $id = $share['id'];
        unset($share['id'], $share['declaration_id']);
        $this->db->run('UPDATE shares SET data = ? WHERE id = ?', [$this->seal('shares', $id, $share), $id]);
    }

    public function activeShareCount(string $declarationId): int
    {
        return count(array_filter($this->shares($declarationId), fn($s) => empty($s['revoked_at'])));
    }

    public function declarationById(string $id): ?array
    {
        $row = $this->db->one('SELECT id, user_id, token_idx, data FROM declarations WHERE id = ?', [$id]);
        return $row ? $this->hydrateDeclaration($row) : null;
    }

    // ----------------------------------------------------------- verificaties

    /** Zoekwaarde van een 6-cijferige code (sms of papier), gebonden aan de verificatie. */
    public function codeMac(string $verificationId, string $code): string
    {
        return $this->crypto->mac('sms-code', $verificationId . '|' . $code);
    }

    public function newCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /** Zoeksleutel: een uitnodiging hoort bij een e-mailadres. Er wordt geen BSN verwerkt of bewaard. */
    public function verificationKey(string $email): string
    {
        return $this->crypto->index('invite', mb_strtolower(trim($email)));
    }

    /**
     * Legt de uitnodiging van de arts vast; de arts heeft de patiënt daarbij in persoon gezien en geïdentificeerd.
     * @return array{verification: array, code: string} de code staat alleen hier in platte tekst
     */
    public function addVerification(string $artsId, string $practiceId, string $email, array $fields): array
    {
        $id = $this->newId();
        $code = $this->newCode();
        $data = $fields + [
            'patient_email' => mb_strtolower(trim($email)),
            'code_mac' => $this->codeMac($id, $code),
            'status' => 'uitgenodigd', // uitgenodigd | geverifieerd | ingetrokken
            'attempts' => 0,
            'expires_at' => time() + 14 * 86400,
            'created_at' => time(),
            'attested_at' => time(),
            'verified_at' => null,
            'revoked_at' => null,
            'revoked_by' => null,
            'history' => [],
        ];
        $this->db->run(
            'INSERT INTO verifications (id, practice_id, arts_id, patient_user_id, key_idx, data) VALUES (?, ?, ?, NULL, ?, ?)',
            [$id, $practiceId, $artsId, $this->verificationKey($email), $this->seal('verifications', $id, $data)]
        );
        return ['verification' => ['id' => $id, 'practice_id' => $practiceId, 'arts_id' => $artsId, 'patient_user_id' => null] + $data, 'code' => $code];
    }

    /** Openstaande uitnodigingen voor dit e-mailadres (nieuwste eerst). @return list<array> */
    public function openVerifications(string $email): array
    {
        $rows = $this->db->all(
            'SELECT id, practice_id, arts_id, patient_user_id, data FROM verifications WHERE key_idx = ? ORDER BY seq DESC',
            [$this->verificationKey($email)]
        );
        $open = [];
        foreach ($rows as $r) {
            $v = $this->hydrateVerification($r);
            if ($v['status'] === 'uitgenodigd' && $v['expires_at'] >= time()) {
                $open[] = $v;
            }
        }
        return $open;
    }

    private function hydrateVerification(array $row): array
    {
        return $this->unseal('verifications', $row) + ['history' => []]
            + ['practice_id' => $row['practice_id'], 'arts_id' => $row['arts_id'], 'patient_user_id' => $row['patient_user_id']];
    }

    public function verificationById(string $id): ?array
    {
        $row = $this->db->one('SELECT id, practice_id, arts_id, patient_user_id, data FROM verifications WHERE id = ?', [$id]);
        return $row ? $this->hydrateVerification($row) : null;
    }

    /** Alle patiënten van de praktijk, nieuwste eerst. @return list<array> */
    public function verificationsForPractice(string $practiceId): array
    {
        $rows = $this->db->all('SELECT id, practice_id, arts_id, patient_user_id, data FROM verifications WHERE practice_id = ? ORDER BY seq DESC', [$practiceId]);
        return array_map(fn($r) => $this->hydrateVerification($r), $rows);
    }

    /** Nieuwste eerst. @return list<array> */
    public function verificationsForPatient(string $patientId): array
    {
        $rows = $this->db->all('SELECT id, practice_id, arts_id, patient_user_id, data FROM verifications WHERE patient_user_id = ? ORDER BY seq DESC', [$patientId]);
        return array_map(fn($r) => $this->hydrateVerification($r), $rows);
    }

    public function saveVerification(array $v): void
    {
        $id = $v['id'];
        $patient = $v['patient_user_id'] ?? null;
        $arts = $v['arts_id'] ?? null;
        unset($v['id'], $v['arts_id'], $v['patient_user_id'], $v['practice_id']);
        $this->db->run(
            'UPDATE verifications SET arts_id = ?, patient_user_id = ?, data = ? WHERE id = ?',
            [$arts, $patient, $this->seal('verifications', $id, $v), $id]
        );
    }

    /** Wijst een patiënt toe aan een arts van dezelfde praktijk (of aan niemand) en legt dat vast in de historie. */
    public function assignVerification(array $v, ?array $newArts, string $byName): void
    {
        $from = $v['arts_id'] ? ($this->userById($v['arts_id'])['name'] ?? null) : null;
        $v['history'][] = ['at' => time(), 'action' => 'overgenomen', 'from' => $from, 'to' => $newArts['name'] ?? null, 'by' => $byName];
        $v['arts_id'] = $newArts['id'] ?? null;
        $this->saveVerification($v);
    }

    /**
     * Ruimt uitnodigingen op die nooit tot een registratie leidden (verlopen of ingetrokken) zodra de bewaartermijn voorbij is.
     * Zo blijven er geen gegevens van mensen achter die geen account hebben genomen.
     * @return int aantal verwijderde uitnodigingen
     */
    public function purgeUnusedInvitations(int $graceSeconds = 30 * 86400): int
    {
        $n = 0;
        $this->db->run('DELETE FROM password_resets WHERE expires_at < ?', [time()]);
        foreach ($this->db->all('SELECT id, practice_id, arts_id, patient_user_id, data FROM verifications WHERE patient_user_id IS NULL') as $row) {
            $v = $this->hydrateVerification($row);
            $end = $v['status'] === 'ingetrokken' ? (int) ($v['revoked_at'] ?? 0) : (int) $v['expires_at'];
            if ($v['status'] !== 'geverifieerd' && $end + $graceSeconds < time()) {
                $n += $this->db->run('DELETE FROM verifications WHERE id = ?', [$v['id']]);
            }
        }
        return $n;
    }

    /**
     * De geldige verificatie van de eigenaar van deze verklaring, voor weergave aan lezers.
     * Het betreft de identiteit van het account, niet de inhoud van een bepaalde versie.
     * Geeft het volledige patiënt-ID niet terug, alleen een afgeschermde variant.
     */
    public function verificationPanel(array $declaration): ?array
    {
        foreach ($this->verificationsForPatient($declaration['user_id']) as $v) {
            if ($v['status'] !== 'geverifieerd') {
                continue;
            }
            $id = (string) ($v['his_id'] ?? '');
            return [
                'arts_name' => $v['arts_name'] ?? '',
                'practice' => $v['practice'] ?? null,
                'verified_at' => $v['attested_at'] ?? $v['verified_at'],
                'his_masked' => $id === '' ? '' : '…' . substr($id, -6),
            ];
        }
        return null;
    }

    // ------------------------------------------------------------ access logs

    public function addLog(string $declarationId, string $type, ?string $identifier, string $ip, string $userAgent): void
    {
        $id = $this->newId();
        $data = [
            'accessor_type' => $type,
            'accessor_identifier' => $identifier,
            'ip_address' => $ip,
            'user_agent' => mb_substr($userAgent, 0, 500),
            'accessed_at' => time(),
        ];
        $this->db->run(
            'INSERT INTO access_logs (id, declaration_id, data) VALUES (?, ?, ?)',
            [$id, $declarationId, $this->seal('access_logs', $id, $data)]
        );
    }

    public function logCount(string $declarationId): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM access_logs WHERE declaration_id = ?', [$declarationId]);
    }

    /** @return list<array> */
    public function logs(string $declarationId): array
    {
        $rows = $this->db->all('SELECT id, data FROM access_logs WHERE declaration_id = ? ORDER BY seq ASC', [$declarationId]);
        return array_map(fn($r) => $this->unseal('access_logs', $r), $rows);
    }

    // ------------------------------------------------------------------- meta

    /** Controleert (of legt vast) dat APP_KEY bij deze database hoort. */
    public function verifyKey(): void
    {
        $row = $this->db->one("SELECT v FROM meta WHERE k = 'key_check'");
        if (!$row) {
            $this->db->run(
                "INSERT OR IGNORE INTO meta (k, v) VALUES ('key_check', ?)",
                [$this->crypto->seal('wilsverklaring', 'meta:key_check')]
            );
            return;
        }
        try {
            $this->crypto->open($row['v'], 'meta:key_check');
        } catch (DecryptException) {
            throw new \RuntimeException('APP_KEY hoort niet bij deze database. Zonder de juiste sleutel zijn de gegevens onleesbaar.');
        }
    }
}
