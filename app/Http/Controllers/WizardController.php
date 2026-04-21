<?php

namespace App\Http\Controllers;

use App\Models\Declaration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WizardController extends Controller
{
    public function show(Request $request)
    {
        $declaration = Declaration::firstOrCreate([
            'user_id' => Auth::id(),
        ], [
            'is_published' => false,
        ]);

        return view('verklaring.wizard', [
            'declaration' => $declaration,
        ]);
    }

    public function versions(Request $request)
    {
        $declaration = Declaration::with('versions')
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('verklaring.versies', [
            'declaration' => $declaration,
            'versions' => $declaration->versions()->orderByDesc('created_at')->get(),
        ]);
    }
}
