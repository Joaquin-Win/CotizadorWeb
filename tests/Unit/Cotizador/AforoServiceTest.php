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
    $bulto           = new BultoData();
    $bulto->largoCm  = 100;  // 1 m
    $bulto->anchoCm  = 100;  // 1 m
    $bulto->altoCm   = 100;  // 1 m
    $bulto->cantidad = 1;
    $bulto->pesoKg   = 10;
    $bulto->paletizado = false;

    $volumen = $this->aforo->calcularVolumenM3($bulto);

    expect($volumen)->toBe(1.0);
});

it('calcula el volumen multiplicado por cantidad', function () {
    $bulto           = new BultoData();
    $bulto->largoCm  = 100;
    $bulto->anchoCm  = 50;
    $bulto->altoCm   = 50;
    $bulto->cantidad = 4;
    $bulto->pesoKg   = 10;
    $bulto->paletizado = false;

    // 1m × 0.5m × 0.5m × 4 bultos = 1.0 m3
    $volumen = $this->aforo->calcularVolumenM3($bulto);

    expect($volumen)->toBe(1.0);
});

it('calcula el peso total en kg', function () {
    $bulto           = new BultoData();
    $bulto->largoCm  = 50;
    $bulto->anchoCm  = 50;
    $bulto->altoCm   = 50;
    $bulto->cantidad = 3;
    $bulto->pesoKg   = 10;
    $bulto->paletizado = false;

    expect($this->aforo->calcularPesoTotalKg($bulto))->toBe(30.0);
});

it('determina unidad M3 cuando el costo por m3 es mayor', function () {
    $bulto           = new BultoData();
    $bulto->largoCm  = 100;
    $bulto->anchoCm  = 100;
    $bulto->altoCm   = 100;
    $bulto->cantidad = 1;
    $bulto->pesoKg   = 1;   // muy liviano → el m3 pesa más
    $bulto->paletizado = false;

    // tarifa M3 = 5000, tarifa KG = 1 → costo_m3 = 5000, costo_tn = 0.001
    $unidad = $this->aforo->determinarUnidadCobro($bulto, ['M3' => 5000, 'KG' => 1]);

    expect($unidad)->toBe('M3');
});

it('determina unidad KG cuando el costo por kg es mayor', function () {
    $bulto           = new BultoData();
    $bulto->largoCm  = 10;
    $bulto->anchoCm  = 10;
    $bulto->altoCm   = 10;
    $bulto->cantidad = 1;
    $bulto->pesoKg   = 10000;   // muy pesado → las toneladas pesan más
    $bulto->paletizado = false;

    // volumen = 0.001 m3 → costo_m3 = 0.001 × 1 = 0.001
    // toneladas = 10 → costo_tn = 10 × 5000 = 50000
    $unidad = $this->aforo->determinarUnidadCobro($bulto, ['M3' => 1, 'KG' => 5000]);

    expect($unidad)->toBe('KG');
});

it('determina unidad PALLET cuando el bulto esta palletizado y hay tarifa pallet', function () {
    $bulto           = new BultoData();
    $bulto->largoCm  = 120;
    $bulto->anchoCm  = 100;
    $bulto->altoCm   = 150;
    $bulto->cantidad = 1;
    $bulto->pesoKg   = 500;
    $bulto->paletizado = true;

    $unidad = $this->aforo->determinarUnidadCobro($bulto, [
        'M3'     => 5000,
        'KG'     => 100,
        'PALLET' => 80000,
    ]);

    expect($unidad)->toBe('PALLET');
});

it('no usa PALLET si el bulto no esta palletizado aunque haya tarifa', function () {
    $bulto           = new BultoData();
    $bulto->largoCm  = 120;
    $bulto->anchoCm  = 100;
    $bulto->altoCm   = 150;
    $bulto->cantidad = 1;
    $bulto->pesoKg   = 100;
    $bulto->paletizado = false;

    $unidad = $this->aforo->determinarUnidadCobro($bulto, [
        'M3'     => 5000,
        'KG'     => 100,
        'PALLET' => 80000,
    ]);

    expect($unidad)->not->toBe('PALLET');
});
