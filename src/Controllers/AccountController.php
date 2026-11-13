<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Response;

final class AccountController extends Controller
{
    public function index(): Response
    {
        return $this->view('pages/account');
    }

    /** Alle gegevens van de gebruiker als JSON (recht op inzage/overdraagbaarheid). */
    public function export(): Response
    {
        $store = $this->app->store;
        $user = $this->user();
        $declaration = $this->declaration();

        $payload = [
            'exported_at' => date('c'),
            'user' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'created_at' => date('c', $user['created_at']),
            ],
            'declaration' => [
                'is_published' => $declaration['is_published'],
                'created_at' => date('c', $declaration['created_at']),
                'draft' => $declaration['draft'],
                'notification_email' => $declaration['notification_email'],
                'access_token_expires_at' => $declaration['token_expires_at'] ? date('c', $declaration['token_expires_at']) : null,
                'versions' => array_map(fn($v) => [
                    'id' => $v['id'],
                    'created_at' => date('c', $v['created_at']),
                    'content' => $v['content'],
                ], $store->versions($declaration['id'])),
                'shares' => array_map(fn($s) => [
                    'email' => $s['email'],
                    'name' => $s['name'],
                    'role' => $s['role'],
                    'accepted_at' => $s['accepted_at'] ? date('c', $s['accepted_at']) : null,
                    'revoked_at' => $s['revoked_at'] ? date('c', $s['revoked_at']) : null,
                ], $store->shares($declaration['id'])),
                'access_logs' => array_map(fn($l) => [
                    'accessor_type' => $l['accessor_type'],
                    'accessed_at' => date('c', $l['accessed_at']),
                    'ip_address' => $l['ip_address'],
                ], $store->logs($declaration['id'])),
            ],
        ];

        $response = Response::json($payload);
        $response->headers['Content-Disposition'] = 'attachment; filename="wilsverklaring-export-' . date('Y-m-d') . '.json"';
        return $response;
    }

    /** Verwijdert het account en alle gegevens definitief; vraagt het wachtwoord ter bevestiging. */
    public function destroy(): Response
    {
        $user = $this->user();
        $key = 'delete:' . $user['id'];
        if ($this->app->limiter->tooMany($key, 5)) {
            return $this->fail('/account', ['password' => 'Te veel pogingen. Probeer het later opnieuw.']);
        }
        if (!password_verify($this->input('password'), $user['password'])) {
            $this->app->limiter->hit($key, 900);
            return $this->fail('/account', ['password' => 'Het wachtwoord klopt niet.']);
        }

        $this->app->store->deleteUser($user['id']);
        $this->app->auth->logout();
        $this->flash('Uw account en alle gegevens zijn verwijderd.');
        return $this->redirect('/');
    }
}
