<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function setup(Request $request)
    {
        $user = Auth::user();

        if (! $user->two_factor_secret) {
            $user->two_factor_secret = Totp::generateSecret();
            $user->save();
        }

        $otpauth = Totp::otpauthUrl(
            $user->email,
            $user->two_factor_secret,
            config('app.name', 'WilsVerklaring')
        );

        return view('auth.two-factor-setup', [
            'secret' => $user->two_factor_secret,
            'otpauth' => $otpauth,
            'confirmed' => $user->hasTwoFactorEnabled(),
        ]);
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();

        if (! $user->two_factor_secret) {
            return redirect()->route('2fa.setup');
        }

        if (! Totp::verify($user->two_factor_secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Onjuiste code. Probeer het opnieuw.',
            ]);
        }

        $user->two_factor_confirmed_at = now();
        $user->save();

        $request->session()->put('2fa.verified', true);
        $request->session()->forget('2fa.required');

        return redirect()->route('dashboard')->with('status', 'Tweestapsverificatie is ingesteld.');
    }

    public function challenge(Request $request)
    {
        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();

        if (! $user || ! $user->two_factor_secret) {
            return redirect()->route('login');
        }

        if (! Totp::verify($user->two_factor_secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Onjuiste code. Probeer het opnieuw.',
            ]);
        }

        $request->session()->put('2fa.verified', true);
        $request->session()->forget('2fa.required');

        return redirect()->intended(route('dashboard'));
    }
}
