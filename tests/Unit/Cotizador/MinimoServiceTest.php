<?php

use App\Services\Cotizador\MinimoService;
use App\Models\TarifaMinima;
use App\Models\TipoServicio;
use App\Models\Provincia;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// MinimoService
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->servicio = new MinimoService();
});

it('retorna el costo calculado si no hay minimo configurado', function () {
    // Sin registros en tarifas_minimas → se retorna el costo tal cual
    $resultado = $this->servicio->aplicar(
        costoCalculado: 5000,
        provinciaOrigenId: 999,
        provinciaDestinoId: 998,
        tipoServicioId: 1
    );

    expect($resultado)->toBe(5000.0);
});

it('aplica el minimo cuando el costo calculado es menor', function () {
    // Necesitamos provincias y tipo_servicio reales
    $provOrigen  = Provincia::first();
    $provDestino = Provincia::skip(1)->first();
    $tipoServicio = TipoServicio::where('codigo', 'TRONCAL')->first();

    if (! $provOrigen || ! $provDestino || ! $tipoServicio) {
        $this->markTestSkipped('Faltan datos de catálogo. Ejecutá los seeders primero.');
    }

    TarifaMinima::create([
        'provincia_origen_id'  => $provOrigen->id,
        'provincia_destino_id' => $provDestino->id,
        'tipo_servicio_id'     => $tipoServicio->id,
        'monto_minimo'         => 15000,
        'descripcion'          => 'Test mínimo',
    ]);

    // Costo calculado = 5000 < mínimo 15000 → debe retornar 15000
    $resultado = $this->servicio->aplicar(
        costoCalculado: 5000,
        provinciaOrigenId: $provOrigen->id,
        provinciaDestinoId: $provDestino->id,
        tipoServicioId: $tipoServicio->id
    );

    expect($resultado)->toBe(15000.0);
});

it('no aplica el minimo cuando el costo calculado es mayor', function () {
    $provOrigen   = Provincia::first();
    $provDestino  = Provincia::skip(1)->first();
    $tipoServicio = TipoServicio::where('codigo', 'TRONCAL')->first();

    if (! $provOrigen || ! $provDestino || ! $tipoServicio) {
        $this->markTestSkipped('Faltan datos de catálogo.');
    }

    TarifaMinima::create([
        'provincia_origen_id'  => $provOrigen->id,
        'provincia_destino_id' => $provDestino->id,
        'tipo_servicio_id'     => $tipoServicio->id,
        'monto_minimo'         => 5000,
        'descripcion'          => 'Test mínimo',
    ]);

    // Costo calculado = 50000 > mínimo 5000 → debe retornar 50000
    $resultado = $this->servicio->aplicar(
        costoCalculado: 50000,
        provinciaOrigenId: $provOrigen->id,
        provinciaDestinoId: $provDestino->id,
        tipoServicioId: $tipoServicio->id
    );

    expect($resultado)->toBe(50000.0);
});
