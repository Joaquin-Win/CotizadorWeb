<?php

use App\Data\Cotizador\BultoData;
use App\Data\Cotizador\CotizacionRequestData;
use App\Data\Cotizador\OrigenData;
use App\Data\Cotizador\DestinoData;
use App\Models\ConfiguracionCotizador;
use App\Models\Proveedor;
use App\Models\TipoCliente;
use App\Models\TipoServicio;
use App\Models\UnidadMedida;
use App\Models\Tarifa;
use App\Services\Cotizador\CotizadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// CotizadorService — tests de integración (con BD en memoria)
// ---------------------------------------------------------------------------

function seedCatalogoBasico(): void
{
    // Configuración
    ConfiguracionCotizador::insertOrIgnore([
        ['clave' => 'seguro_porcentaje', 'valor' => '0.80', 'tipo' => 'NUMBER', 'descripcion' => '', 'grupo' => 'seguro', 'editable' => true, 'created_at' => now(), 'updated_at' => now()],
        ['clave' => 'iva_porcentaje',    'valor' => '0',    'tipo' => 'NUMBER', 'descripcion' => '', 'grupo' => 'impuestos', 'editable' => true, 'created_at' => now(), 'updated_at' => now()],
        ['clave' => 'algoritmo_version', 'valor' => 'v2.0.0', 'tipo' => 'STRING', 'descripcion' => '', 'grupo' => 'sistema', 'editable' => false, 'created_at' => now(), 'updated_at' => now()],
    ]);
    // Catálogos mínimos que el motor exige
    TipoServicio::firstOrCreate(['codigo' => 'TRONCAL'], ['nombre' => 'Troncal', 'activo' => true]);
    UnidadMedida::firstOrCreate(['codigo' => 'M3'], ['nombre' => 'Metro cúbico', 'activo' => true]);
    \App\Models\Provincia::firstOrCreate(['id' => 1], ['nombre' => 'Córdoba', 'codigo_georef' => 'CBA', 'activo' => true]);
    \App\Models\Provincia::firstOrCreate(['id' => 2], ['nombre' => 'Buenos Aires', 'codigo_georef' => 'BSA', 'activo' => true]);
}

/**
 * Crea un DTO mínimo para cotizar provincia a provincia sin tramos adicionales.
 */
function makeDtoSimple(int $provOrigenId, int $provDestinoId): CotizacionRequestData
{
    $origen = new OrigenData(provinciaId: $provOrigenId, localidadId: null, solicitaRetiro: false);

    $destino = new DestinoData(provinciaId: $provDestinoId, localidadId: null, solicitaEntrega: false, retiroEnSucursal: false);

    $bulto = new BultoData(tipoBultoId: 1, cantidad: 1, largoCm: 100, anchoCm: 100, altoCm: 100, pesoKg: 50, paletizado: false);

    $dto               = new CotizacionRequestData();
    $dto->origen       = $origen;
    $dto->destino      = $destino;
    $dto->bultos       = [$bulto];
    $dto->valorDeclarado = null;
    $dto->solicitaCarga = false;
    $dto->solicitaDescarga = false;
    $dto->clienteId    = null;
    $dto->usuarioId    = null;
    $dto->tipoClienteId = TipoCliente::where('codigo', 'PUBLICO')->value('id') ?? 1;

    return $dto;
}

it('retorna ATENCION_PERSONALIZADA cuando no hay proveedor activo', function () {
    seedCatalogoBasico();
    // No crear proveedor → debe fallar

    $service = app(CotizadorService::class);
    $dto     = makeDtoSimple(1, 2);

    $resultado = $service->calcular($dto);

    expect($resultado->estado)->toBe('ATENCION_PERSONALIZADA');
    expect($resultado->errores)->not->toBeEmpty();
});

