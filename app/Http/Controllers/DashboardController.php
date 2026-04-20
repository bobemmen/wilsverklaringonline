<?php

namespace App\Http\Controllers;

use App\Models\Declaration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $declaration = Declaration::with('currentVersion', 'shares')
            ->where('user_id', $user->id)
            ->first();

        if (! $declaration) {
            $declaration = Declaration::create([
                'user_id' => $user->id,
                'is_published' => false,
            ]);
        }

        $viewCount = $declaration->accessLogs()->count();
        $activeShares = $declaration->shares()->whereNull('revoked_at')->count();
        $versionCount = $declaration->versions()->count();

        return view('dashboard', [
            'declaration' => $declaration,
            'viewCount' => $viewCount,
            'activeShares' => $activeShares,
            'versionCount' => $versionCount,
        ]);
    }
}
