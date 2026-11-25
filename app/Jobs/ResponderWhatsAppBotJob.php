<?php

namespace App\Jobs;

use App\Models\MensajeWhatsapp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Responde un mensaje entrante del bot de WhatsApp: pregunta a Gemini y contesta por YCloud. */
class ResponderWhatsAppBotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1; // un reintento podría mandarle dos respuestas al cliente

    public function __construct(public int $mensajeId) {}

    public function handle(): void
    {
        $mensaje = MensajeWhatsapp::with('conversacion')->find($this->mensajeId);
        $conversacion = $mensaje?->conversacion;

        if (! $conversacion) {
            return;
        }

        if ($conversacion->estado === 'humano') {
            if ($conversacion->updated_at->gt(now()->subHours(config('whatsapp.bot.horas_modo_humano')))) {
                return; // la atiende una persona
            }
            $conversacion->update(['estado' => 'bot']);
        }

        // Gemini espera que la charla empiece con un mensaje del usuario.
        $historial = $conversacion->mensajes()->latest('id')->limit(10)->get()->reverse()
            ->skipUntil(fn (MensajeWhatsapp $m) => $m->direccion === 'entrante');
        [$respuesta, $derivar] = $this->consultarGemini($historial);

        if (! $this->enviar($conversacion->telefono, $respuesta)) {
            return;
        }

        $conversacion->mensajes()->create(['direccion' => 'saliente', 'texto' => $respuesta]);

        if ($derivar) {
            $conversacion->update(['estado' => 'humano']);
        }
    }

    /** @return array{0: string, 1: bool} respuesta y si hay que derivar a una persona */
    private function consultarGemini($historial): array
    {
        $error = [config('whatsapp.bot.mensaje_error'), true];
        $clave = config('whatsapp.bot.gemini_api_key');

        if (! $clave) {
            Log::warning('[whatsapp-bot] Falta GEMINI_API_KEY.');

            return $error;
        }

        try {
            $modelo = config('whatsapp.bot.gemini_modelo');
            $resp = Http::withHeaders(['x-goog-api-key' => $clave])
                ->timeout(20)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent", [
                    'system_instruction' => ['parts' => [['text' => file_get_contents(resource_path('prompts/whatsapp_bot.txt'))]]],
                    'contents' => $historial->map(fn (MensajeWhatsapp $m) => [
                        'role' => $m->direccion === 'entrante' ? 'user' : 'model',
                        'parts' => [['text' => $m->texto]],
                    ])->values()->all(),
                    'generationConfig' => ['responseMimeType' => 'application/json'],
                ]);

            $json = json_decode((string) $resp->json('candidates.0.content.parts.0.text'), true);

            if (! $resp->successful() || ! is_array($json) || ! is_string($json['respuesta'] ?? null) || trim($json['respuesta']) === '') {
                Log::error('[whatsapp-bot] Respuesta inválida de Gemini: '.$resp->body());

                return $error;
            }

            return [mb_strcut($json['respuesta'], 0, 4000), (bool) ($json['derivar'] ?? false)];
        } catch (Throwable $e) {
            Log::error("[whatsapp-bot] Error consultando Gemini: {$e->getMessage()}");

            return $error;
        }
    }

    private function enviar(string $telefono, string $texto): bool
    {
        $clave = config('whatsapp.bot.ycloud_api_key');
        $numero = config('whatsapp.bot.numero');

        if (! $clave || ! $numero) {
            Log::warning('[whatsapp-bot] Falta YCLOUD_API_KEY o WHATSAPP_BOT_NUMERO.');

            return false;
        }

        try {
            $resp = Http::withHeaders(['X-API-Key' => $clave])->timeout(10)->post('https://api.ycloud.com/v2/whatsapp/messages', [
                'from' => $numero,
                'to' => $telefono,
                'type' => 'text',
                'text' => ['body' => $texto],
            ]);

            if (! $resp->successful()) {
                Log::error("[whatsapp-bot] YCloud rechazó la respuesta a {$telefono}: ".$resp->body());
            }

            return $resp->successful();
        } catch (Throwable $e) {
            Log::error("[whatsapp-bot] Error enviando por YCloud a {$telefono}: {$e->getMessage()}");

            return false;
        }
    }
}
