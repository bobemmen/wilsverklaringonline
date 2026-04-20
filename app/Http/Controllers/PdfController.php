<?php

namespace App\Http\Controllers;

use App\Models\Declaration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PdfController extends Controller
{
    public function export(Request $request)
    {
        $declaration = Declaration::with('currentVersion', 'user')
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $version = $declaration->currentVersion;
        $content = $version ? $version->content : [];

        $publicUrl = null;
        if ($declaration->tokenIsActive()) {
            $publicUrl = route('public.show', ['token' => '[BEWAAR-LINK-GOED]']);
        }

        $pdf = Pdf::loadView('pdf.wilsverklaring', [
            'declaration' => $declaration,
            'user' => $declaration->user,
            'version' => $version,
            'content' => $content,
            'publicUrl' => $publicUrl,
        ]);

        return $pdf->download('wilsverklaring-' . now()->format('Y-m-d') . '.pdf');
    }
}
