<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\HttpException;
use WV\Response;

/** Alleen-lezen weergave voor de arts, via een geheime link (/v/{token}). */
final class PublicController extends Controller
{
    private const CONFIRM_LINK_TTL = 3600;      // e-mailbevestigingslink: 1 uur geldig
    private const CONFIRMED_SESSION_TTL = 28800; // daarna 8 uur toegang in deze browser

    public function show(string $token): Response
    {
        $store = $this->app->store;
        $declaration = $store->declarationByToken($token);
        if (!$declaration || !$store->tokenIsActive($declaration)) {
            throw new HttpException(404);
        }

        if (!empty($declaration['notification_email']) && !$this->isConfirmed($declaration['id'])) {
            // maximaal één bevestigingsmail per 5 minuten, zodat de link niet als spam-trigger te misbruiken is
            if ($this->app->limiter->attempt('arts-mail|' . $declaration['id'], 1, 300)) {
                $this->sendConfirmation($declaration, $token);
            }
            return $this->view('public/wait', ['email' => $this->mask($declaration['notification_email'])]);
        }

        $version = $store->version($declaration['current_version_id'], $declaration['id']);
        if (!$declaration['is_published'] || !$version) {
            return $this->view('public/empty');
        }

        $store->addLog(
            $declaration['id'],
            'arts',
            $declaration['notification_email'] ? $this->app->crypto->index('accessor', $declaration['notification_email']) : null,
            $this->app->request->ip(),
            $this->app->request->userAgent()
        );

        $versions = $store->versions($declaration['id']);
        $number = 1;
        foreach ($versions as $i => $v) {
            if ($v['id'] === $version['id']) {
                $number = $i + 1;
            }
        }

        return $this->view('public/read', [
            'owner' => $store->userById($declaration['user_id']),
            'version' => $version,
            'doc' => \WV\Content::normalize($version['content']),
            'versionNumber' => $number,
        ]);
    }

    public function confirm(string $token, string $proof): Response
    {
        $store = $this->app->store;
        $declaration = $store->declarationByToken($token);
        if (!$declaration || !$store->tokenIsActive($declaration)) {
            throw new HttpException(404);
        }
        if (!preg_match('/^(\d{10})-([a-f0-9]{64})$/', $proof, $m) || (int) $m[1] < time()) {
            throw new HttpException(403);
        }
        if (!hash_equals($this->proof($declaration, (int) $m[1]), $m[2])) {
            throw new HttpException(403);
        }

        $confirmed = $this->app->session->get('arts_confirmed', []);
        $confirmed[$declaration['id']] = time();
        $this->app->session->put('arts_confirmed', $confirmed);

        return $this->redirect('/v/' . $token);
    }

    // ---------------------------------------------------------------- helpers

    private function isConfirmed(string $declarationId): bool
    {
        $confirmed = $this->app->session->get('arts_confirmed', []);
        return is_array($confirmed)
            && isset($confirmed[$declarationId])
            && $confirmed[$declarationId] > time() - self::CONFIRMED_SESSION_TTL;
    }

    /** Handtekening gebonden aan de verklaring, de huidige code en de vervaltijd. */
    private function proof(array $declaration, int $expires): string
    {
        return $this->app->crypto->mac('arts-confirm', $declaration['id'] . '|' . $declaration['token_idx'] . '|' . $expires);
    }

    private function sendConfirmation(array $declaration, string $token): void
    {
        $expires = time() + self::CONFIRM_LINK_TTL;
        $url = $this->app->url('/v/' . $token . '/bevestig/' . $expires . '-' . $this->proof($declaration, $expires));

        $this->app->mailer->send(
            $declaration['notification_email'],
            'Wilsverklaring raadpleging - bevestiging vereist',
            "Er is een wilsverklaring bij u opgevraagd.\n\n"
            . "Klik op deze link om de verklaring te openen (de link is een uur geldig):\n" . $url . "\n\n"
            . "Heeft u dit niet zelf opgevraagd? Dan kunt u deze e-mail negeren."
        );
    }

    private function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        return mb_substr($local, 0, 1) . '***@' . $domain;
    }
}
