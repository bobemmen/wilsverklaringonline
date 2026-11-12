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

        $pdf = DeclarationPdf::build(
            $this->user()['name'],
            $version ? fmt_date($version['created_at']) : null,
            $content,
            $this->app->store->tokenIsActive($declaration),
            $this->input('groot') === '1' ? 1.35 : 1.0,
            $this->app->store->verificationPanel($declaration, $version)
        );
        return Response::download($pdf, 'wilsverklaring-' . date('Y-m-d') . '.pdf', 'application/pdf');
    }
}
