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
        $name = trim($this->input('name'));
        $email = mb_strtolower(trim($this->input('email')));
        $password = $this->input('password');
        $old = ['name' => $name, 'email' => $email];

        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Vul uw naam in (maximaal 120 tekens).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Vul een geldig e-mailadres in.';
        }
        if (mb_strlen($password) < self::MIN_PASSWORD) {
            $errors['password'] = 'Uw wachtwoord moet minimaal ' . self::MIN_PASSWORD . ' tekens bevatten.';
        } elseif (strlen($password) > 1024) {
            $errors['password'] = 'Uw wachtwoord is te lang.';
        } elseif ($password !== $this->input('password_confirmation')) {
            $errors['password'] = 'De wachtwoorden komen niet overeen.';
        }

        if (!$this->app->limiter->attempt('register|' . $this->app->request->ip(), 10, 3600)) {
            $errors['email'] = 'Te veel registraties vanaf dit adres. Probeer het later opnieuw.';
        }
        if ($errors) {
            return $this->fail('/registreren', $errors, $old);
        }

        $user = $this->app->store->createUser($name, $email, $this->app->auth->hashPassword($password));
        if ($user === null) {
            return $this->fail('/registreren', ['email' => 'Dit e-mailadres is al in gebruik.'], $old);
        }

        $this->app->auth->login($user);
        $this->flash('Welkom. Stel tweestapsverificatie in om verder te gaan.');
        return $this->redirect('/2fa/instellen');
    }

    public function logout(): Response
    {
        $this->app->auth->logout();
        return $this->redirect('/');
    }
}