it('retorna ATENCION_PERSONALIZADA cuando no hay tarifa TRONCAL para la ruta', function () {
    seedCatalogoBasico();

    // Crear proveedor activo
    Proveedor::create([
        'nombre' => 'SET Logística',
        'cuit'   => '30-12345678-9',
        'activo' => true,
    ]);

    $service = app(CotizadorService::class);
    $dto     = makeDtoSimple(1, 2);

    $resultado = $service->calcular($dto);

    expect($resultado->estado)->toBe('ATENCION_PERSONALIZADA');
    expect($resultado->errores)->toContain('Sin tarifa para: TRONCAL');
});

it('retorna OK y calcula total cuando hay tarifa TRONCAL disponible', function () {
    seedCatalogoBasico();

    $proveedor = Proveedor::create([
        'nombre' => 'SET Logística',
        'cuit'   => '30-12345678-9',
        'activo' => true,
    ]);

    $tipoTroncal = TipoServicio::where('codigo', 'TRONCAL')->first();
    $unidadM3    = UnidadMedida::where('codigo', 'M3')->first();

    if (! $tipoTroncal || ! $unidadM3) {
        $this->markTestSkipped('Seeders de catálogo no ejecutados. Correr: php artisan db:seed');
    }

    // Crear tarifa TRONCAL para la ruta 1→2
    Tarifa::create([
        'proveedor_id'         => $proveedor->id,
        'provincia_origen_id'  => 1,
        'provincia_destino_id' => 2,
        'tipo_servicio_id'     => $tipoTroncal->id,
        'unidad_medida_id'     => $unidadM3->id,
        'costo_unitario'       => 5000,
        'maximo'               => null,
        'vigente_desde'        => now()->subDay()->toDateString(),
        'vigente_hasta'        => null,
        'created_by'           => null,
        'updated_by'           => null,
    ]);

    $service = app(CotizadorService::class);
    $dto     = makeDtoSimple(1, 2);

    $resultado = $service->calcular($dto);

    expect($resultado->estado)->toBe('OK');
    expect($resultado->costoTroncal)->toBeGreaterThan(0);
    expect($resultado->totalFinal)->toBeGreaterThan(0);
    expect($resultado->versionAlgoritmo)->toBe('v2.0.0');
});

it('el total final es mayor que el subtotal cuando hay margen', function () {
    seedCatalogoBasico();

    $proveedor = Proveedor::create([
        'nombre' => 'SET Logística',
        'cuit'   => '30-12345678-9',
        'activo' => true,
    ]);

    $tipoTroncal = TipoServicio::where('codigo', 'TRONCAL')->first();
    $unidadM3    = UnidadMedida::where('codigo', 'M3')->first();

    if (! $tipoTroncal || ! $unidadM3) {
        $this->markTestSkipped('Seeders no ejecutados.');
    }

    Tarifa::create([
        'proveedor_id'         => $proveedor->id,
        'provincia_origen_id'  => 1,
        'provincia_destino_id' => 2,
        'tipo_servicio_id'     => $tipoTroncal->id,
        'unidad_medida_id'     => $unidadM3->id,
        'costo_unitario'       => 10000,
        'maximo'               => null,
        'vigente_desde'        => now()->subDay()->toDateString(),
        'vigente_hasta'        => null,
        'created_by'           => null,
        'updated_by'           => null,
    ]);

    // Agregar margen de ganancia global
    \App\Models\MargenGanancia::create([
        'tipo_cliente_id'  => null,
        'tipo_servicio_id' => null,
        'porcentaje'       => 20,
        'descripcion'      => 'Margen global test',
        'activo'           => true,
        'vigente_desde'    => now()->subDay()->toDateString(),
    ]);

    $service   = app(CotizadorService::class);
    $dto       = makeDtoSimple(1, 2);
    $resultado = $service->calcular($dto);

    expect($resultado->estado)->toBe('OK');
    // Con margen del 20%, el total debe ser > subtotal_flete
    expect($resultado->totalFinal)->toBeGreaterThan($resultado->subtotalFlete);
    expect($resultado->margenMonto)->toBeGreaterThan(0);
});
