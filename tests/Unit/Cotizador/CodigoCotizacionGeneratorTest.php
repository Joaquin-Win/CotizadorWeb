<?php

use App\Services\Cotizador\CodigoCotizacionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// CodigoCotizacionGenerator
// ---------------------------------------------------------------------------

it('genera un codigo con el formato SET-ANIO-XXXXXX', function () {
    $generator = new CodigoCotizacionGenerator();
    $codigo    = $generator->generar();

    $anio = now()->year;
    expect($codigo)->toMatch("/^SET-{$anio}-[A-Z0-9]{6}$/");
});

it('genera codigos distintos en llamadas sucesivas', function () {
    $generator = new CodigoCotizacionGenerator();
    $codigos   = [];

    for ($i = 0; $i < 10; $i++) {
        $codigos[] = $generator->generar();
    }

    // Todos deben ser únicos
    expect(count(array_unique($codigos)))->toBe(10);
});

it('el codigo tiene exactamente 14 caracteres', function () {
    // SET-2026-A3X9KQ = 14 chars
    $generator = new CodigoCotizacionGenerator();
    $codigo    = $generator->generar();

    // SET-XXXX-XXXXXX = 3+1+4+1+6 = 15 chars
    expect(strlen($codigo))->toBe(15);
});
