<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        $store = $this->app->store;
        $declaration = $this->declaration();

        return $this->view('pages/dashboard', [
            'declaration' => $declaration,
            'currentVersion' => $store->version($declaration['current_version_id'], $declaration['id']),
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
