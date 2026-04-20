<?php

namespace App\Http\Controllers;

use App\Models\Declaration;
use App\Models\DeclarationShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareController extends Controller
{
    public function index(Request $request)
    {
        $declaration = Declaration::firstOrCreate([
            'user_id' => Auth::id(),
        ], [
            'is_published' => false,
        ]);

        $shares = $declaration->shares()->orderByDesc('created_at')->get();

        return view('naasten.index', [
            'declaration' => $declaration,
            'shares' => $shares,
        ]);
    }

    public function invite(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:120',
            'role' => 'required|in:naaste,gemachtigde',
        ]);

        $declaration = Declaration::where('user_id', Auth::id())->firstOrFail();

        $token = bin2hex(random_bytes(32));

        DeclarationShare::create([
            'declaration_id' => $declaration->id,
            'email' => $validated['email'],
            'name' => $validated['name'] ?? null,
            'role' => $validated['role'],
            'share_token' => hash('sha256', $token),
        ]);

        session()->flash('share_plaintext_token', $token);
        session()->flash('status', 'Uitnodiging aangemaakt. Deel de onderstaande link met de ontvanger.');

        return redirect()->route('naasten.index');
    }

    public function revoke(Request $request, DeclarationShare $share)
    {
        abort_unless($share->declaration->user_id === Auth::id(), 403);

        $share->revoked_at = now();
        $share->save();

        return redirect()->route('naasten.index')->with('status', 'Toegang ingetrokken.');
    }
}
