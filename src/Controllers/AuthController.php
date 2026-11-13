<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Response;

final class AuthController extends Controller
{
    private const MIN_PASSWORD = 10;

    public function loginForm(): Response
    {
        return $this->view('auth/login');
    }

    public function login(): Response
    {
        $email = mb_strtolower(trim($this->input('email')));
        $password = $this->input('password');

        if ($email === '' || $password === '') {
            return $this->fail('/inloggen', ['email' => 'Vul uw e-mailadres en wachtwoord in.'], ['email' => $email]);
        }

        $ip = $this->app->request->ip();
        $emailKey = 'login:' . $email . '|' . $ip;
        $ipKey = 'login-ip:' . $ip;
        if ($this->app->limiter->tooMany($emailKey, 5) || $this->app->limiter->tooMany($ipKey, 30)) {
            return $this->fail('/inloggen', ['email' => 'Te veel mislukte pogingen. Probeer het over een kwartier opnieuw.'], ['email' => $email]);
        }

        $user = $this->app->auth->attempt($email, $password);
        if ($user === null) {
            $this->app->limiter->hit($emailKey, 900);
            $this->app->limiter->hit($ipKey, 900);
            return $this->fail('/inloggen', ['email' => 'Deze gegevens komen niet overeen.'], ['email' => $email]);
        }

        $this->app->limiter->clear($emailKey);
        $this->app->auth->login($user);

        return $this->redirect($this->app->auth->twoFactorEnabled($user) ? '/2fa/controle' : '/2fa/instellen');
    }

    public function registerForm(): Response
    {
        return $this->view('auth/register');
    }

    public function register(): Response
    {
        $store = $this->app->store;
        $name = trim($this->input('name'));
        $email = mb_strtolower(trim($this->input('email')));
        $code = preg_replace('/\D/', '', $this->input('code')) ?? '';
        $password = $this->input('password');
        $old = ['name' => $name, 'email' => $email];

        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Vul uw naam in (maximaal 120 tekens).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Vul een geldig e-mailadres in.';
        }
        if (strlen($code) !== 6) {
            $errors['code'] = 'Vul de code van 6 cijfers in die uw huisarts u op papier heeft gegeven.';
        }
        if (mb_strlen($password) < self::MIN_PASSWORD) {
            $errors['password'] = 'Uw wachtwoord moet minimaal ' . self::MIN_PASSWORD . ' tekens bevatten.';
        } elseif (strlen($password) > 1024) {
            $errors['password'] = 'Uw wachtwoord is te lang.';
        } elseif ($password !== $this->input('password_confirmation')) {
            $errors['password'] = 'De wachtwoorden komen niet overeen.';
        }
        if ($this->input('akkoord') !== '1') {
            $errors['akkoord'] = 'Om verder te gaan moet u de pilotinformatie en de privacyverklaring hebben gelezen en toestemming geven.';
        }
        if ($errors) {
            return $this->fail('/registreren', $errors, $old);
        }

        // Pogingen tellen per adres én per e-mailadres, ook als het adres niet is uitgenodigd (geen detectie van uitgenodigde adressen).
        $ipOk = $this->app->limiter->attempt('register|' . $this->app->request->ip(), 10, 3600);
        $mailOk = $this->app->limiter->attempt('register-mail|' . $this->app->crypto->index('email', $email), 8, 3600);
        $mismatch = 'Deze combinatie van e-mailadres en code klopt niet, of de uitnodiging is verlopen. Vraag uw huisarts om hulp.';
        if (!$ipOk || !$mailOk) {
            return $this->fail('/registreren', ['code' => 'Te veel pogingen. Probeer het later opnieuw.'], $old);
        }

        $match = null;
        foreach ($store->openVerifications($email) as $v) {
            if (hash_equals($v['code_mac'], $store->codeMac($v['id'], $code))) {
                $match = $v;
                break;
            }
        }
        if ($match === null) {
            // foute code bij een bestaande uitnodiging telt mee; na 5 pogingen vervalt hij
            foreach ($store->openVerifications($email) as $v) {
                $v['attempts'] = (int) $v['attempts'] + 1;
                if ($v['attempts'] >= 5) {
                    $v['status'] = 'ingetrokken';
                    $v['revoked_at'] = time();
                    $v['revoked_by'] = 'blokkade';
                }
                $store->saveVerification($v);
            }
            $this->app->auth->hashPassword($password); // vergelijkbare rekentijd
            return $this->fail('/registreren', ['code' => $mismatch], $old);
        }

        $user = $store->createUser($name, $email, $this->app->auth->hashPassword($password));
        if ($user === null) {
            return $this->fail('/registreren', ['email' => 'Met dit e-mailadres bestaat al een account. Log in, of vraag uw huisarts om hulp.'], $old);
        }

        // Uitdrukkelijke toestemming (art. 9 lid 2 sub a AVG), met de versie van de tekst waarmee die is gegeven.
        $user['consent_at'] = time();
        $user['consent_version'] = \WV\Legal::VERSION;
        $store->saveUser($user);

        // De huisarts heeft bij het uitnodigen al bevestigd dat hij deze patiënt in persoon heeft gezien en geïdentificeerd.
        $match['status'] = 'geverifieerd';
        $match['patient_user_id'] = $user['id'];
        $match['verified_at'] = time();
        $match['attempts'] = 0;
        $match['code_mac'] = null;
        $store->saveVerification($match);

        $this->app->mailer->send(
            $email,
            'Uw account bij WilsVerklaring is aangemaakt',
            "Beste $name,\n\nEr is een account aangemaakt voor dit e-mailadres, op uitnodiging van uw huisarts, {$match['arts_name']} ({$match['practice']}). "
            . "Uw identiteit is daarbij bevestigd. De artsen van de praktijk kunnen uw gepubliceerde verklaring inzien; dat kunt u in uw account altijd intrekken.\n\n"
            . 'Was u dit niet? Neem dan contact op met uw huisartsenpraktijk.'
        );

        $this->app->auth->login($user);
        $this->flash('Welkom. Uw identiteit is bevestigd door uw huisarts. Stel nu tweestapsverificatie in om verder te gaan.');
        return $this->redirect('/2fa/instellen');
    }

    public function logout(): Response
    {
        $this->app->auth->logout();
        return $this->redirect('/');
    }
}
