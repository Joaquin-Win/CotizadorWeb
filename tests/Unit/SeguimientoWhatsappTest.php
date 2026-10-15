<?php

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\CotizacionContactoWhatsapp;
use App\Models\CotizacionLead;
use App\Models\EstadoCotizacion;
use App\Services\WhatsApp\SeguimientoCotizacionService;
use Illuminate\Support\Carbon;

// Pruebas sin base de datos: usan modelos en memoria.

function svc(): SeguimientoCotizacionService
{
    return new SeguimientoCotizacionService;
}

function cotizacionEn(string $codigoEstado, bool $final = false, array $attrs = []): Cotizacion
{
    $c = new Cotizacion(['codigo' => 'SET-2026-ABC123'] + $attrs);
    $c->id = 10;
    $estado = new EstadoCotizacion;
    $estado->codigo = $codigoEstado;
    $estado->es_final = $final;
    $c->setRelation('estado', $estado);

    return $c;
}

test('normaliza telefonos argentinos a formato wa.me', function (?string $entrada, ?string $esperado) {
    expect(svc()->normalizarTelefono($entrada))->toBe($esperado);
})->with([
    ['+54 9 351 384-9993', '5493513849993'],
    ['5493513849993', '5493513849993'],
    ['0351 15 384 9993', '5493513849993'],
    ['351 384 9993', '5493513849993'],
    ['011 15 5555-1234', '5491155551234'],
    ['(11) 5555-1234', '5491155551234'],
    ['54 351 384 9993', '5493513849993'],
    ['', null],
    [null, null],
    ['123', null],
    ['+1 305 555 0100', null],
]);

test('el mensaje incluye nombre, codigo, importe y enlace', function () {
    $c = cotizacionEn('PENDIENTE_CONFIRMACION');
    $msg = svc()->construirMensaje($c, 'Juan', 12345.5);

    expect($msg)->toContain('Juan')
        ->toContain('SET-2026-ABC123')
        ->toContain('$ 12.345,50')
        ->toContain('/cotizador/SET-2026-ABC123');
});

test('el enlace wa.me contiene telefono y mensaje codificado', function () {
    $url = svc()->construirEnlace('5493513849993', 'Hola, ¿qué tal?');

    expect($url)->toStartWith('https://wa.me/5493513849993?text=')
        ->and(urldecode(explode('text=', $url)[1]))->toBe('Hola, ¿qué tal?');
});

test('solo se ofrecen para seguimiento las cotizaciones pendientes', function () {
    expect(svc()->esEstadoSeguible(cotizacionEn('PENDIENTE_CONFIRMACION')))->toBeTrue()
        ->and(svc()->esEstadoSeguible(cotizacionEn('ENVIADA')))->toBeTrue()
        ->and(svc()->esEstadoSeguible(cotizacionEn('ACEPTADA', true)))->toBeFalse()
        ->and(svc()->esEstadoSeguible(cotizacionEn('RECHAZADA', true)))->toBeFalse()
        ->and(svc()->esEstadoSeguible(cotizacionEn('VENCIDA', true)))->toBeFalse()
        ->and(svc()->esEstadoSeguible(cotizacionEn('BORRADOR')))->toBeFalse();
});

test('resuelve telefono del cliente y cae al lead si el del cliente es invalido', function () {
    $c = cotizacionEn('PENDIENTE_CONFIRMACION');
    $c->setRelation('cliente', new Cliente(['razon_social' => 'ACME', 'telefono' => 'sin telefono']));
    $c->setRelation('lead', new CotizacionLead(['nombre_cliente' => 'Ana', 'telefono_cliente' => '351 384 9993']));
    $c->setRelation('usuario', null);

    $r = svc()->resolverContacto($c);

    expect($r['telefono'])->toBe('5493513849993');
});

test('sin telefono valido el estado de seguimiento es sin_telefono', function () {
    $c = cotizacionEn('PENDIENTE_CONFIRMACION');
    $c->setRelation('cliente', new Cliente(['razon_social' => 'ACME', 'telefono' => null]));
    $c->setRelation('lead', null);
    $c->setRelation('usuario', null);

    $r = svc()->resolverContacto($c);

    expect($r['telefono'])->toBeNull()
        ->and(svc()->estadoSeguimiento($c, $r['telefono'], null))->toBe('sin_telefono');
});

test('estado de seguimiento: pendiente, contactado y no_aplica', function () {
    $pend = cotizacionEn('PENDIENTE_CONFIRMACION');
    $fin = cotizacionEn('ACEPTADA', true);
    $contacto = new CotizacionContactoWhatsapp(['created_at' => now()]);

    expect(svc()->estadoSeguimiento($pend, '5493513849993', null))->toBe('pendiente')
        ->and(svc()->estadoSeguimiento($pend, '5493513849993', $contacto))->toBe('contactado')
        ->and(svc()->estadoSeguimiento($fin, '5493513849993', null))->toBe('no_aplica');
});

test('ventana anti duplicados', function () {
    $reciente = new CotizacionContactoWhatsapp;
    $reciente->created_at = Carbon::now()->subHours(2);
    $viejo = new CotizacionContactoWhatsapp;
    $viejo->created_at = Carbon::now()->subHours(48);

    expect(svc()->dentroDeVentanaAntiDuplicado($reciente))->toBeTrue()
        ->and(svc()->dentroDeVentanaAntiDuplicado($viejo))->toBeFalse()
        ->and(svc()->dentroDeVentanaAntiDuplicado(null))->toBeFalse();
});

test('generarContacto rechaza cotizaciones confirmadas sin tocar la base', function () {
    svc()->generarContacto(cotizacionEn('ACEPTADA', true), 1);
})->throws(DomainException::class, 'ya no está pendiente');
