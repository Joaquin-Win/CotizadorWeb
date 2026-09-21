<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');


Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
        Route::get('clients/{client}/portal-empresa', [ClientController::class, 'portalEmpresa'])->name('portal.empresa');
        Route::get('clients/{client}/portal-pedidos', [ClientController::class, 'portalPedidos'])->name('portal.pedidos');
        Route::get('clients/{client}/portal-seguimientos', [ClientController::class, 'portalSeguimientos'])->name('portal.seguimientos');
        Route::get('clients/{client}/portal-documentos', [ClientController::class, 'portalDocumentos'])->name('portal.documentos');
        Route::get('clients/{client}/portal-perfil', [ClientController::class, 'portalPerfil'])->name('portal.perfil');
        Route::get('clients/{client}/portal-mi-cuenta', [ClientController::class, 'portalMiCuenta'])->name('portal.mi-cuenta');
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
