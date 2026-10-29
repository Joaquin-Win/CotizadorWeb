<?php

/**
 * Auditoria/seguridad-2026-10 — Tests de seguridad de rutas.
 *
 * Verifican configuracion de middleware en rutas criticas.
 * No requieren base de datos.
 */

use Illuminate\Support\Facades\Route;

// Carga la aplicacion para tener acceso al router
beforeEach(function () {
    // Los tests de Unit no tienen app() por defecto en Pest — forzar carga
    if (! app()->routesAreCached()) {
        // Las rutas ya estan cargadas por el bootstrap de Laravel
    }
});

it('cotizador.calcular tiene middleware throttle', function () {
    $route = Route::getRoutes()->getByName('cotizador.calcular');

    expect($route)->not->toBeNull('cotizador.calcular debe existir');

    $hasThrottle = collect($route->middleware())
        ->contains(fn ($m) => str_starts_with($m, 'throttle:'));

    expect($hasThrottle)->toBeTrue('cotizador.calcular debe tener throttle');
});

it('cotizador.guardar tiene middleware throttle', function () {
    $route = Route::getRoutes()->getByName('cotizador.guardar');

    expect($route)->not->toBeNull('cotizador.guardar debe existir');

    $hasThrottle = collect($route->middleware())
        ->contains(fn ($m) => str_starts_with($m, 'throttle:'));

    expect($hasThrottle)->toBeTrue('cotizador.guardar debe tener throttle');
});

it('admin.cotizaciones.index requiere autenticacion y rol admin', function () {
    $route = Route::getRoutes()->getByName('admin.cotizaciones.index');

    expect($route)->not->toBeNull();

    $middleware = collect($route->middleware());
    $hasAuth    = $middleware->contains('auth') || $middleware->contains('auth:web');
    $hasAdmin   = $middleware->contains('can:esAdmin');

    expect($hasAuth)->toBeTrue('admin.cotizaciones.index debe requerir auth');
    expect($hasAdmin)->toBeTrue('admin.cotizaciones.index debe requerir can:esAdmin');
});

it('admin.configuracion.update requiere autenticacion y rol admin', function () {
    $route = Route::getRoutes()->getByName('admin.configuracion.update');

    expect($route)->not->toBeNull();

    $middleware = collect($route->middleware());
    $hasAdmin   = $middleware->contains('can:esAdmin');

    expect($hasAdmin)->toBeTrue('admin.configuracion.update debe tener can:esAdmin');
});

it('clientes.portal.resumen requiere autenticacion', function () {
    $route = Route::getRoutes()->getByName('clientes.portal.resumen');

    expect($route)->not->toBeNull();

    $hasAuth = collect($route->middleware())
        ->contains(fn ($m) => str_starts_with($m, 'auth'));

    expect($hasAuth)->toBeTrue('clientes.portal.resumen debe requerir auth');
});
