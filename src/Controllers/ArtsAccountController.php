<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Response;

/** Eigen wachtwoord van de huisarts wijzigen (verplicht na een tijdelijk wachtwoord). */
final class ArtsAccountController extends Controller
{
    private const MIN_PASSWORD = 12;

    public function form(): Response
    {
        return $this->view('arts/wachtwoord', ['forced' => !empty($this->user()['must_change_password'])]);
    }

    public function save(): Response
    {
        $user = $this->user();
        $key = 'pw-change:' . $user['id'];
        if ($this->app->limiter->tooMany($key, 5)) {
            return $this->fail('/arts/wachtwoord', ['current' => 'Te veel pogingen. Probeer het over een kwartier opnieuw.']);
        }
        $new = $this->input('password');
        $errors = [];
        if (!password_verify($this->input('current'), $user['password'])) {
            $this->app->limiter->hit($key, 900);
            $errors['current'] = 'Uw huidige wachtwoord klopt niet.';
        }
        if (mb_strlen($new) < self::MIN_PASSWORD) {
            $errors['password'] = 'Uw nieuwe wachtwoord moet minimaal ' . self::MIN_PASSWORD . ' tekens bevatten.';
        } elseif (strlen($new) > 1024) {
            $errors['password'] = 'Uw wachtwoord is te lang.';
        } elseif ($new !== $this->input('password_confirmation')) {
            $errors['password'] = 'De wachtwoorden komen niet overeen.';
        } elseif (password_verify($new, $user['password'])) {
            $errors['password'] = 'Kies een ander wachtwoord dan het huidige.';
        }
        if ($errors) {
            return $this->fail('/arts/wachtwoord', $errors);
        }
        $this->app->limiter->clear($key);
        $user['password'] = $this->app->auth->hashPassword($new);
        $user['must_change_password'] = false;
        $this->app->store->saveUser($user);
        $this->flash('Uw wachtwoord is gewijzigd.');
        return $this->redirect('/arts');
    }
}
