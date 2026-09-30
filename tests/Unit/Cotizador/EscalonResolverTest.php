<?php

use App\Models\Tarifa;
use App\Models\TipoServicio;
use App\Models\UnidadMedida;
use App\Services\Cotizador\EscalonResolver;
use Illuminate\Database\Eloquent\Collection;

// ---------------------------------------------------------------------------
// EscalonResolver
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->resolver = new EscalonResolver();
});

it('retorna null cuando no hay tarifas', function () {
    $resultado = $this->resolver->calcular(new Collection(), 10.0);
    expect($resultado)->toBeNull();
});

it('retorna null cuando la cantidad es cero', function () {
    $tarifa = new Tarifa(['costo_unitario' => 100, 'maximo' => null]);
    $resultado = $this->resolver->calcular(new Collection([$tarifa]), 0);
    expect($resultado)->toBeNull();
});

it('calcula costo unitario x cantidad', function () {
    $tarifa = new Tarifa(['costo_unitario' => 5000, 'maximo' => null]);
    $resultado = $this->resolver->calcular(new Collection([$tarifa]), 2.0);
    expect($resultado)->toBe(10000.0);
});

it('aplica tope de cobro maximo si existe', function () {
    // costo_unitario = 10000, cantidad = 5 → costo_calculado = 50000
    // maximo = 30000 → debe retornar 30000
    $tarifa = new Tarifa(['costo_unitario' => 10000, 'maximo' => 30000]);
    $resultado = $this->resolver->calcular(new Collection([$tarifa]), 5.0);
    expect($resultado)->toBe(30000.0);
});

it('no aplica maximo si el costo calculado es menor', function () {
    // costo_unitario = 1000, cantidad = 2 → costo_calculado = 2000 < maximo 5000
    $tarifa = new Tarifa(['costo_unitario' => 1000, 'maximo' => 5000]);
    $resultado = $this->resolver->calcular(new Collection([$tarifa]), 2.0);
    expect($resultado)->toBe(2000.0);
});

it('construye mapa de tarifas por unidad de medida', function () {
    $unidadM3 = new UnidadMedida(['codigo' => 'M3']);
    $unidadKg = new UnidadMedida(['codigo' => 'KG']);

    $tarifaM3 = new Tarifa(['costo_unitario' => 5000]);
    $tarifaM3->setRelation('unidadMedida', $unidadM3);

    $tarifaKg = new Tarifa(['costo_unitario' => 200]);
    $tarifaKg->setRelation('unidadMedida', $unidadKg);

    $mapa = $this->resolver->mapaTarifas(new Collection([$tarifaM3, $tarifaKg]));

    expect($mapa)->toHaveKey('M3', 5000.0)
                 ->toHaveKey('KG', 200.0);
});
