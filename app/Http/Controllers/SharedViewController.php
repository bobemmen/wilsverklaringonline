<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\DeclarationShare;
use Illuminate\Http\Request;

class SharedViewController extends Controller
{
    public function show(Request $request, string $shareToken)
    {
        $hash = hash('sha256', $shareToken);
        $share = DeclarationShare::with('declaration.currentVersion', 'declaration.user')
            ->where('share_token', $hash)
            ->first();

        if (! $share || $share->revoked_at) {
            abort(404);
        }

        if (is_null($share->accepted_at)) {
            $share->accepted_at = now();
            $share->save();
        }

        $declaration = $share->declaration;

        AccessLog::create([
            'declaration_id' => $declaration->id,
            'accessor_type' => 'naaste',
            'accessor_identifier' => hash('sha256', $share->email),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'accessed_at' => now(),
        ]);

        if (! $declaration->is_published || ! $declaration->currentVersion) {
            return view('publiek.leeg', [
                'declaration' => $declaration,
            ]);
        }

        return view('gedeeld.leespagina', [
            'declaration' => $declaration,
            'share' => $share,
            'version' => $declaration->currentVersion,
            'content' => $declaration->currentVersion->content,
        ]);
    }
}
