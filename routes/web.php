<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Cotizador\CotizacionController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------
// Página principal / cotizador público (sin auth)
// ---------------------------------------------------------------
Route::inertia('/', 'welcome')->name('home');

// ---------------------------------------------------------------
// Cotizador público
// ---------------------------------------------------------------
Route::get('/cotizador', [CotizacionController::class, 'index'])->name('cotizador.index');

// API pública del cotizador
Route::prefix('api')->group(function () {
    Route::post('cotizador/calcular', [CotizacionController::class, 'calcular'])->name('cotizador.calcular');
    Route::post('cotizaciones',        [CotizacionController::class, 'guardar'])->name('cotizaciones.guardar');

    // Datos para selects del wizard
    Route::get('provincias',                          [\App\Http\Controllers\Api\GeografiaController::class, 'provincias']);
    Route::get('provincias/{id}/localidades',         [\App\Http\Controllers\Api\GeografiaController::class, 'localidades']);
    Route::get('tipos-bulto',                         [\App\Http\Controllers\Api\CatalogoController::class, 'tiposBulto']);
});

// ---------------------------------------------------------------
// Área autenticada
// ---------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Cotizador autenticado (sobreescribe cálculo con contexto de cliente)
    Route::prefix('api/auth')->group(function () {
        Route::post('cotizador/calcular', [CotizacionController::class, 'calcular'])->name('auth.cotizador.calcular');
    });

    Route::get('mis-cotizaciones', [CotizacionController::class, 'misCotizaciones'])->name('cotizaciones.index');
    Route::get('cotizaciones/{codigo}', [CotizacionController::class, 'show'])->name('cotizaciones.show');
});

require __DIR__ . '/settings.php';
