<?php

use App\Data\Cotizador\BultoData;
use App\Services\Cotizador\AforoService;

// ---------------------------------------------------------------------------
// AforoService
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->aforo = new AforoService();
});

it('calcula el volumen en m3 de un bulto', function () {
    $bulto = new BultoData(tipoBultoId: 1, cantidad: 1, largoCm: 100, anchoCm: 100, altoCm: 100, pesoKg: 10, paletizado: false);

    $volumen = $this->aforo->calcularVolumenM3($bulto);

    expect($volumen)->toBe(1.0);
});

it('calcula el volumen multiplicado por cantidad', function () {
    $bulto = new BultoData(tipoBultoId: 1, cantidad: 4, largoCm: 100, anchoCm: 50, altoCm: 50, pesoKg: 10, paletizado: false);

    // 1m × 0.5m × 0.5m × 4 bultos = 1.0 m3
    $volumen = $this->aforo->calcularVolumenM3($bulto);

    expect($volumen)->toBe(1.0);
});

it('calcula el peso total en kg', function () {
    $bulto = new BultoData(tipoBultoId: 1, cantidad: 3, largoCm: 50, anchoCm: 50, altoCm: 50, pesoKg: 10, paletizado: false);

    expect($this->aforo->calcularPesoTotalKg($bulto))->toBe(30.0);
});

it('determina unidad M3 cuando el costo por m3 es mayor', function () {
    $bulto = new BultoData(tipoBultoId: 1, cantidad: 1, largoCm: 100, anchoCm: 100, altoCm: 100, pesoKg: 1, paletizado: false);

    // tarifa M3 = 5000, tarifa KG = 1 → costo_m3 = 5000, costo_tn = 0.001
    $unidad = $this->aforo->determinarUnidadCobro($bulto, ['M3' => 5000, 'KG' => 1]);

    expect($unidad)->toBe('M3');
});

it('determina unidad KG cuando el costo por kg es mayor', function () {
    $bulto = new BultoData(tipoBultoId: 1, cantidad: 1, largoCm: 10, anchoCm: 10, altoCm: 10, pesoKg: 10000, paletizado: false);

    // volumen = 0.001 m3 → costo_m3 = 0.001 × 1 = 0.001
    // toneladas = 10 → costo_tn = 10 × 5000 = 50000
    $unidad = $this->aforo->determinarUnidadCobro($bulto, ['M3' => 1, 'KG' => 5000]);

    expect($unidad)->toBe('KG');
});

it('determina unidad PALLET cuando el bulto esta palletizado y hay tarifa pallet', function () {
    $bulto = new BultoData(tipoBultoId: 1, cantidad: 1, largoCm: 120, anchoCm: 100, altoCm: 150, pesoKg: 500, paletizado: true);

    $unidad = $this->aforo->determinarUnidadCobro($bulto, [
        'M3'     => 5000,
        'KG'     => 100,
        'PALLET' => 80000,
    ]);

    expect($unidad)->toBe('PALLET');
});

it('no usa PALLET si el bulto no esta palletizado aunque haya tarifa', function () {
    $bulto = new BultoData(tipoBultoId: 1, cantidad: 1, largoCm: 120, anchoCm: 100, altoCm: 150, pesoKg: 100, paletizado: false);

    $unidad = $this->aforo->determinarUnidadCobro($bulto, [
        'M3'     => 5000,
        'KG'     => 100,
        'PALLET' => 80000,
    ]);

    expect($unidad)->not->toBe('PALLET');
});
