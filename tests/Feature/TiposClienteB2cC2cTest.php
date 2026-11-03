<?php

use App\Models\Cliente;
use App\Models\TipoCliente;
use App\Models\User;
use Database\Seeders\CatalogoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(CatalogoSeeder::class));

test('B2C y C2C existen activos y permiten acuerdos comerciales', function (string $codigo) {
    $tipo = TipoCliente::where('codigo', $codigo)->first();

    expect($tipo)->not->toBeNull()
        ->and($tipo->activo)->toBeTruthy()
        ->and($tipo->permite_acuerdo_comercial)->toBeTruthy()
        ->and($tipo->requiere_usuario)->toBeTruthy();
})->with(['B2C', 'C2C']);

test('se puede crear un cliente B2C o C2C con su acceso al portal', function (string $codigo) {
    $admin = User::factory()->create(['rol_id' => 1]);
    $tipo = TipoCliente::where('codigo', $codigo)->first();

    $this->actingAs($admin)->post('/clientes', [
        'razon_social' => "Cliente {$codigo}",
        'nombre_fantasia' => null, // el formulario siempre lo envía; el controlador lo lee sin validar que exista
        'cuit' => '20-11111111-2',
        'tipo_cliente_id' => $tipo->id,
        'email_acceso' => strtolower($codigo).'@example.com',
        'password' => 'Una-clave-larga-9137',
        'password_confirmation' => 'Una-clave-larga-9137',
    ])->assertSessionHasNoErrors();

    $cliente = Cliente::where('cuit', '20-11111111-2')->first();

    expect($cliente->tipo_cliente_id)->toBe($tipo->id)
        ->and($cliente->usuarios()->count())->toBe(1);
})->with(['B2C', 'C2C']);
