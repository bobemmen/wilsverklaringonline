<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Response;

/**
 * Wachtwoord vergeten voor patiënten.
 *
 *  - Het antwoord is altijd hetzelfde, of het e-mailadres bij een account hoort of niet.
 *  - De link is 60 minuten geldig en eenmalig; alleen een HMAC ervan staat in de database.
 *  - Tweestapsverificatie blijft gewoon gelden: een nieuw wachtwoord geeft geen toegang zonder de tweede factor.
 *  - Na het wijzigen worden alle sessies van het account beëindigd en krijgt de eigenaar een bevestigingsmail.
 *  - Artsen gebruiken dit niet: hun wachtwoord wordt door een praktijkbeheerder opnieuw ingesteld.
 */
final class PasswordResetController extends Controller
{
    private const MIN_PASSWORD = 10;

    public function forgotForm(): Response
    {
        return $this->view('auth/forgot');
    }

    public function forgot(): Response
    {
        $email = mb_strtolower(trim($this->input('email')));
        $store = $this->app->store;
        $generic = 'Als dit e-mailadres bij een account hoort, ontvangt u binnen enkele minuten een e-mail met een link om een nieuw wachtwoord te kiezen. De link is een uur geldig. Niets ontvangen? Kijk ook in uw map met ongewenste e-mail.';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            return $this->fail('/wachtwoord-vergeten', ['email' => 'Vul een geldig e-mailadres in.'], ['email' => $email]);
        }

        // maximaal 3 verzoeken per uur per e-mailadres, ook voor onbekende adressen (geen verschil zichtbaar)
        $allowed = $this->app->limiter->attempt('forgot-mail|' . $this->app->crypto->index('email', $email), 3, 3600);
        $user = $allowed ? $store->userByEmail($email) : null;
        if ($user !== null && !$store->isArts($user)) {
            $token = $store->createPasswordReset($user['id']);
            $this->app->mailer->send(
                $user['email'],
                'Nieuw wachtwoord kiezen voor WilsVerklaring',
                "Beste {$user['name']},\n\nU of iemand anders heeft gevraagd om een nieuw wachtwoord voor uw account. "
                . "Kies hier uw nieuwe wachtwoord (de link is een uur geldig en werkt één keer):\n\n"
                . $this->app->url('/wachtwoord-resetten/' . $token) . "\n\n"
                . "U blijft uw tweestapsverificatie nodig hebben om in te loggen.\n\n"
                . "Was u dit niet? Dan kunt u deze e-mail negeren; uw wachtwoord verandert niet."
            );
        }
        $this->flash($generic);
        return $this->redirect('/wachtwoord-vergeten');
    }

    public function resetForm(string $token): Response
    {
        return $this->view('auth/reset', ['token' => $token, 'valid' => $this->app->store->passwordResetUser($token) !== null]);
    }

    public function reset(string $token): Response
    {
        $store = $this->app->store;
        $user = $store->passwordResetUser($token);
        if ($user === null || $store->isArts($user)) {
            return $this->redirect('/wachtwoord-resetten/' . $token);
        }
        $password = $this->input('password');
        $url = '/wachtwoord-resetten/' . $token;
        if (mb_strlen($password) < self::MIN_PASSWORD) {
            return $this->fail($url, ['password' => 'Uw wachtwoord moet minimaal ' . self::MIN_PASSWORD . ' tekens bevatten.']);
        }
        if (strlen($password) > 1024) {
            return $this->fail($url, ['password' => 'Uw wachtwoord is te lang.']);
        }
        if ($password !== $this->input('password_confirmation')) {
            return $this->fail($url, ['password' => 'De wachtwoorden komen niet overeen.']);
        }

        $user['password'] = $this->app->auth->hashPassword($password);
        $user['must_change_password'] = false;
        $store->saveUser($user);
        $store->clearPasswordResets($user['id']);
        $store->endSessions($user['id']);
        $this->app->mailer->send(
            $user['email'],
            'Uw wachtwoord is gewijzigd',
            "Beste {$user['name']},\n\nUw wachtwoord voor WilsVerklaring is zojuist gewijzigd. Was u dit niet? "
            . "Neem dan direct contact op met uw huisartsenpraktijk en vraag om uw account te laten controleren."
        );
        $this->flash('Uw wachtwoord is gewijzigd. Log in met uw nieuwe wachtwoord en uw tweestapsverificatie.');
        return $this->redirect('/inloggen');
    }
}
