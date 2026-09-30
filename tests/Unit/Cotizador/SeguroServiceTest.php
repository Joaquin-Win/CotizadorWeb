<?php

use App\Services\Cotizador\SeguroService;
use App\Models\ConfiguracionCotizador;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// SeguroService
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->servicio = new SeguroService();

    // Insertar configuración de seguro para los tests
    ConfiguracionCotizador::create([
        'clave'       => 'seguro_porcentaje',
        'valor'       => '0.80',
        'tipo'        => 'NUMBER',
        'descripcion' => 'Test',
        'grupo'       => 'seguro',
        'editable'    => true,
    ]);
});

it('retorna cero si no hay valor declarado', function () {
    expect($this->servicio->calcular(null))->toBe(0.0);
    expect($this->servicio->calcular(0))->toBe(0.0);
});

it('retorna cero si el seguro esta incluido en el acuerdo', function () {
    expect($this->servicio->calcular(100000, incluidoEnAcuerdo: true))->toBe(0.0);
});

it('calcula el seguro correctamente con porcentaje de la BD', function () {
    // valor_declarado = 100000, porcentaje = 0.80% → 800
    $costo = $this->servicio->calcular(100000);
    expect($costo)->toBe(800.0);
});

it('calcula el seguro para valores distintos', function () {
    // valor_declarado = 50000, porcentaje = 0.80% → 400
    $costo = $this->servicio->calcular(50000);
    expect($costo)->toBe(400.0);
});

it('retorna el porcentaje vigente desde la BD', function () {
    $pct = $this->servicio->porcentajeVigente();
    expect($pct)->toBe(0.80);
});
