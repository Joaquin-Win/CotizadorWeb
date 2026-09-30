<?php

use App\Data\Cotizador\ResultadoCotizacion;
use App\Services\Cotizador\CommercialPricingPolicy;
use App\Services\Cotizador\SeguroService;
use App\Services\Cotizador\MargenService;
use App\Services\Cotizador\AcuerdoComercialService;
use App\Models\ConfiguracionCotizador;
use App\Models\TipoCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// CommercialPricingPolicy
// ---------------------------------------------------------------------------

beforeEach(function () {
    // Insertar configuracion de seguro
    ConfiguracionCotizador::insertOrIgnore([
        ['clave' => 'seguro_porcentaje', 'valor' => '0.80', 'tipo' => 'NUMBER', 'descripcion' => '', 'grupo' => 'seguro', 'editable' => true, 'created_at' => now(), 'updated_at' => now()],
        ['clave' => 'iva_porcentaje',    'valor' => '0',    'tipo' => 'NUMBER', 'descripcion' => '', 'grupo' => 'impuestos', 'editable' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->policy = new CommercialPricingPolicy(
        new SeguroService(),
        new MargenService(),
        new AcuerdoComercialService(),
    );
});

it('calcula el subtotal flete sumando los tramos', function () {
    $resultado = new ResultadoCotizacion();
    $resultado->costoPrimeraMilla = 5000;
    $resultado->costoTroncal      = 20000;
    $resultado->costoUltimaMilla  = 5000;
    $resultado->costoPuertaPuerta = 0;
    $resultado->costoCargaDescarga = 0;
    $resultado->adicionales       = [];

    $tipoCliente = TipoCliente::where('codigo', 'PUBLICO')->first();
    if (! $tipoCliente) {
        $this->markTestSkipped('Seeder de tipos_cliente no ejecutado.');
    }

    $resultado = $this->policy->aplicar($resultado, [
        'tipo_cliente_id'    => $tipoCliente->id,
        'tipo_servicio_id'   => null,
        'valor_declarado'    => null,
        'seguro_incluido'    => false,
        'descuento_porcentaje' => 0,
        'iva_porcentaje'     => 0,
    ]);

    expect($resultado->subtotalFlete)->toBe(30000.0);
});

it('calcula el seguro sobre el valor declarado', function () {
    $resultado = new ResultadoCotizacion();
    $resultado->costoPrimeraMilla  = 0;
    $resultado->costoTroncal       = 20000;
    $resultado->costoUltimaMilla   = 0;
    $resultado->costoPuertaPuerta  = 0;
    $resultado->costoCargaDescarga = 0;
    $resultado->adicionales        = [];

    $tipoCliente = TipoCliente::where('codigo', 'PUBLICO')->first();
    if (! $tipoCliente) {
        $this->markTestSkipped('Seeder no ejecutado.');
    }

    $resultado = $this->policy->aplicar($resultado, [
        'tipo_cliente_id'    => $tipoCliente->id,
        'tipo_servicio_id'   => null,
        'valor_declarado'    => 100000,   // → seguro = 0.80% = 800
        'seguro_incluido'    => false,
        'descuento_porcentaje' => 0,
        'iva_porcentaje'     => 0,
    ]);

    expect($resultado->costoSeguro)->toBe(800.0);
});

it('aplica el descuento sobre el total con margen', function () {
    $resultado = new ResultadoCotizacion();
    $resultado->costoPrimeraMilla  = 0;
    $resultado->costoTroncal       = 10000;
    $resultado->costoUltimaMilla   = 0;
    $resultado->costoPuertaPuerta  = 0;
    $resultado->costoCargaDescarga = 0;
    $resultado->adicionales        = [];

    $tipoCliente = TipoCliente::where('codigo', 'PUBLICO')->first();
    if (! $tipoCliente) {
        $this->markTestSkipped('Seeder no ejecutado.');
    }

    $resultado = $this->policy->aplicar($resultado, [
        'tipo_cliente_id'    => $tipoCliente->id,
        'tipo_servicio_id'   => null,
        'valor_declarado'    => null,
        'seguro_incluido'    => false,
        'descuento_porcentaje' => 10,   // 10%
        'iva_porcentaje'     => 0,
    ]);

    // El total debe ser 10% menos que (subtotal + margen)
    expect($resultado->descuentoPorcentaje)->toBe(10.0);
    expect($resultado->descuentoMonto)->toBeGreaterThan(0);
    expect($resultado->totalFinal)->toBeLessThan(10000);
});

it('el total final nunca es negativo', function () {
    $resultado = new ResultadoCotizacion();
    $resultado->costoPrimeraMilla  = 0;
    $resultado->costoTroncal       = 100;  // muy bajo
    $resultado->costoUltimaMilla   = 0;
    $resultado->costoPuertaPuerta  = 0;
    $resultado->costoCargaDescarga = 0;
    $resultado->adicionales        = [];

    $tipoCliente = TipoCliente::where('codigo', 'PUBLICO')->first();
    if (! $tipoCliente) {
        $this->markTestSkipped('Seeder no ejecutado.');
    }

    $resultado = $this->policy->aplicar($resultado, [
        'tipo_cliente_id'    => $tipoCliente->id,
        'tipo_servicio_id'   => null,
        'valor_declarado'    => null,
        'seguro_incluido'    => false,
        'descuento_porcentaje' => 100,  // 100% de descuento
        'iva_porcentaje'     => 0,
    ]);

    expect($resultado->totalFinal)->toBeGreaterThanOrEqual(0);
});
