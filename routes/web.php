<?php

use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\GeografiaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Cotizador\CotizacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\CotizacionesAdminController;
use App\Http\Controllers\Admin\CotizadorConfiguracionController;
use App\Http\Controllers\Admin\MargenGananciaController;
use App\Http\Controllers\Admin\TarifaController;
use App\Http\Controllers\Admin\TransoftConfiguracionController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Portal\ClientePortalController;
use App\Http\Controllers\Portal\DocumentoController;
use App\Http\Controllers\Portal\InvitacionController;
use Illuminate\Support\Facades\Route;

// ===========================================================
// PÚBLICO — sin autenticación
// ===========================================================
Route::inertia('/', 'welcome')->name('home');

// API pública para selects del cotizador
Route::prefix('api/publica')->name('api.')->group(function () {
    Route::get('provincias',                    [GeografiaController::class, 'provincias'])->name('provincias');
    Route::get('provincias/{id}/localidades',   [GeografiaController::class, 'localidades'])->name('localidades');
    Route::get('tipos-bulto',                   [CatalogoController::class, 'tiposBulto'])->name('tipos-bulto');
    Route::get('tipos-servicio',                [CatalogoController::class, 'tiposServicio'])->name('tipos-servicio');
});

// Cotizador público (no requiere login)
Route::prefix('cotizador')->name('cotizador.')->group(function () {
    Route::get('/',                                  [CotizacionController::class, 'index'])->name('index');
    Route::post('calcular',                          [CotizacionController::class, 'calcular'])->name('calcular');
    Route::post('guardar',                           [CotizacionController::class, 'guardar'])->name('guardar');
    Route::get('{codigo}',                           [CotizacionController::class, 'resultado'])->name('resultado');
});

// Invitaciones (token público para aceptar invitación de acceso)
Route::get('invitacion/{token}',  [InvitacionController::class, 'aceptar'])->name('invitaciones.aceptar');
Route::post('invitacion/{token}', [InvitacionController::class, 'confirmar'])->name('invitaciones.confirmar');


