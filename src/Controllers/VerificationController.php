<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\HttpException;
use WV\Response;

/** Patiëntkant van de huisartsverificatie: uitnodiging openen, koppelen en intrekken. */
final class VerificationController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const MAX_SENDS = 3;
    private const CODE_TTL = 600;

    public function show(string $token): Response
    {
        $v = $this->find($token);
        $arts = ['name' => $v['arts_name'] ?? '', 'practice' => $v['practice'] ?? null];
        $auth = $this->app->auth;

        if (!$auth->check()) {
            // na inloggen of registreren terug naar deze uitnodiging
            $this->app->session->put('intended', '/uitnodiging/' . $token);
            return $this->view('verification/landing', ['arts' => $arts, 'token' => $token]);
        }
        if ($this->app->store->isArts($auth->user())) {
            throw new HttpException(403);
        }
        if (!$auth->twoFactorEnabled()) {
            $this->app->session->put('intended', '/uitnodiging/' . $token);
            return $this->redirect('/2fa/instellen');
        }
        if (!$auth->twoFactorVerified()) {
            $this->app->session->put('intended', '/uitnodiging/' . $token);
            return $this->redirect('/2fa/controle');
        }
        if ($v['patient_user_id'] === $this->user()['id']) {
            return $this->redirect('/dashboard');
        }

        if ($v['pending_user_id'] === $this->user()['id'] && $v['sms_code_mac'] && $v['sms_expires_at'] > time()) {
            return $this->view('verification/code', ['arts' => $arts, 'token' => $token, 'paper' => ($v['code_method'] ?? 'sms') === 'papier', 'phone' => $v['phone'] ? mask_phone((string) $v['phone']) : '', 'canResend' => ($v['code_method'] ?? 'sms') === 'sms' && (int) $v['sms_sends'] < self::MAX_SENDS]);
        }

        return $this->view('verification/link', ['arts' => $arts, 'token' => $token, 'name' => $v['patient_name']]);
    }

    public function link(string $token): Response
    {
        $v = $this->find($token);
        if ($v['status'] !== 'uitgenodigd') {
            return $this->redirect('/dashboard');
        }
        $user = $this->user();
        $back = '/uitnodiging/' . $token;
        $store = $this->app->store;

        $dob = parse_birthdate($this->input('dob'));
        $bsn = $this->input('bsn');
        $ok = $dob !== null
            && hash_equals($v['dob_proof'], $store->dobProof($dob->format('Y-m-d')))
            && hash_equals($v['bsn_proof'], $store->bsnProof($bsn));

        if (!$ok) {
            $v['attempts'] = (int) $v['attempts'] + 1;
            if ($v['attempts'] >= self::MAX_ATTEMPTS) {
                // geblokkeerd: de arts moet opnieuw uitnodigen
                $v['status'] = 'ingetrokken';
                $v['revoked_at'] = time();
                $v['revoked_by'] = 'blokkade';
                $store->saveVerification($v);
                throw new HttpException(404);
            }
            $store->saveVerification($v);
            return $this->fail($back, ['match' => 'De gegevens komen niet overeen met wat uw huisarts heeft opgegeven. Controleer uw geboortedatum en BSN.']);
        }
        if ($this->input('consent') !== '1') {
            return $this->fail($back, ['consent' => 'Geef toestemming om door te gaan.']);
        }

        if (($v['code_method'] ?? 'sms') === 'papier') {
            // code op papier van de huisarts: er wordt niets verstuurd
            $v['pending_user_id'] = $user['id'];
            $v['sms_code_mac'] = $v['paper_code_mac'];
            $v['sms_expires_at'] = $v['expires_at'];
            $v['sms_attempts'] = 0;
            $store->saveVerification($v);
            return $this->redirect($back);
        }
        // identiteit uit het dossier klopt: tweede controle met een sms-code naar het door de arts opgegeven nummer
        $v['pending_user_id'] = $user['id'];
        $v['sms_sends'] = 0;
        if (!$this->sendCode($v)) {
            $store->saveVerification($v);
            return $this->fail($back, ['match' => 'De sms kon niet worden verstuurd. Probeer het later opnieuw of neem contact op met uw huisarts.']);
        }
        $store->saveVerification($v);
        $this->flash('We hebben een sms met een code gestuurd naar ' . mask_phone((string) $v['phone']) . '.');
        return $this->redirect($back);
    }

    public function code(string $token): Response
    {
        $v = $this->find($token);
        $user = $this->user();
        $store = $this->app->store;
        $back = '/uitnodiging/' . $token;
        if ($v['status'] !== 'uitgenodigd' || $v['pending_user_id'] !== $user['id'] || !$v['sms_code_mac']) {
            return $this->redirect($back);
        }
        if ($v['sms_expires_at'] < time()) {
            return $this->fail($back, ['code' => 'De code is verlopen. Vraag een nieuwe aan.']);
        }

        $given = preg_replace('/\D/', '', $this->input('code')) ?? '';
        $ok = strlen($given) === 6 && hash_equals($v['sms_code_mac'], $this->codeMac($v, $given));
        if (!$ok) {
            $v['sms_attempts'] = (int) $v['sms_attempts'] + 1;
            if ($v['sms_attempts'] >= self::MAX_ATTEMPTS) {
                $v['status'] = 'ingetrokken';
                $v['revoked_at'] = time();
                $v['revoked_by'] = 'blokkade';
                $store->saveVerification($v);
                throw new HttpException(404);
            }
            $store->saveVerification($v);
            return $this->fail($back, ['code' => 'Deze code klopt niet.']);
        }

        $v['status'] = 'gekoppeld';
        $v['patient_user_id'] = $user['id'];
        $v['linked_at'] = time();
        $v['attempts'] = 0;
        $v['pending_user_id'] = null;
        $v['sms_code_mac'] = null;
        $v['sms_expires_at'] = null;
        $store->saveVerification($v);

        $this->flash('Gekoppeld aan uw huisarts. Uw huisarts kan nu bevestigen dat dit account bij u hoort.');
        return $this->redirect('/dashboard');
    }

    public function resend(string $token): Response
    {
        $v = $this->find($token);
        $store = $this->app->store;
        $back = '/uitnodiging/' . $token;
        if ($v['status'] !== 'uitgenodigd' || $v['pending_user_id'] !== $this->user()['id']) {
            return $this->redirect($back);
        }
        if (($v['code_method'] ?? 'sms') !== 'sms' || (int) $v['sms_sends'] >= self::MAX_SENDS) {
            return $this->fail($back, ['code' => 'U kunt geen nieuwe code meer aanvragen. Neem contact op met uw huisarts.']);
        }
        if (!$this->sendCode($v)) {
            $store->saveVerification($v);
            return $this->fail($back, ['code' => 'De sms kon niet worden verstuurd.']);
        }
        $store->saveVerification($v);
        $this->flash('Er is een nieuwe code verstuurd.');
        return $this->redirect($back);
    }

    private function codeMac(array $v, string $code): string
    {
        return $this->app->store->codeMac($v['id'], $code);
    }

    /** Maakt een nieuwe 6-cijferige code (oude vervalt) en verstuurt die; werkt $v bij, bewaart niet. */
    private function sendCode(array &$v): bool
    {
        $code = $this->app->store->newCode();
        $v['sms_code_mac'] = $this->codeMac($v, $code);
        $v['sms_expires_at'] = time() + self::CODE_TTL;
        $v['sms_attempts'] = 0;
        $v['sms_sends'] = (int) $v['sms_sends'] + 1;
        return $this->app->sms->send((string) $v['phone'], "WilsVerklaring: uw code is $code. Deel deze code met niemand. Geldig 10 minuten.");
    }

    public function patientRevoke(string $id): Response
    {
        $v = $this->app->store->verificationById($id);
        if (!$v || $v['patient_user_id'] !== $this->user()['id']) {
            throw new HttpException(404);
        }
        $v['status'] = 'ingetrokken';
        $v['revoked_at'] = time();
        $v['revoked_by'] = 'patient';
        $this->app->store->saveVerification($v);

        $this->flash('De koppeling met uw huisarts is ingetrokken. Uw huisarts kan uw verklaring niet meer inzien.');
        return $this->redirect('/dashboard');
    }

    /** Open uitnodiging; onbekend, verlopen of ingetrokken geeft altijd dezelfde 404. */
    private function find(string $token): array
    {
        $v = $this->app->store->verificationByToken($token);
        if (!$v || $v['status'] !== 'uitgenodigd' || $v['expires_at'] < time()) {
            // een al gekoppelde patiënt mag zijn eigen link blijven gebruiken (doorverwijzing naar dashboard)
            if ($v && $v['status'] !== 'ingetrokken' && $this->app->auth->check() && $v['patient_user_id'] === $this->app->auth->user()['id']) {
                return $v;
            }
            throw new HttpException(404);
        }
        return $v;
    }
}
