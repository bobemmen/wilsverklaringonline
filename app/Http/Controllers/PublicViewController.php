<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\Declaration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicViewController extends Controller
{
    public function show(Request $request, string $token)
    {
        $hash = hash('sha256', $token);
        $declaration = Declaration::with('currentVersion', 'user')
            ->where('access_token', $hash)
            ->first();

        if (! $declaration || ! $declaration->tokenIsActive()) {
            abort(404);
        }

        if (! empty($declaration->notification_email) && ! $request->session()->has('arts_confirmed_' . $declaration->id)) {
            $confirmHash = hash_hmac('sha256', $declaration->id . '|' . $hash, config('app.key'));
            $confirmUrl = url('/v/' . $token . '/bevestig/' . $confirmHash);

            try {
                Mail::raw(
                    "Er is een wilsverklaring bij u opgevraagd.\n\nKlik op deze link om de verklaring te openen:\n" . $confirmUrl,
                    function ($message) use ($declaration) {
                        $message->to($declaration->notification_email)
                            ->subject('Wilsverklaring raadpleging - bevestiging vereist');
                    }
                );
            } catch (\Throwable $e) {
                // Silently ignore mailer errors in dev. In production, log.
            }

            return view('publiek.wachten', [
                'email' => $declaration->notification_email,
            ]);
        }

        if (! $declaration->is_published || ! $declaration->currentVersion) {
            return view('publiek.leeg', [
                'declaration' => $declaration,
            ]);
        }

        AccessLog::create([
            'declaration_id' => $declaration->id,
            'accessor_type' => 'arts',
            'accessor_identifier' => $declaration->notification_email ? hash('sha256', $declaration->notification_email) : null,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'accessed_at' => now(),
        ]);

        $versions = $declaration->versions()->orderBy('created_at')->get();
        $versionNumber = $versions->search(fn($v) => $v->id === $declaration->current_version_id) + 1;

        return view('publiek.leespagina', [
            'declaration' => $declaration,
            'version' => $declaration->currentVersion,
            'content' => $declaration->currentVersion->content,
            'versionNumber' => $versionNumber,
        ]);
    }

    public function confirmEmail(Request $request, string $token, string $hash)
    {
        $tokenHash = hash('sha256', $token);
        $declaration = Declaration::where('access_token', $tokenHash)->first();

        if (! $declaration || ! $declaration->tokenIsActive()) {
            abort(404);
        }

        $expected = hash_hmac('sha256', $declaration->id . '|' . $tokenHash, config('app.key'));

        if (! hash_equals($expected, $hash)) {
            abort(403);
        }

        $request->session()->put('arts_confirmed_' . $declaration->id, true);

        return redirect('/v/' . $token);
    }
}
