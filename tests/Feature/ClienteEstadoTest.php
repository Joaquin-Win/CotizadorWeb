<?php

use App\Models\Cliente;
use App\Models\EstadoCliente;
use App\Models\TipoCliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCliente(array $overrides = []): Cliente
{
    [$tipo, $activo] = seedEstadoTipoOnce();

    return Cliente::create(array_merge([
        'tipo_cliente_id' => $tipo->id,
        'estado_id' => $activo->id,
        'razon_social' => 'Empresa Test S.R.L.',
        'cuit' => '30-12345678-9',
    ], $overrides));
}

function seedEstadoTipoOnce(): array
{
    $tipo = TipoCliente::firstOrCreate(['codigo' => 'B2B'], ['nombre' => 'B2B', 'activo' => true]);
    $activo = EstadoCliente::firstOrCreate(['codigo' => 'ACTIVO'], ['nombre' => 'Activo', 'permite_operar' => true, 'activo' => true]);
    $inactivo = EstadoCliente::firstOrCreate(['codigo' => 'INACTIVO'], ['nombre' => 'Inactivo', 'permite_operar' => false, 'activo' => true]);

    return [$tipo, $activo, $inactivo];
}

test('inhabilitar cambia el estado y lo saca de la lista', function () {
    $admin = User::factory()->create(['rol_id' => 1]);
    $cliente = makeCliente();

    $this->actingAs($admin)->put("/clientes/{$cliente->id}/inactivar");

    expect($cliente->refresh()->estado->codigo)->toBe('INACTIVO');

    $response = $this->actingAs($admin)->get('/clientes');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('clientes/index')
        ->where('clientes', fn ($clientes) => collect($clientes)->pluck('id')->doesntContain($cliente->id)),
    );
});

test('habilitar devuelve el cliente a la lista', function () {
    [$tipo, $activo, $inactivo] = seedEstadoTipoOnce();
    $admin = User::factory()->create(['rol_id' => 1]);
    $cliente = makeCliente(['estado_id' => $inactivo->id]);

    $this->actingAs($admin)->put("/clientes/{$cliente->id}/activar");

    expect($cliente->refresh()->estado->codigo)->toBe('ACTIVO');
});

test('login bloqueado para empresa inactiva sin pendientes', function () {
    [$tipo, $activo, $inactivo] = seedEstadoTipoOnce();
    $cliente = makeCliente(['estado_id' => $inactivo->id]);
    $user = User::factory()->create([
        'rol_id' => 2,
        'cliente_id' => $cliente->id,
        'email' => 'invitado@test.com',
    ]);

    $response = $this->post(route('login.store'), [
        'email' => 'invitado@test.com',
        'password' => 'password',
    ]);

    $this->assertGuest();
});
