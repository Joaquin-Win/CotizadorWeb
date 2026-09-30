<?php

use App\Data\Cotizador\CotizacionRequestData;
use App\Data\Cotizador\OrigenData;
use App\Data\Cotizador\DestinoData;
use App\Data\Cotizador\BultoData;
use App\Services\Cotizador\RutaResolver;

// ---------------------------------------------------------------------------
// RutaResolver — tests puramente unitarios (sin BD)
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->resolver = new RutaResolver();
});

function makeDtoConTramos(
    bool $solicitaRetiro   = false,
    bool $solicitaEntrega  = false,
    bool $retiraEnSucursal = false,
    ?int $localidadOrigenId  = null,
    ?int $localidadDestinoId = null,
): CotizacionRequestData {
    $origen              = new OrigenData();
    $origen->provinciaId = 1;
    $origen->localidadId = $localidadOrigenId;
    $origen->solicitaRetiro = $solicitaRetiro;

    $destino                  = new DestinoData();
    $destino->provinciaId     = 2;
    $destino->localidadId     = $localidadDestinoId;
    $destino->solicitaEntrega = $solicitaEntrega;
    $destino->retiroEnSucursal = $retiraEnSucursal;

    $dto         = new CotizacionRequestData();
    $dto->origen = $origen;
    $dto->destino= $destino;
    $dto->bultos = [];

    return $dto;
}

it('incluye solo TRONCAL cuando no hay retiro ni entrega', function () {
    $dto    = makeDtoConTramos();
    $tramos = $this->resolver->resolverTramos($dto);

    expect($tramos)->toBe(['TRONCAL']);
});

it('incluye PRIMERA_MILLA y TRONCAL cuando solicita retiro con localidad', function () {
    $dto    = makeDtoConTramos(solicitaRetiro: true, localidadOrigenId: 10);
    $tramos = $this->resolver->resolverTramos($dto);

    expect($tramos)->toContain('PRIMERA_MILLA')
                   ->toContain('TRONCAL');
});

it('no incluye PRIMERA_MILLA cuando solicita retiro sin localidad de origen', function () {
    $dto    = makeDtoConTramos(solicitaRetiro: true, localidadOrigenId: null);
    $tramos = $this->resolver->resolverTramos($dto);

    expect($tramos)->not->toContain('PRIMERA_MILLA')
                   ->toContain('TRONCAL');
});

it('incluye TRONCAL y ULTIMA_MILLA cuando solicita entrega', function () {
    $dto    = makeDtoConTramos(solicitaEntrega: true);
    $tramos = $this->resolver->resolverTramos($dto);

    expect($tramos)->toContain('TRONCAL')
                   ->toContain('ULTIMA_MILLA');
});

it('no incluye ULTIMA_MILLA cuando retira en sucursal', function () {
    $dto    = makeDtoConTramos(solicitaEntrega: true, retiraEnSucursal: true);
    $tramos = $this->resolver->resolverTramos($dto);

    expect($tramos)->not->toContain('ULTIMA_MILLA');
});

it('incluye los tres tramos cuando retiro y entrega con localidades', function () {
    $dto = makeDtoConTramos(
        solicitaRetiro:    true,
        solicitaEntrega:   true,
        localidadOrigenId: 10,
    );
    $tramos = $this->resolver->resolverTramos($dto);

    expect($tramos)->toContain('PRIMERA_MILLA')
                   ->toContain('TRONCAL')
                   ->toContain('ULTIMA_MILLA');
});

it('candidato a PUERTA_PUERTA cuando retiro y entrega con ambas localidades', function () {
    $dto = makeDtoConTramos(
        solicitaRetiro:    true,
        solicitaEntrega:   true,
        localidadOrigenId: 10,
        localidadDestinoId: 20,
    );

    expect($this->resolver->candidatoPuertaPuerta($dto))->toBeTrue();
});

it('no es candidato a PUERTA_PUERTA si falta la localidad destino', function () {
    $dto = makeDtoConTramos(
        solicitaRetiro:    true,
        solicitaEntrega:   true,
        localidadOrigenId: 10,
        localidadDestinoId: null,
    );

    expect($this->resolver->candidatoPuertaPuerta($dto))->toBeFalse();
});
