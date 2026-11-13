<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Response;
use WV\Totp;

final class TwoFactorController extends Controller
{
    private function issuer(): string
    {
        return \WV\Config::get('APP_NAME', 'WilsVerklaring');
    }

    public function setup(): Response
    {
        $user = $this->user();

        // Al ingesteld: het geheim wordt nooit opnieuw getoond.
        if ($this->app->auth->twoFactorEnabled($user)) {
            if (!$this->app->auth->twoFactorVerified()) {
                return $this->redirect('/2fa/controle');
            }
            return $this->view('auth/two-factor-setup', ['confirmed' => true, 'secret' => null, 'otpauth' => null]);
        }

        if (empty($user['two_factor_secret'])) {
            $user['two_factor_secret'] = Totp::generateSecret();
            $this->app->store->saveUser($user);
        }

        return $this->view('auth/two-factor-setup', [
            'confirmed' => false,
            'secret' => $user['two_factor_secret'],
            'otpauth' => Totp::otpauthUrl($user['email'], $user['two_factor_secret'], $this->issuer()),
        ]);
    }

    public function confirm(): Response
    {
        $user = $this->user();
        if ($this->app->auth->twoFactorEnabled($user) || empty($user['two_factor_secret'])) {
            return $this->redirect('/2fa/instellen');
        }

        $step = $this->checkCode($user);
        if ($step === null) {
            return $this->fail('/2fa/instellen', ['code' => 'Onjuiste code. Probeer het opnieuw.']);
        }

        $user['two_factor_confirmed_at'] = time();
        $user['two_factor_last_step'] = $step;
        $this->app->store->saveUser($user);

        $this->app->auth->markTwoFactorVerified();
        $this->app->session->put('recovery_codes', $this->app->store->newRecoveryCodes($user));
        $this->flash('Tweestapsverificatie is ingesteld.');
        return $this->redirect('/2fa/herstelcodes');
    }

    /** Toont de nieuwe herstelcodes tot de gebruiker bevestigt dat ze zijn bewaard (de sessie is versleuteld). */
    public function recoveryCodes(): Response
    {
        $codes = $this->app->session->get('recovery_codes');
        if (!is_array($codes) || !$codes) {
            return $this->redirect('/2fa/beheer');
        }
        return $this->view('auth/recovery-codes', ['codes' => $codes]);
    }

    public function recoveryCodesSaved(): Response
    {
        $this->app->session->forget('recovery_codes');
        return $this->redirect('/dashboard');
    }

    /** Overzicht: aantal resterende herstelcodes, nieuwe codes of een nieuw apparaat (met wachtwoord). */
    public function manage(): Response
    {
        $user = $this->user();
        return $this->view('auth/two-factor-manage', [
            'remaining' => count($user['recovery_codes'] ?? []),
            'isArts' => $this->app->store->isArts($user),
        ]);
    }

    public function newRecoveryCodes(): Response
    {
        $user = $this->user();
        if (!$this->confirmPassword($user, '/2fa/beheer')) {
            return $this->fail('/2fa/beheer', ['password' => 'Uw wachtwoord klopt niet.']);
        }
        $this->app->session->put('recovery_codes', $this->app->store->newRecoveryCodes($user));
        $this->flash('Nieuwe herstelcodes aangemaakt. De oude codes werken niet meer.');
        return $this->redirect('/2fa/herstelcodes');
    }

    /** Nieuw apparaat koppelen: het oude geheim vervalt; herstelcodes blijven geldig tot de bevestiging. */
    public function newDevice(): Response
    {
        $user = $this->user();
        if (!$this->confirmPassword($user, '/2fa/beheer')) {
            return $this->fail('/2fa/beheer', ['password' => 'Uw wachtwoord klopt niet.']);
        }
        $user['two_factor_secret'] = null;
        $user['two_factor_confirmed_at'] = null;
        $user['two_factor_last_step'] = 0;
        $this->app->store->saveUser($user);
        $this->flash('Koppel nu uw nieuwe apparaat.');
        return $this->redirect('/2fa/instellen');
    }

