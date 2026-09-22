<?php

use App\Http\Controllers\Cotizador\EnvioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
    Route::get('/cotizador/envio', [EnvioController::class, 'create'])
        ->name('cotizador.envio.create');

    Route::post('/cotizador/envio', [EnvioController::class, 'store'])
        ->name('cotizador.envio.store');
});

require __DIR__.'/settings.php';
