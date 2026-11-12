<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\HttpException;
use WV\Legal;
use WV\Response;

/** Pilotinformatie, privacyverklaring en gebruiksvoorwaarden: openbaar, ook zonder inloggen. */
final class LegalController extends Controller
{
    public function pilot(): Response { return $this->show('pilot'); }
    public function privacy(): Response { return $this->show('privacy'); }
    public function voorwaarden(): Response { return $this->show('voorwaarden'); }

    private function show(string $page): Response
    {
        $doc = Legal::render($this->app->root, $page);
        if ($doc === null) {
            throw new HttpException(404);
        }
        return $this->view('pages/legal', $doc + ['pages' => Legal::PAGES, 'current' => $page]);
    }
}
