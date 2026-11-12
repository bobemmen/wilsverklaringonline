<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\HttpException;
use WV\Response;

/** Patiëntkant van de huisartsverificatie: uitnodiging openen, koppelen en intrekken. */
final class VerificationController extends Controller
{
    private const MAX_ATTEMPTS = 5;

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

        $v['status'] = 'gekoppeld';
        $v['patient_user_id'] = $user['id'];
        $v['linked_at'] = time();
        $v['attempts'] = 0;
        $store->saveVerification($v);

        $this->flash('Gekoppeld aan uw huisarts. Maak uw verklaring af en publiceer hem; uw huisarts kan hem daarna bevestigen.');
        return $this->redirect('/dashboard');
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
