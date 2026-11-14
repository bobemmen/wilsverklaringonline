<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Content;
use WV\DeclarationPdf;
use WV\HttpException;
use WV\Response;

final class PdfController extends Controller
{
    public function export(): Response
    {
        $declaration = $this->declaration();
        $version = $this->app->store->version($declaration['current_version_id'], $declaration['id']);
        $number = null;
        if ($version) {
            foreach ($this->app->store->versions($declaration['id']) as $i => $v) {
                if ($v['id'] === $version['id']) {
                    $number = $i + 1;
                }
            }
        }
        return $this->respond($declaration, $version, $number, true, 'wilsverklaring-' . date('Y-m-d') . '.pdf');
    }

    /** PDF van de huidige versie via het versienummer. Oudere versies zijn alleen te lezen, niet te downloaden. */
    public function version(string $n): Response
    {
        $declaration = $this->declaration();
        $versions = $this->app->store->versions($declaration['id']);
        $number = (int) $n;
        if ($number < 1 || $number > count($versions)) {
            throw new HttpException(404);
        }
        $version = $versions[$number - 1];
        if ($version['id'] !== $declaration['current_version_id']) {
            throw new HttpException(404);
        }
        return $this->respond($declaration, $version, $number, true, 'wilsverklaring-versie-' . $number . '.pdf');
    }

    private function respond(array $declaration, ?array $version, ?int $number, bool $current, string $filename): Response
    {
        $content = Content::normalize($version['content'] ?? []);
        $tokenActive = $current && $this->app->store->tokenIsActive($declaration);
        $link = $tokenActive && !empty($declaration['access_token']) ? $this->app->url('/v/' . $declaration['access_token']) : null;

        $pdf = DeclarationPdf::build(
            $this->user()['name'],
            $version ? fmt_date($version['created_at']) : null,
            $content,
            $tokenActive,
            $this->input('groot') === '1' ? 1.35 : 1.0,
            $current ? $this->app->store->verificationPanel($declaration) : null,
            $number,
            $link
        );
        return Response::download($pdf, $filename, 'application/pdf');
    }
}
