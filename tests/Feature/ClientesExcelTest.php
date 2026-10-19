<?php

use App\Models\Cliente;
use App\Models\EstadoCliente;
use App\Models\TipoCliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

uses(RefreshDatabase::class);

function seedCatalogo(): array
{
    $tipo = TipoCliente::firstOrCreate(['codigo' => 'B2B'], ['nombre' => 'B2B', 'activo' => true]);
    $activo = EstadoCliente::firstOrCreate(['codigo' => 'ACTIVO'], ['nombre' => 'Activo', 'permite_operar' => true, 'activo' => true]);
    $inactivo = EstadoCliente::firstOrCreate(['codigo' => 'INACTIVO'], ['nombre' => 'Inactivo', 'permite_operar' => false, 'activo' => true]);

    return [$tipo, $activo, $inactivo];
}

/** Genera un CUIT válido (dígito verificador OK). */
function cuitValido(): string
{
    $base = str_pad(random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
    $diez = '20' . $base; // 10 dígitos
    $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    $suma = 0;
    for ($i = 0; $i < 10; $i++) {
        $suma += (int) $diez[$i] * $pesos[$i];
    }
    $resto = $suma % 11;
    $check = $resto === 0 ? 0 : ($resto === 1 ? 9 : 11 - $resto);

    $cuit11 = $diez . $check; // 11 dígitos

    return substr($cuit11, 0, 2) . '-' . substr($cuit11, 2, 8) . '-' . substr($cuit11, 10, 1);
}

function archivoXlsx(array $filas): string
{
    $ruta = storage_path('app/test-clientes-import.xlsx');
    $writer = new Writer();
    $writer->openToFile($ruta);
    $writer->addRow(Row::fromValues($filas[0]));
    for ($i = 1; $i < count($filas); $i++) {
        $writer->addRow(Row::fromValues($filas[$i]));
    }
    $writer->close();

    return $ruta;
}

test('importar crea cliente con contacto principal', function () {
    seedCatalogo();
    $admin = User::factory()->create(['rol_id' => 1]);

    $cuit = cuitValido();
    $ruta = archivoXlsx([
        ['Razón social', 'Nombre fantasía', 'CUIT', 'Tipo', 'Estado', 'Email facturación', 'Teléfono', 'Dirección', 'Provincia', 'Localidad', 'Código postal', 'Nombre contacto', 'Email contacto', 'Teléfono contacto'],
        ['Prueba S.A.', 'Prueba', $cuit, 'B2B', 'Activo', 'factura@prueba.com', '3511112222', 'Calle 1', null, null, null, 'Contacto', 'contacto@prueba.com', '3513334444'],
    ]);

    $resultado = app(\App\Services\Clientes\ClientesExcelService::class)->importar($ruta, $admin->id);

    expect($resultado['creadas'])->toBe(1);
    expect($resultado['actualizadas'])->toBe(0);
    expect($resultado['errores'])->toBeEmpty();

    $cliente = Cliente::where('cuit', $cuit)->first();
    expect($cliente)->not->toBeNull();
    expect($cliente->razon_social)->toBe('Prueba S.A.');
    expect($cliente->estado->codigo)->toBe('ACTIVO');
    expect($cliente->tipoCliente->codigo)->toBe('B2B');
    expect($cliente->contactos->first()->es_principal)->toBeTrue();
    expect($cliente->contactos->first()->nombre)->toBe('Contacto');
});

test('importar actualiza por CUIT (idempotente)', function () {
    seedCatalogo();
    $admin = User::factory()->create(['rol_id' => 1]);

    $cuit = cuitValido();
    Cliente::create([
        'razon_social' => 'Vieja S.A.',
        'cuit' => $cuit,
        'tipo_cliente_id' => 1,
        'estado_id' => 1,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $ruta = archivoXlsx([
        ['Razón social', 'Nombre fantasía', 'CUIT', 'Tipo', 'Estado', 'Email facturación', 'Teléfono', 'Dirección', 'Provincia', 'Localidad', 'Código postal', 'Nombre contacto', 'Email contacto', 'Teléfono contacto'],
        ['Nueva S.A.', 'Nueva', $cuit, 'B2B', 'Activo', 'factura@nueva.com', '3512223333', 'Calle 2', null, null, null, null, null, null],
    ]);

    $resultado = app(\App\Services\Clientes\ClientesExcelService::class)->importar($ruta, $admin->id);

    expect($resultado['creadas'])->toBe(0);
    expect($resultado['actualizadas'])->toBe(1);
    expect($resultado['errores'])->toBeEmpty();

    $cliente = Cliente::where('cuit', $cuit)->first();
    expect($cliente->razon_social)->toBe('Nueva S.A.');
    expect($cliente->telefono)->toBe('3512223333');
});

test('CUIT inválido genera error de fila y no importa nada', function () {
    seedCatalogo();
    $admin = User::factory()->create(['rol_id' => 1]);

    $ruta = archivoXlsx([
        ['Razón social', 'Nombre fantasía', 'CUIT', 'Tipo', 'Estado', 'Email facturación', 'Teléfono', 'Dirección', 'Provincia', 'Localidad', 'Código postal', 'Nombre contacto', 'Email contacto', 'Teléfono contacto'],
        ['Mala S.A.', 'Mala', '30-12345678-9', 'B2B', 'Activo', 'factura@mala.com', '3511112222', 'Calle 1', 'Córdoba', 'Córdoba', 'X5000', null, null, null],
    ]);

    $resultado = app(\App\Services\Clientes\ClientesExcelService::class)->importar($ruta, $admin->id);

    expect($resultado['creadas'])->toBe(0);
    expect($resultado['actualizadas'])->toBe(0);
    expect($resultado['errores'])->toContain('Fila 2: CUIT inválido: usá formato XX-XXXXXXXX-X con dígito verificador correcto.');
    expect(Cliente::count())->toBe(0);
});

test('razón social vacía genera error de fila', function () {
    seedCatalogo();
    $admin = User::factory()->create(['rol_id' => 1]);

    $cuit = cuitValido();
    $ruta = archivoXlsx([
        ['Razón social', 'Nombre fantasía', 'CUIT', 'Tipo', 'Estado', 'Email facturación', 'Teléfono', 'Dirección', 'Provincia', 'Localidad', 'Código postal', 'Nombre contacto', 'Email contacto', 'Teléfono contacto'],
        ['', 'Sin razón', $cuit, 'B2B', 'Activo', 'factura@x.com', null, null, null, null, null, null, null, null],
    ]);

    $resultado = app(\App\Services\Clientes\ClientesExcelService::class)->importar($ruta, $admin->id);

    expect($resultado['creadas'])->toBe(0);
    expect($resultado['errores'])->toContain('Fila 2: Razón social es obligatoria.');
});

test('cliente no puede acceder a rutas de gestión (dashboard, clientes, usuarios)', function () {
    seedCatalogo();
    [$tipo, $activo] = seedCatalogo();
    $admin = User::factory()->create(['rol_id' => 1]);
    $cliente = Cliente::create([
        'razon_social' => 'Cliente Test S.A.',
        'cuit' => cuitValido(),
        'tipo_cliente_id' => $tipo->id,
        'estado_id' => $activo->id,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    $usuario = User::factory()->create([
        'rol_id' => 2,
        'cliente_id' => $cliente->id,
        'email' => 'cliente@test.com',
        'password' => bcrypt('password'),
    ]);

    // /dashboard redirige al portal del cliente
    $this->actingAs($usuario)->get('/dashboard')->assertRedirect();

    // /usuarios y /clientes son solo admin
    $this->actingAs($usuario)->get('/usuarios')->assertStatus(403);
    $this->actingAs($usuario)->get('/clientes')->assertStatus(403);
});

test('cliente accede a su propio portal y no al ajeno', function () {
    seedCatalogo();
    [$tipo, $activo] = seedCatalogo();
    $admin = User::factory()->create(['rol_id' => 1]);
    $cliente = Cliente::create([
        'razon_social' => 'Cliente Test S.A.',
        'cuit' => cuitValido(),
        'tipo_cliente_id' => $tipo->id,
        'estado_id' => $activo->id,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    $otro = Cliente::create([
        'razon_social' => 'Otro Test S.A.',
        'cuit' => cuitValido(),
        'tipo_cliente_id' => $tipo->id,
        'estado_id' => $activo->id,
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);
    $usuario = User::factory()->create([
        'rol_id' => 2,
        'cliente_id' => $cliente->id,
        'email' => 'cliente@test.com',
        'password' => bcrypt('password'),
    ]);

    // Su propio portal: OK
    $this->actingAs($usuario)->get("/clientes/{$cliente->id}/portal/resumen")->assertOk();

    // Portal ajeno: 403
    $this->actingAs($usuario)->get("/clientes/{$otro->id}/portal/resumen")->assertStatus(403);
});
