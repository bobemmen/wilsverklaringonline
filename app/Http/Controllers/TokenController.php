<?php

namespace App\Http\Controllers;

use App\Models\Declaration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TokenController extends Controller
{
    public function index(Request $request)
    {
        $declaration = Declaration::firstOrCreate([
            'user_id' => Auth::id(),
        ], [
            'is_published' => false,
        ]);

        $plaintext = session()->pull('plaintext_token');

        return view('token.index', [
            'declaration' => $declaration,
            'plaintextToken' => $plaintext,
            'viewCount' => $declaration->accessLogs()->count(),
        ]);
    }

    public function regenerate(Request $request)
    {
        $declaration = Declaration::where('user_id', Auth::id())->firstOrFail();

        $token = bin2hex(random_bytes(32));
        $declaration->access_token = hash('sha256', $token);
        $declaration->save();

        session()->flash('plaintext_token', $token);

        return redirect()->route('token.index')->with('status', 'Nieuwe toegangscode aangemaakt.');
    }

    public function revoke(Request $request)
    {
        $declaration = Declaration::where('user_id', Auth::id())->firstOrFail();
        $declaration->access_token = null;
        $declaration->access_token_expires_at = null;
        $declaration->save();

        return redirect()->route('token.index')->with('status', 'Toegangscode ingetrokken.');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'notification_email' => 'nullable|email',
            'access_token_expires_at' => 'nullable|date|after:today',
        ]);

        $declaration = Declaration::where('user_id', Auth::id())->firstOrFail();
        $declaration->notification_email = $validated['notification_email'] ?? null;
        $declaration->access_token_expires_at = $validated['access_token_expires_at'] ?? null;
        $declaration->save();

        return redirect()->route('token.index')->with('status', 'Instellingen opgeslagen.');
    }
}
