<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\PublicViewController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\SharedViewController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\WizardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/inloggen', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/inloggen', [AuthenticatedSessionController::class, 'store']);
    Route::get('/registreren', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/registreren', [RegisteredUserController::class, 'store']);
});

Route::post('/uitloggen', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/2fa/instellen', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/instellen', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
    Route::get('/2fa/controle', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
    Route::post('/2fa/controle', [TwoFactorController::class, 'verify'])->name('2fa.verify');
});

Route::middleware(['auth', '2fa'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/verklaring/wizard', [WizardController::class, 'show'])->name('wizard.show');
    Route::get('/verklaring/versies', [WizardController::class, 'versions'])->name('wizard.versions');
    Route::get('/verklaring/pdf', [PdfController::class, 'export'])->name('wizard.pdf');

    Route::get('/token', [TokenController::class, 'index'])->name('token.index');
    Route::post('/token/genereer', [TokenController::class, 'regenerate'])->name('token.regenerate');
    Route::delete('/token', [TokenController::class, 'revoke'])->name('token.revoke');
    Route::post('/token/instellingen', [TokenController::class, 'updateSettings'])->name('token.settings');

    Route::get('/naasten', [ShareController::class, 'index'])->name('naasten.index');
    Route::post('/naasten', [ShareController::class, 'invite'])->name('naasten.invite');
    Route::delete('/naasten/{share}', [ShareController::class, 'revoke'])->name('naasten.revoke');

    Route::get('/account/export', [AccountController::class, 'export'])->name('account.export');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/gebruiker/{user}', [AdminController::class, 'show'])->name('show');
        Route::delete('/gebruiker/{user}', [AdminController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware('throttle:10,1')->group(function () {
    Route::get('/v/{token}', [PublicViewController::class, 'show'])->name('public.show');
    Route::get('/v/{token}/bevestig/{hash}', [PublicViewController::class, 'confirmEmail'])->name('public.confirm');
});

Route::get('/gedeeld/{shareToken}', [SharedViewController::class, 'show'])
    ->middleware('throttle:20,1')
    ->name('shared.show');
