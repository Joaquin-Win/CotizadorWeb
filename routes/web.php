<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\InvitacionController;
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

        Route::post('clients/{client}/invitaciones', [InvitacionController::class, 'store'])->name('invitaciones.store');
        Route::delete('clients/{client}/invitaciones/{invitacion}', [InvitacionController::class, 'destroy'])->name('invitaciones.destroy');
        Route::delete('clients/{client}/usuarios/{usuario}', [InvitacionController::class, 'quitarAcceso'])->name('usuarios.quitar-acceso');
        Route::put('clients/{client}/clave', [InvitacionController::class, 'cambiarClave'])->name('usuarios.cambiar-clave');
        Route::put('clients/{client}/empresa', [ClientController::class, 'updateEmpresa'])->name('portal.empresa-update');

        Route::post('clients/{client}/documentos', [DocumentoController::class, 'store'])->name('documentos.store');
        Route::get('clients/{client}/documentos/{documento}', [DocumentoController::class, 'descargar'])->name('documentos.descargar');
        Route::delete('clients/{client}/documentos/{documento}', [DocumentoController::class, 'destroy'])->name('documentos.destroy');
    });

Route::get('invitacion/{token}', [InvitacionController::class, 'aceptar'])->name('invitaciones.aceptar');
Route::post('invitacion/{token}', [InvitacionController::class, 'confirmar'])->name('invitaciones.confirmar');

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
