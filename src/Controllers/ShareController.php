<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\HttpException;
use WV\Response;

final class ShareController extends Controller
{
    public function index(): Response
    {
        $declaration = $this->declaration();
        return $this->view('pages/shares', [
            'shares' => $this->app->store->shares($declaration['id']),
            'shareUrl' => ($t = $this->app->session->flashed('share_plaintext_token'))
                ? $this->app->url('/gedeeld/' . $t) : null,
        ]);
    }

    public function invite(): Response
    {
        $email = mb_strtolower(trim($this->input('email')));
        $name = trim($this->input('name'));
        $role = $this->input('role');
        $old = ['email' => $email, 'name' => $name, 'role' => $role];
        $errors = [];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors['email'] = 'Vul een geldig e-mailadres in.';
        }
        if (mb_strlen($name) > 120) {
            $errors['name'] = 'De naam mag maximaal 120 tekens bevatten.';
        }
        if (!in_array($role, ['naaste', 'gemachtigde'], true)) {
            $errors['role'] = 'Kies een rol.';
        }
        if ($errors) {
            return $this->fail('/naasten', $errors, $old);
        }

        $declaration = $this->declaration();
        $result = $this->app->store->addShare($declaration['id'], $email, $name !== '' ? $name : null, $role);

        $this->app->session->flash('share_plaintext_token', $result['token']);
        $this->flash('Uitnodiging aangemaakt. Deel de onderstaande link met de ontvanger.');
        return $this->redirect('/naasten');
    }

    public function revoke(string $id): Response
    {
        $declaration = $this->declaration();
        $share = $this->app->store->shareById($id, $declaration['id']) ?? throw new HttpException(404);

        $share['revoked_at'] = time();
        $this->app->store->saveShare($share);

        $this->flash('Toegang ingetrokken.');
        return $this->redirect('/naasten');
    }
}
