<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function export(Request $request)
    {
        $user = Auth::user()->load('declarations.versions', 'declarations.shares', 'declarations.accessLogs');

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'declarations' => $user->declarations->map(function ($declaration) {
                return [
                    'id' => $declaration->id,
                    'is_published' => $declaration->is_published,
                    'created_at' => $declaration->created_at?->toIso8601String(),
                    'versions' => $declaration->versions->map(function ($v) {
                        return [
                            'id' => $v->id,
                            'created_at' => $v->created_at?->toIso8601String(),
                            'content' => $v->content,
                        ];
                    }),
                    'shares' => $declaration->shares->map(function ($s) {
                        return [
                            'email' => $s->email,
                            'name' => $s->name,
                            'role' => $s->role,
                            'accepted_at' => $s->accepted_at?->toIso8601String(),
                            'revoked_at' => $s->revoked_at?->toIso8601String(),
                        ];
                    }),
                    'access_logs' => $declaration->accessLogs->map(function ($l) {
                        return [
                            'accessor_type' => $l->accessor_type,
                            'accessed_at' => $l->accessed_at?->toIso8601String(),
                            'ip_address' => $l->ip_address,
                        ];
                    }),
                ];
            }),
        ];

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="wilsverklaring-export-' . now()->format('Y-m-d') . '.json"',
        ]);
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Uw account en alle gegevens zijn verwijderd.');
    }
}