// ===========================================================
// ÁREA AUTENTICADA
// ===========================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Gestión de usuarios (admin): empresas + accesos + ver plataforma.
    Route::get('usuarios', [ClienteController::class, 'usuarios'])->name('usuarios.index');

    // -------------------------------------------------------
    // Mis cotizaciones (panel de cliente logueado)
    // -------------------------------------------------------
    Route::prefix('mis-cotizaciones')->name('cotizaciones.')->group(function () {
        Route::get('/',            [CotizacionController::class, 'misCotizaciones'])->name('index');
        Route::get('{codigo}',     [CotizacionController::class, 'show'])->name('show');
    });

    // -------------------------------------------------------
    // Gestión de Clientes (backoffice SET)
    // -------------------------------------------------------
    Route::prefix('clientes')->name('clientes.')->group(function () {
        Route::get('/',                                  [ClienteController::class, 'index'])->name('index');
        Route::post('/',                                 [ClienteController::class, 'store'])->name('store');
        Route::get('{cliente}',                          [ClienteController::class, 'show'])->name('show');
        Route::put('{cliente}',                          [ClienteController::class, 'update'])->name('update');
        Route::put('{cliente}/inactivar',                 [ClienteController::class, 'setInactive'])->name('inactivar');
        Route::put('{cliente}/activar',                   [ClienteController::class, 'setActive'])->name('activar');

        // Portal del cliente (visto desde backoffice)
        Route::prefix('{cliente}/portal')->name('portal.')->group(function () {
            Route::get('resumen',     [ClienteController::class, 'portalResumen'])->name('resumen');
            Route::get('pedidos',     [ClienteController::class, 'portalPedidos'])->name('pedidos');
            Route::get('documentos',  [ClienteController::class, 'portalDocumentos'])->name('documentos');
            Route::get('perfil',      [ClienteController::class, 'portalPerfil'])->name('perfil');
            Route::get('cotizador',   [ClienteController::class, 'portalCotizador'])->name('cotizador');
            Route::get('mi-cuenta',   [ClienteController::class, 'portalMiCuenta'])->name('mi-cuenta');
            Route::put('empresa',     [ClienteController::class, 'updateEmpresa'])->name('empresa-update');
        });

        // Invitaciones de acceso a portal por cliente
        Route::post('{cliente}/invitaciones',                       [InvitacionController::class, 'store'])->name('invitaciones.store');
        Route::delete('{cliente}/invitaciones/{invitacion}',        [InvitacionController::class, 'destroy'])->name('invitaciones.destroy');
        Route::delete('{cliente}/usuarios/{usuario}',               [InvitacionController::class, 'quitarAcceso'])->name('usuarios.quitar-acceso');
        Route::put('{cliente}/clave',                               [InvitacionController::class, 'cambiarClave'])->name('usuarios.cambiar-clave');

        // Documentos
        Route::post('{cliente}/documentos',                         [DocumentoController::class, 'store'])->name('documentos.store');
        Route::get('{cliente}/documentos/{documento}/descargar',    [DocumentoController::class, 'descargar'])->name('documentos.descargar');
        Route::delete('{cliente}/documentos/{documento}',           [DocumentoController::class, 'destroy'])->name('documentos.destroy');
    });

    // -------------------------------------------------------
    // Panel Admin SET (solo rol ADMIN)
    // -------------------------------------------------------
    Route::prefix('admin')->name('admin.')->middleware('can:esAdmin')->group(function () {

        // Cotizaciones administrativas (solo admin)
        Route::get('cotizaciones',                         [CotizacionesAdminController::class, 'index'])->name('cotizaciones.index');
        Route::post('cotizaciones/{cotizacion}/confirmar', [CotizacionesAdminController::class, 'confirmar'])->name('cotizaciones.confirmar');

        // Usuarios internos SET
        Route::resource('usuarios', UsuarioController::class)->except(['show']);
        Route::put('usuarios/{usuario}/margenes', [UsuarioController::class, 'updateMargenes'])->name('admin.usuarios.margenes');
        Route::post('usuarios/{usuario}/toggle-activo', [UsuarioController::class, 'toggleActivo'])->name('admin.usuarios.toggle');

        // Tarifas
        Route::resource('tarifas', TarifaController::class);

        // Configuración Cotizador (Precios, Tarifas, Provincias y Localidades activas)
        Route::get('cotizador/configuracion',                                   [CotizadorConfiguracionController::class, 'index'])->name('cotizador.config');
        Route::post('cotizador/precios',                                        [CotizadorConfiguracionController::class, 'updatePrecios'])->name('cotizador.precios.update');
        Route::post('cotizador/costos-adicionales',                             [CotizadorConfiguracionController::class, 'storeCostoAdicional'])->name('cotizador.costos.store');
        Route::delete('cotizador/costos-adicionales/{costo}',                   [CotizadorConfiguracionController::class, 'destroyCostoAdicional'])->name('cotizador.costos.destroy');
        Route::post('cotizador/tarifas',                                        [CotizadorConfiguracionController::class, 'storeTarifa'])->name('cotizador.tarifas.store');
        Route::put('cotizador/tarifas/{tarifa}',                                [CotizadorConfiguracionController::class, 'updateTarifa'])->name('cotizador.tarifas.update');
        Route::delete('cotizador/tarifas/{tarifa}',                             [CotizadorConfiguracionController::class, 'destroyTarifa'])->name('cotizador.tarifas.destroy');
        Route::post('cotizador/provincias/{provincia}/toggle',                  [CotizadorConfiguracionController::class, 'toggleProvincia'])->name('cotizador.provincias.toggle');
        Route::get('cotizador/provincias/{provincia}/localidades',              [CotizadorConfiguracionController::class, 'localidadesProvincia'])->name('cotizador.provincias.localidades');
        Route::post('cotizador/provincias/{provincia}/toggle-localidades',       [CotizadorConfiguracionController::class, 'toggleTodasLocalidades'])->name('cotizador.provincias.toggle-todas');
        Route::post('cotizador/localidades/{localidad}/toggle',                  [CotizadorConfiguracionController::class, 'toggleLocalidad'])->name('cotizador.localidades.toggle');

        // Márgenes de ganancia
        Route::resource('margenes', MargenGananciaController::class)->except(['show']);

        // Config Transoft
        Route::get('transoft/configuracion',          [TransoftConfiguracionController::class, 'edit'])->name('transoft.config');
        Route::post('transoft/configuracion',         [TransoftConfiguracionController::class, 'update'])->name('transoft.config.update');
        Route::post('transoft/test-conexion',         [TransoftConfiguracionController::class, 'testConexion'])->name('transoft.test');
    });

});

require __DIR__ . '/settings.php';