    /** Inloggen met een herstelcode in plaats van een code uit de app (eenmalig). */
    public function recoveryForm(): Response
    {
        if (!$this->app->auth->twoFactorEnabled($this->user()) || $this->app->auth->twoFactorVerified()) {
            return $this->redirect('/dashboard');
        }
        return $this->view('auth/recovery-use');
    }

    public function recoveryUse(): Response
    {
        $user = $this->user();
        if (!$this->app->auth->twoFactorEnabled($user) || $this->app->auth->twoFactorVerified()) {
            return $this->redirect('/dashboard');
        }
        $key = '2fa:' . $user['id'];
        if ($this->app->limiter->tooMany($key, 5) || !$this->app->store->useRecoveryCode($user, $this->input('code'))) {
            $this->app->limiter->hit($key, 300);
            return $this->fail('/2fa/herstel', ['code' => 'Deze herstelcode klopt niet of is al gebruikt.']);
        }
        $this->app->limiter->clear($key);
        $this->app->auth->markTwoFactorVerified();
        $left = count($user['recovery_codes']);
        $this->app->mailer->send(
            $user['email'],
            'Er is een herstelcode gebruikt om in te loggen',
            "Beste {$user['name']},\n\nEr is zojuist een herstelcode gebruikt om in te loggen op uw account. U heeft er nog $left over. "
            . 'Was u dit niet? Wijzig dan direct uw wachtwoord en neem contact op met uw huisartsenpraktijk.'
        );
        $this->flash($left > 0
            ? "U bent ingelogd met een herstelcode. U heeft er nog $left over. Koppel zo nodig hieronder een nieuw apparaat of maak nieuwe codes."
            : 'U bent ingelogd met uw laatste herstelcode. Maak hieronder meteen nieuwe codes of koppel een nieuw apparaat.');
        return $this->redirect('/2fa/beheer');
    }

    private function confirmPassword(array $user, string $back): bool
    {
        $key = 'pw-confirm:' . $user['id'];
        if ($this->app->limiter->tooMany($key, 5)) {
            return false;
        }
        if (!password_verify($this->input('password'), $user['password'])) {
            $this->app->limiter->hit($key, 900);
            return false;
        }
        $this->app->limiter->clear($key);
        return true;
    }

    public function challenge(): Response
    {
        $user = $this->user();
        if (!$this->app->auth->twoFactorEnabled($user)) {
            return $this->redirect('/2fa/instellen');
        }
        if ($this->app->auth->twoFactorVerified()) {
            return $this->redirect('/dashboard');
        }
        return $this->view('auth/two-factor-challenge');
    }

    public function verify(): Response
    {
        $user = $this->user();
        if (!$this->app->auth->twoFactorEnabled($user)) {
            return $this->redirect('/2fa/instellen');
        }

        $step = $this->checkCode($user);
        if ($step === null) {
            return $this->fail('/2fa/controle', ['code' => 'Onjuiste code. Probeer het opnieuw.']);
        }
        $user['two_factor_last_step'] = $step;
        $this->app->store->saveUser($user);

        $this->app->auth->markTwoFactorVerified();
        return $this->redirect($this->intended());
    }

    /** Controleert de ingevoerde code met rate limiting en weigering van hergebruik. Geeft de tijdstap of null. */
    private function checkCode(array $user): ?int
    {
        $key = '2fa:' . $user['id'];
        if ($this->app->limiter->tooMany($key, 5)) {
            return null;
        }
        $step = Totp::verify((string) $user['two_factor_secret'], $this->input('code'));
        if ($step === null || $step <= (int) ($user['two_factor_last_step'] ?? 0)) {
            $this->app->limiter->hit($key, 300);
            return null;
        }
        $this->app->limiter->clear($key);
        return $step;
    }
}
