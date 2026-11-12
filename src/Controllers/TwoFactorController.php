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
        $this->flash('Tweestapsverificatie is ingesteld.');
        return $this->redirect('/dashboard');
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
