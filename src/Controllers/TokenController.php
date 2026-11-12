<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Response;

final class TokenController extends Controller
{
    public function index(): Response
    {
        $declaration = $this->declaration();
        return $this->view('pages/token', [
            'declaration' => $declaration,
            'tokenActive' => $this->app->store->tokenIsActive($declaration),
            'plaintextToken' => $this->app->session->flashed('plaintext_token'),
            'viewCount' => $this->app->store->logCount($declaration['id']),
        ]);
    }

    public function regenerate(): Response
    {
        $declaration = $this->declaration();
        $token = bin2hex(random_bytes(32));
        $declaration['token_idx'] = $this->app->store->tokenIndex($token);
        $this->app->store->saveDeclaration($declaration);

        $this->app->session->flash('plaintext_token', $token);
        $this->flash('Nieuwe toegangscode aangemaakt.');
        return $this->redirect('/token');
    }

    public function revoke(): Response
    {
        $declaration = $this->declaration();
        $declaration['token_idx'] = null;
        $declaration['token_expires_at'] = null;
        $this->app->store->saveDeclaration($declaration);

        $this->flash('Toegangscode ingetrokken.');
        return $this->redirect('/token');
    }

    public function settings(): Response
    {
        $email = trim($this->input('notification_email'));
        $expires = trim($this->input('access_token_expires_at'));
        $old = ['notification_email' => $email, 'access_token_expires_at' => $expires];
        $errors = [];

        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254)) {
            $errors['notification_email'] = 'Vul een geldig e-mailadres in.';
        }
        $expiresAt = null;
        if ($expires !== '') {
            $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $expires);
            if (!$dt || $dt->format('Y-m-d') !== $expires) {
                $errors['access_token_expires_at'] = 'Vul een geldige datum in.';
            } elseif ($dt->getTimestamp() <= strtotime('today 23:59:59')) {
                $errors['access_token_expires_at'] = 'De vervaldatum moet na vandaag liggen.';
            } else {
                $expiresAt = $dt->setTime(23, 59, 59)->getTimestamp(); // geldig t/m de gekozen dag
            }
        }
        if ($errors) {
            return $this->fail('/token', $errors, $old);
        }

        $declaration = $this->declaration();
        $declaration['notification_email'] = $email !== '' ? mb_strtolower($email) : null;
        $declaration['token_expires_at'] = $expiresAt;
        $this->app->store->saveDeclaration($declaration);

        $this->flash('Instellingen opgeslagen.');
        return $this->redirect('/token');
    }
}
