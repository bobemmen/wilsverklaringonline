<?php

declare(strict_types=1);

namespace WV\Controllers;

use WV\Content;
use WV\DeclarationPdf;
use WV\Response;

final class PdfController extends Controller
{
    public function export(): Response
    {
        $declaration = $this->declaration();
        $version = $this->app->store->version($declaration['current_version_id'], $declaration['id']);
        $content = Content::normalize($version['content'] ?? []);

        $number = null;
        if ($version) {
            foreach ($this->app->store->versions($declaration['id']) as $i => $v) {
                if ($v['id'] === $version['id']) {
                    $number = $i + 1;
                }
            }
        }

        $tokenActive = $this->app->store->tokenIsActive($declaration);
        $link = $tokenActive && !empty($declaration['access_token']) ? $this->app->url('/v/' . $declaration['access_token']) : null;

        $pdf = DeclarationPdf::build(
            $this->user()['name'],
            $version ? fmt_date($version['created_at']) : null,
            $content,
            $tokenActive,
            $this->input('groot') === '1' ? 1.35 : 1.0,
            $this->app->store->verificationPanel($declaration),
            $number,
            $link
        );
        return Response::download($pdf, 'wilsverklaring-' . date('Y-m-d') . '.pdf', 'application/pdf');
    }
}
