<?php

use App\Models\ConversacionWhatsapp;
use App\Models\MensajeWhatsapp;
use App\Services\WhatsApp\YCloudFirma;
use Illuminate\Support\Facades\Http;

const CLIENTE = '+5493510000000';

beforeEach(function () {
    config()->set('whatsapp.bot.ycloud_api_key', 'ycloud-test');
    config()->set('whatsapp.bot.numero', '+5493512340859');
    config()->set('whatsapp.bot.gemini_api_key', 'gemini-test');
});

function firmaValida(): void
{
    test()->mock(YCloudFirma::class, fn ($m) => $m->shouldReceive('valida')->andReturn(true));
}

function geminiResponde(string $respuesta, bool $derivar = false): void
{
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [
            ['text' => json_encode(['respuesta' => $respuesta, 'derivar' => $derivar, 'motivo' => ''])],
        ]]]]]),
        'api.ycloud.com/*' => Http::response(['data' => ['id' => 'out-1']]),
    ]);
}

function mensajeEntrante(string $texto, string $id = 'wamid.1'): array
{
    return [
        'id' => 'evt-'.$id,
        'type' => 'whatsapp.inbound_message.received',
        'apiVersion' => 'v2',
        'whatsappInboundMessage' => [
            'id' => 'yc-'.$id,
            'wamid' => $id,
            'from' => CLIENTE,
            'to' => '+5493512340859',
            'type' => 'text',
            'text' => ['body' => $texto],
        ],
    ];
}

test('rechaza webhooks sin firma de YCloud', function () {
    $this->postJson('/webhooks/ycloud/whatsapp', mensajeEntrante('hola'))->assertForbidden();

    expect(MensajeWhatsapp::count())->toBe(0);
});

test('responde un mensaje con lo que dice Gemini y lo envía por YCloud', function () {
    firmaValida();
    geminiResponde('Hola, soy SET Asistente.');

    $this->postJson('/webhooks/ycloud/whatsapp', mensajeEntrante('hola'))->assertOk();

    Http::assertSent(fn ($r) => str_contains($r->url(), 'api.ycloud.com/v2/whatsapp/messages')
        && $r['from'] === '+5493512340859'
        && $r['to'] === CLIENTE
        && $r->hasHeader('X-API-Key', 'ycloud-test')
        && $r['text']['body'] === 'Hola, soy SET Asistente.');

    $conversacion = ConversacionWhatsapp::where('telefono', CLIENTE)->first();
    expect($conversacion->estado)->toBe('bot')
        ->and($conversacion->mensajes()->pluck('direccion')->all())->toBe(['entrante', 'saliente']);
});

test('un mensaje repetido por reintento de YCloud se responde una sola vez', function () {
    firmaValida();
    geminiResponde('Hola');

    $this->postJson('/webhooks/ycloud/whatsapp', mensajeEntrante('hola'))->assertOk();
    $this->postJson('/webhooks/ycloud/whatsapp', mensajeEntrante('hola'))->assertOk();

    Http::assertSentCount(2); // una llamada a Gemini y un envío
});

test('si Gemini pide derivar, el bot deja de responder en esa conversación', function () {
    firmaValida();
    geminiResponde('Una persona del equipo te va a responder por acá.', derivar: true);

    $this->postJson('/webhooks/ycloud/whatsapp', mensajeEntrante('quiero hacer un reclamo', 'wamid.1'))->assertOk();
    expect(ConversacionWhatsapp::first()->estado)->toBe('humano');

    $this->postJson('/webhooks/ycloud/whatsapp', mensajeEntrante('hola?', 'wamid.2'))->assertOk();
    Http::assertSentCount(2); // solo el primer mensaje generó llamadas
});

test('si una persona escribe desde la app, el bot se calla en esa conversación', function () {
    firmaValida();
    geminiResponde('no debería enviarse');

    $this->postJson('/webhooks/ycloud/whatsapp', [
        'id' => 'evt-eco',
        'type' => 'whatsapp.smb.message.echoes',
        'whatsappMessage' => [
            'wamid' => 'wamid.eco',
            'from' => '+5493512340859',
            'to' => CLIENTE,
            'type' => 'text',
            'text' => ['body' => 'Te atiendo yo'],
        ],
    ])->assertOk();

    $this->postJson('/webhooks/ycloud/whatsapp', mensajeEntrante('gracias', 'wamid.3'))->assertOk();

    expect(ConversacionWhatsapp::first()->estado)->toBe('humano');
    Http::assertNothingSent();
});

test('si Gemini falla, avisa al cliente y deriva a una persona', function () {
    firmaValida();
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response('error', 500),
        'api.ycloud.com/*' => Http::response(['data' => ['id' => 'out-1']]),
    ]);

    $this->postJson('/webhooks/ycloud/whatsapp', mensajeEntrante('hola'))->assertOk();

    Http::assertSent(fn ($r) => str_contains($r->url(), 'api.ycloud.com')
        && $r['text']['body'] === config('whatsapp.bot.mensaje_error'));
    expect(ConversacionWhatsapp::first()->estado)->toBe('humano');
});

test('la firma acepta solo el HMAC correcto y reciente de YCloud', function () {
    config()->set('whatsapp.bot.ycloud_webhook_secret', 'whsec_test');
    $firma = new YCloudFirma;
    $cuerpo = '{"type":"x"}';
    $t = (string) time();
    $hmac = fn (string $t) => hash_hmac('sha256', $t.'.'.$cuerpo, 'whsec_test');

    expect($firma->valida($cuerpo, "t={$t},s={$hmac($t)}"))->toBeTrue()
        ->and($firma->valida($cuerpo.' ', "t={$t},s={$hmac($t)}"))->toBeFalse()
        ->and($firma->valida($cuerpo, "t={$t},s=".str_repeat('0', 64)))->toBeFalse()
        ->and($firma->valida($cuerpo, null))->toBeFalse();

    $viejo = (string) (time() - 3600);
    expect($firma->valida($cuerpo, "t={$viejo},s={$hmac($viejo)}"))->toBeFalse();
});
