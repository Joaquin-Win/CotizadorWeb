<?php

namespace App\Jobs;

use App\Models\AjusteSistema;
use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Envía el WhatsApp de una cotización al admin (Meta Cloud API). Independiente del correo. */
class SendCotizacionWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public Cotizacion $cotizacion,
        public float $totalFinal,
        public string $estadoCotizacion,
        public string $telefono,
    ) {}

    public function handle(): void
    {
        $codigo = $this->cotizacion->codigo;

        try {
            $token   = AjusteSistema::obtener('whatsapp_token');
            $phoneId = AjusteSistema::obtener('whatsapp_phone_id');
            $numero  = preg_replace('/\D+/', '', $this->telefono);

            if (! $token || ! $phoneId || ! $numero) {
                Log::warning("[notificaciones] WhatsApp de cotización {$codigo} omitido: falta token, Phone ID o número.");

                return;
            }

            $total = $this->estadoCotizacion === 'ATENCION_PERSONALIZADA'
                ? 'A confirmar'
                : '$' . number_format($this->totalFinal, 2, ',', '.');

            $resp = Http::withToken($token)->timeout(10)->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $numero,
                'type'              => 'text',
                'text'              => ['body' => "Nueva cotización {$codigo}. Total: {$total}. " . url("/cotizador/{$codigo}")],
            ]);

            if (! $resp->successful()) {
                Log::error("[notificaciones] WhatsApp cotización {$codigo} rechazado: " . $resp->body());
            }
        } catch (Throwable $e) {
            Log::error("[notificaciones] Error enviando WhatsApp de cotización {$codigo}: {$e->getMessage()}");
        }
    }
}
