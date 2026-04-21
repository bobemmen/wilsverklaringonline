<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $query = User::query()
            ->withCount([
                'declarations as declaration_count',
                'declarations as published_count' => fn ($q) => $q->where('is_published', true),
            ])
            ->with(['declarations' => function ($q) {
                $q->with('currentVersion')->withCount(['versions', 'shares', 'accessLogs']);
            }])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(25)->withQueryString();

        return view('admin.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function show(Request $request, User $user)
    {
        $user->load([
            'declarations.currentVersion',
            'declarations.versions',
            'declarations.shares',
            'declarations.accessLogs' => fn ($q) => $q->latest('accessed_at')->limit(20),
        ]);

        return view('admin.show', [
            'user' => $user,
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:200',
            'confirm_email' => 'required|string',
        ]);

        if ($request->input('confirm_email') !== $user->email) {
            return back()->withErrors([
                'confirm_email' => 'Het e-mailadres komt niet overeen.',
            ])->withInput();
        }

        if ($user->id === Auth::id()) {
            return back()->withErrors([
                'reason' => 'U kunt uw eigen account niet via het admin-dashboard verwijderen.',
            ]);
        }

        Log::channel('daily')->warning('admin.user_deleted', [
            'admin_id' => Auth::id(),
            'admin_email' => Auth::user()->email,
            'deleted_user_id' => $user->id,
            'deleted_user_email' => $user->email,
            'reason' => $request->input('reason'),
            'declaration_count' => $user->declarations()->count(),
            'ip' => $request->ip(),
            'at' => now()->toIso8601String(),
        ]);

        $user->delete();

        return redirect()
            ->route('admin.index')
            ->with('status', 'Account en alle verklaringen zijn verwijderd.');
    }
}
