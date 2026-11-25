<?php

namespace App\Http\Controllers;

use App\Jobs\ResponderWhatsAppBotJob;
use App\Models\ConversacionWhatsapp;
use App\Models\MensajeWhatsapp;
use App\Services\WhatsApp\YCloudFirma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Webhook de YCloud para el bot de WhatsApp. Guarda el mensaje, encola la respuesta y contesta 200 rápido
 * (YCloud reintenta si no respondemos 2xx).
 */
class WhatsAppBotWebhookController extends Controller
{
    public function __invoke(Request $request, YCloudFirma $firma): JsonResponse
    {
        abort_unless($firma->valida($request->getContent(), $request->header('YCloud-Signature')), 403);

        match ($request->input('type')) {
            'whatsapp.inbound_message.received' => $this->recibir((array) $request->input('whatsappInboundMessage', [])),
            'whatsapp.smb.message.echoes' => $this->ecoDesdeLaApp((array) $request->input('whatsappMessage', [])),
            default => null,
        };

        return response()->json(['ok' => true]);
    }

    private function recibir(array $mensaje): void
    {
        $texto = data_get($mensaje, 'text.body');
        $telefono = $this->normalizar(data_get($mensaje, 'from'));
        $externoId = data_get($mensaje, 'wamid') ?? data_get($mensaje, 'id');

        if (data_get($mensaje, 'type') !== 'text' || ! is_string($texto) || trim($texto) === '' || ! $telefono || ! $externoId) {
            return; // audios, imágenes, etc.: el bot mínimo solo responde texto
        }

        $conversacion = ConversacionWhatsapp::firstOrCreate(['telefono' => $telefono]);

        $guardado = MensajeWhatsapp::createOrFirst(['externo_id' => $externoId], [
            'conversacion_whatsapp_id' => $conversacion->id,
            'direccion' => 'entrante',
            'texto' => $texto,
        ]);

        if ($guardado->wasRecentlyCreated) {
            ResponderWhatsAppBotJob::dispatch($guardado->id);
        }
    }

    /** Una persona escribió desde la app del celular: el bot se calla en esa conversación. */
    private function ecoDesdeLaApp(array $mensaje): void
    {
        $telefono = $this->normalizar(data_get($mensaje, 'to'));

        if (! $telefono) {
            return;
        }

        $conversacion = ConversacionWhatsapp::firstOrCreate(['telefono' => $telefono]);
        $conversacion->estado = 'humano';
        $conversacion->touch(); // touch guarda el estado y reinicia el plazo de modo humano

        $texto = data_get($mensaje, 'text.body');
        $externoId = data_get($mensaje, 'wamid') ?? data_get($mensaje, 'id');
        if (is_string($texto) && $texto !== '' && $externoId) {
            MensajeWhatsapp::createOrFirst(['externo_id' => $externoId], [
                'conversacion_whatsapp_id' => $conversacion->id,
                'direccion' => 'saliente',
                'texto' => $texto,
            ]);
        }
    }

    private function normalizar(mixed $telefono): ?string
    {
        $digitos = is_string($telefono) ? preg_replace('/\D+/', '', $telefono) : '';

        return $digitos !== '' ? '+'.$digitos : null;
    }
}
