<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('2fa.setup');
        }

        if (! $request->session()->get('2fa.verified', false)) {
            return redirect()->route('2fa.challenge');
        }

        return $next($request);
    }
}
