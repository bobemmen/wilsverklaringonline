<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Content;
use WV\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        $store = $this->app->store;
        $declaration = $this->declaration();
        $currentVersion = $store->version($declaration['current_version_id'], $declaration['id']);
        $data = is_array($declaration['draft'])
            ? Content::normalize($declaration['draft'])
            : ($currentVersion ? Content::normalize($currentVersion['content']) : Content::defaults());

        return $this->view('pages/dashboard', [
            'declaration' => $declaration,
            'currentVersion' => $currentVersion,
            'progress' => Content::progress($data),
            'completion' => Content::completion($data),
            'hasDraft' => is_array($declaration['draft']),
            'tokenActive' => $store->tokenIsActive($declaration),
            'viewCount' => $store->logCount($declaration['id']),
            'activeShares' => $store->activeShareCount($declaration['id']),
            'versionCount' => $store->versionCount($declaration['id']),
            'verifications' => array_values(array_filter(
                $store->verificationsForPatient($this->user()['id']),
                fn($v) => $v['status'] !== 'ingetrokken'
            )),
        ]);
    }
}
