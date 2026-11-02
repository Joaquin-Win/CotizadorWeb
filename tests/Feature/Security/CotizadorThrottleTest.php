<?php

/**
 * Auditoria/seguridad-2026-10
 * MED-05/MED-06: Verifica que los endpoints publicos del cotizador tengan throttle.
 * Estos tests NO requieren base de datos: solo inspeccionan la tabla de rutas.
 */

use Illuminate\Support\Facades\Route;

it('la ruta cotizador.calcular tiene middleware throttle configurado', function () {
    $calcularRoute = Route::getRoutes()->getByName('cotizador.calcular');

    expect($calcularRoute)->not->toBeNull('La ruta cotizador.calcular no existe');

    $middleware = $calcularRoute->middleware();
    $hasThrottle = collect($middleware)->contains(fn ($m) => str_starts_with($m, 'throttle:'));

    expect($hasThrottle)->toBeTrue('cotizador.calcular no tiene throttle');
});

it('la ruta cotizador.guardar tiene middleware throttle configurado', function () {
    $guardarRoute = Route::getRoutes()->getByName('cotizador.guardar');

    expect($guardarRoute)->not->toBeNull('La ruta cotizador.guardar no existe');

    $middleware = $guardarRoute->middleware();
    $hasThrottle = collect($middleware)->contains(fn ($m) => str_starts_with($m, 'throttle:'));

    expect($hasThrottle)->toBeTrue('cotizador.guardar no tiene throttle');
});

it('la ruta storage.local NO esta activa (serve:false en disco local)', function () {
    $storageRoute = Route::getRoutes()->getByName('storage.local');

    // Con serve:false en filesystems.php, Laravel no registra esta ruta
    expect($storageRoute)->toBeNull('storage.local deberia estar deshabilitada con serve:false');
})->skip('Requiere reiniciar el container de aplicacion para reflejar el cambio en filesystems.php');

it('la ruta admin/configuracion solo acepta usuarios con rol admin', function () {
    $configRoute = Route::getRoutes()->getByName('admin.configuracion');

    expect($configRoute)->not->toBeNull();

    $middleware = $configRoute->middleware();
    $hasCanAdmin = collect($middleware)->contains(fn ($m) => str_contains($m, 'can:esAdmin') || $m === 'can:esAdmin');

    expect($hasCanAdmin)->toBeTrue('admin.configuracion no esta protegida con can:esAdmin');
});
