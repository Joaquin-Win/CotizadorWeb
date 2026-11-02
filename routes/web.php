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
use App\Http\Controllers\Admin\ClientesExcelController;
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
    Route::get('/',       [CotizacionController::class, 'index'])->name('index');
    Route::post('calcular', [CotizacionController::class, 'calcular'])
        ->name('calcular')
        ->middleware('throttle:30,1'); // MED-05: max 30 cálculos/min por IP
    Route::post('guardar',  [CotizacionController::class, 'guardar'])
        ->name('guardar')
        ->middleware('throttle:10,1'); // MED-06: max 10 guardados/min por IP (crea en BD + email + WA)
    Route::get('{codigo}',  [CotizacionController::class, 'resultado'])->name('resultado');
});

// Invitaciones (token público para aceptar invitación de acceso)
Route::get('invitacion/{token}',  [InvitacionController::class, 'aceptar'])->name('invitaciones.aceptar');
Route::post('invitacion/{token}', [InvitacionController::class, 'confirmar'])->name('invitaciones.confirmar');


// ===========================================================
// ÁREA AUTENTICADA
// ===========================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('dashboard', DashboardController::class)->name('dashboard')->middleware(['redirige.a.portal']);

    // Gestión de usuarios (admin): empresas + accesos + ver plataforma.
    Route::get('usuarios', [ClienteController::class, 'usuarios'])->name('usuarios.index')->middleware('can:esAdmin');

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

        // Administración de empresas - solo administradores
        Route::middleware('can:esAdmin')->group(function () {
            Route::get('/',                                  [ClienteController::class, 'index'])->name('index');
            Route::post('/',                                 [ClienteController::class, 'store'])->name('store');
            Route::get('{cliente}',                          [ClienteController::class, 'show'])->name('show');
            Route::put('{cliente}',                          [ClienteController::class, 'update'])->name('update');
            Route::put('{cliente}/inactivar',                 [ClienteController::class, 'setInactive'])->name('inactivar');
            Route::put('{cliente}/activar',                   [ClienteController::class, 'setActive'])->name('activar');

            // Excel de clientes
            Route::get('excel/exportar',                     [ClientesExcelController::class, 'exportar'])->name('excel.exportar');
            Route::post('excel/importar',                    [ClientesExcelController::class, 'importar'])->name('excel.importar');
        });

        // Portal del cliente - admin o el propio cliente (redirige.a.portal)
        Route::prefix('{cliente}/portal')->name('portal.')->middleware('redirige.a.portal')->group(function () {
            Route::get('resumen',     [ClienteController::class, 'portalResumen'])->name('resumen');
            Route::get('pedidos',     [ClienteController::class, 'portalPedidos'])->name('pedidos');
            Route::get('documentos',  [ClienteController::class, 'portalDocumentos'])->name('documentos');
            Route::get('perfil',      [ClienteController::class, 'portalPerfil'])->name('perfil');
            Route::get('cotizador',   [ClienteController::class, 'portalCotizador'])->name('cotizador');
            Route::get('mi-cuenta',   [ClienteController::class, 'portalMiCuenta'])->name('mi-cuenta');
            Route::put('empresa',     [ClienteController::class, 'updateEmpresa'])->name('empresa-update');
        });

        // Invitaciones de acceso a portal por cliente
        Route::post('{cliente}/invitaciones',                       [InvitacionController::class, 'store'])->name('invitaciones.store')->middleware('redirige.a.portal');
        Route::delete('{cliente}/invitaciones/{invitacion}',        [InvitacionController::class, 'destroy'])->name('invitaciones.destroy')->middleware('redirige.a.portal');
        Route::delete('{cliente}/usuarios/{usuario}',               [InvitacionController::class, 'quitarAcceso'])->name('usuarios.quitar-acceso')->middleware('redirige.a.portal');
        Route::put('{cliente}/clave',                               [InvitacionController::class, 'cambiarClave'])->name('usuarios.cambiar-clave')->middleware('redirige.a.portal');

        // Documentos
        Route::post('{cliente}/documentos',                         [DocumentoController::class, 'store'])->name('documentos.store')->middleware('redirige.a.portal');
        Route::get('{cliente}/documentos/{documento}/descargar',    [DocumentoController::class, 'descargar'])->name('documentos.descargar')->middleware('redirige.a.portal');
        Route::delete('{cliente}/documentos/{documento}',           [DocumentoController::class, 'destroy'])->name('documentos.destroy')->middleware('redirige.a.portal');
    });

    // -------------------------------------------------------
    // API de notificaciones admin (sesión + rol ADMIN)
    // -------------------------------------------------------
    Route::prefix('api/admin/notifications')->name('api.admin.notifications.')->middleware('can:esAdmin')->group(function () {
        Route::get('/',            [\App\Http\Controllers\Admin\NotificacionesController::class, 'index'])->name('index');
        Route::post('read-all',    [\App\Http\Controllers\Admin\NotificacionesController::class, 'readAll'])->name('read-all');
        Route::post('{id}/read',   [\App\Http\Controllers\Admin\NotificacionesController::class, 'read'])->name('read');
    });

    // -------------------------------------------------------
    // Panel Admin SET (solo rol ADMIN)
    // -------------------------------------------------------
    Route::prefix('admin')->name('admin.')->middleware('can:esAdmin')->group(function () {

        // Cotizaciones administrativas (solo admin)
        Route::get('cotizaciones',                         [CotizacionesAdminController::class, 'index'])->name('cotizaciones.index');
        Route::post('cotizaciones/{cotizacion}/confirmar', [CotizacionesAdminController::class, 'confirmar'])->name('cotizaciones.confirmar');
        Route::post('cotizaciones/{cotizacion}/whatsapp',         [CotizacionesAdminController::class, 'contactarWhatsapp'])->name('cotizaciones.whatsapp');
        Route::get('cotizaciones/{cotizacion}/whatsapp/preview',   [CotizacionesAdminController::class, 'previewWhatsapp'])->name('cotizaciones.whatsapp.preview');

        // Configuración global (mail, SMTP, WhatsApp, API Key de mapas)
        Route::get('configuracion', [\App\Http\Controllers\Admin\AjustesSistemaController::class, 'index'])->name('configuracion');
        Route::put('configuracion', [\App\Http\Controllers\Admin\AjustesSistemaController::class, 'update'])->name('configuracion.update');
        Route::post('configuracion/probar/{servicio}', [\App\Http\Controllers\Admin\AjustesPruebaController::class, 'probar'])
            ->whereIn('servicio', ['smtp', 'whatsapp', 'contacto'])
            ->name('configuracion.probar');

        // Notificaciones (vista)
        Route::get('notificaciones', [\App\Http\Controllers\Admin\NotificacionesController::class, 'vista'])->name('notificaciones');

        // Usuarios internos SET
        Route::resource('usuarios', UsuarioController::class)->except(['show']);
        Route::put('usuarios/{usuario}/margenes', [UsuarioController::class, 'updateMargenes'])->name('admin.usuarios.margenes');
        Route::post('usuarios/{usuario}/toggle-activo', [UsuarioController::class, 'toggleActivo'])->name('admin.usuarios.toggle');
        Route::put('usuarios/{usuario}/estado', [UsuarioController::class, 'setEstado'])->name('admin.usuarios.estado');
        Route::put('usuarios/{usuario}/descuento', [UsuarioController::class, 'updateDescuento'])->name('admin.usuarios.descuento');

        // Tarifas
        Route::resource('tarifas', TarifaController::class);

        // Configuración Cotizador (Precios, Tarifas, Provincias y Localidades activas)
        Route::get('cotizador/configuracion',                                   [CotizadorConfiguracionController::class, 'index'])->name('cotizador.config');
        Route::post('cotizador/precios',                                        [CotizadorConfiguracionController::class, 'updatePrecios'])->name('cotizador.precios.update');
        Route::post('cotizador/costos-adicionales',                             [CotizadorConfiguracionController::class, 'storeCostoAdicional'])->name('cotizador.costos.store');
        Route::delete('cotizador/costos-adicionales/{costo}',                   [CotizadorConfiguracionController::class, 'destroyCostoAdicional'])->name('cotizador.costos.destroy');
        Route::post('cotizador/tarifas',                                        [CotizadorConfiguracionController::class, 'storeTarifa'])->name('cotizador.tarifas.store');
        Route::put('cotizador/tarifas/{tarifa}',                                [CotizadorConfiguracionController::class, 'updateTarifa'])->name('cotizador.tarifas.update');
        Route::get('cotizador/tarifas/exportar',                                [CotizadorConfiguracionController::class, 'exportarTarifas'])->name('cotizador.tarifas.exportar');
        Route::get('cotizador/tarifas/plantilla',                               [CotizadorConfiguracionController::class, 'plantillaTarifas'])->name('cotizador.tarifas.plantilla');
        Route::post('cotizador/tarifas/importar',                               [CotizadorConfiguracionController::class, 'importarTarifas'])->name('cotizador.tarifas.importar');
        Route::delete('cotizador/tarifas/{tarifa}',                             [CotizadorConfiguracionController::class, 'destroyTarifa'])->name('cotizador.tarifas.destroy');
        Route::post('cotizador/provincias/{provincia}/toggle',                  [CotizadorConfiguracionController::class, 'toggleProvincia'])->name('cotizador.provincias.toggle');
        Route::get('cotizador/provincias/{provincia}/localidades',              [CotizadorConfiguracionController::class, 'localidadesProvincia'])->name('cotizador.provincias.localidades');
        Route::post('cotizador/provincias/{provincia}/toggle-localidades',       [CotizadorConfiguracionController::class, 'toggleTodasLocalidades'])->name('cotizador.provincias.toggle-todas');
        Route::post('cotizador/localidades/{localidad}/toggle',                  [CotizadorConfiguracionController::class, 'toggleLocalidad'])->name('cotizador.localidades.toggle');
        Route::post('cotizador/localidades/{localidad}/deposito',                [CotizadorConfiguracionController::class, 'toggleDeposito'])->name('cotizador.localidades.deposito');

        // Márgenes de ganancia
        Route::resource('margenes', MargenGananciaController::class)->except(['show']);

        // Config Transoft
        Route::get('transoft/configuracion',          [TransoftConfiguracionController::class, 'edit'])->name('transoft.config');
        Route::post('transoft/configuracion',         [TransoftConfiguracionController::class, 'update'])->name('transoft.config.update');
        Route::post('transoft/test-conexion',         [TransoftConfiguracionController::class, 'testConexion'])->name('transoft.test');
    });

});

require __DIR__ . '/settings.php';
