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
        $row = $this->db->one('SELECT id, data FROM users WHERE id = ?', [$id]);
        return $row ? $this->unseal('users', $row) : null;
    }

    public function isArts(?array $user): bool
    {
        return $user !== null && ($user['role'] ?? 'patient') === 'arts';
    }

    public function userByEmail(string $email): ?array
    {
        $row = $this->db->one('SELECT id, data FROM users WHERE email_idx = ?', [$this->emailIndex($email)]);
        return $row ? $this->unseal('users', $row) : null;
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
            'practice' => null,
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
        return ['id' => $id] + $data;
    }

    public function saveUser(array $user): void
    {
        $id = $user['id'];
        unset($user['id']);
        $this->db->run('UPDATE users SET data = ? WHERE id = ?', [$this->seal('users', $id, $user), $id]);
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

    /** BSN en geboortedatum worden nooit bewaard, alleen als HMAC om later te kunnen vergelijken. */
    public function bsnProof(string $bsn): string
    {
        return $this->crypto->mac('bsn', preg_replace('/\D/', '', $bsn));
    }

    public function dobProof(string $isoDate): string
    {
        return $this->crypto->mac('dob', $isoDate);
    }

    /** @return array{verification: array, token: string} */
    public function addVerification(string $artsId, array $fields): array
    {
        $id = $this->newId();
        $token = bin2hex(random_bytes(32));
        $data = $fields + [
            'status' => 'uitgenodigd', // uitgenodigd | gekoppeld | geverifieerd | ingetrokken
            'attempts' => 0,
            'expires_at' => time() + 14 * 86400,
            'created_at' => time(),
            'linked_at' => null,
            'verified_at' => null,
            'verified_version_id' => null,
            'verified_hash' => null,
            'revoked_at' => null,
            'revoked_by' => null,
        ];
        $this->db->run(
            'INSERT INTO verifications (id, arts_id, patient_user_id, token_idx, data) VALUES (?, ?, NULL, ?, ?)',
            [$id, $artsId, $this->tokenIndex($token), $this->seal('verifications', $id, $data)]
        );
        return ['verification' => ['id' => $id, 'arts_id' => $artsId, 'patient_user_id' => null] + $data, 'token' => $token];
    }

    private function hydrateVerification(array $row): array
    {
        return $this->unseal('verifications', $row) + ['arts_id' => $row['arts_id'], 'patient_user_id' => $row['patient_user_id']];
    }

    public function verificationByToken(string $token): ?array
    {
        $row = $this->db->one('SELECT id, arts_id, patient_user_id, data FROM verifications WHERE token_idx = ?', [$this->tokenIndex($token)]);
        return $row ? $this->hydrateVerification($row) : null;
    }

    public function verificationById(string $id): ?array
    {
        $row = $this->db->one('SELECT id, arts_id, patient_user_id, data FROM verifications WHERE id = ?', [$id]);
        return $row ? $this->hydrateVerification($row) : null;
    }

    /** Nieuwste eerst. @return list<array> */
    public function verificationsForArts(string $artsId): array
    {
        $rows = $this->db->all('SELECT id, arts_id, patient_user_id, data FROM verifications WHERE arts_id = ? ORDER BY seq DESC', [$artsId]);
        return array_map(fn($r) => $this->hydrateVerification($r), $rows);
    }

    /** Nieuwste eerst. @return list<array> */
    public function verificationsForPatient(string $patientId): array
    {
        $rows = $this->db->all('SELECT id, arts_id, patient_user_id, data FROM verifications WHERE patient_user_id = ? ORDER BY seq DESC', [$patientId]);
        return array_map(fn($r) => $this->hydrateVerification($r), $rows);
    }

    public function saveVerification(array $v): void
    {
        $id = $v['id'];
        $patient = $v['patient_user_id'] ?? null;
        unset($v['id'], $v['arts_id'], $v['patient_user_id']);
        $this->db->run(
            'UPDATE verifications SET patient_user_id = ?, data = ? WHERE id = ?',
            [$patient, $this->seal('verifications', $id, $v), $id]
        );
    }

    /**
     * De meest recente geldige verificatie van deze verklaring, voor weergave aan lezers.
     * Geeft het volledige patiënt-ID niet terug, alleen een afgeschermde variant.
     */
    public function verificationPanel(array $declaration, ?array $version): ?array
    {
        foreach ($this->verificationsForPatient($declaration['user_id']) as $v) {
            if ($v['status'] !== 'geverifieerd') {
                continue;
            }
            $current = $version !== null
                && $v['verified_version_id'] === $version['id']
                && hash_equals((string) $v['verified_hash'], $this->contentHash($version['content']));
            $id = (string) ($v['his_id'] ?? '');
            return [
                'arts_name' => $v['arts_name'] ?? '',
                'practice' => $v['practice'] ?? null,
                'verified_at' => $v['verified_at'],
                'his_masked' => $id === '' ? '' : '…' . substr($id, -6),
                'current' => $current,
            ];
        }
        return null;
    }

    /** Inhoud-vingerafdruk: laat zien of een versie na de verificatie nog gelijk is. */
    public function contentHash(array $content): string
    {
        return $this->crypto->mac('version-content', json_encode($content, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
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
