<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Content;
use WV\HttpException;
use WV\Response;

/** Alleen-lezen weergave voor een uitgenodigde naaste of gemachtigde (/gedeeld/{token}). */
final class SharedController extends Controller
{
    public function show(string $token): Response
    {
        $store = $this->app->store;
        $share = $store->shareByToken($token);
        if (!$share || !empty($share['revoked_at'])) {
            throw new HttpException(404);
        }
        $declaration = $store->declarationById($share['declaration_id']) ?? throw new HttpException(404);

        if (empty($share['accepted_at'])) {
            $share['accepted_at'] = time();
            $store->saveShare($share);
        }

        $store->addLog(
            $declaration['id'],
            'naaste',
            $this->app->crypto->index('accessor', $share['email']),
            $this->app->request->ip(),
            $this->app->request->userAgent()
        );

        $version = $store->version($declaration['current_version_id'], $declaration['id']);
        if (!$declaration['is_published'] || !$version) {
            return $this->view('public/empty');
        }

        return $this->view('public/shared', [
            'owner' => $store->userById($declaration['user_id']),
            'share' => $share,
            'version' => $version,
            'doc' => Content::normalize($version['content']),
        ]);
    }
}
