<?php

namespace App\Jobs;

use App\Models\Cotizacion;
use App\Models\CotizacionLead;
use App\Notifications\CotizacionConfirmada;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/** Envía el correo de una cotización al admin. Independiente del WhatsApp. */
class SendCotizacionEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public Cotizacion $cotizacion,
        public CotizacionLead $lead,
        public float $totalFinal,
        public string $estadoCotizacion,
        public string $email,
    ) {}

    public function handle(): void
    {
        try {
            Notification::route('mail', $this->email)->notify(new CotizacionConfirmada(
                cotizacion:       $this->cotizacion,
                lead:             $this->lead,
                totalFinal:       $this->totalFinal,
                estadoCotizacion: $this->estadoCotizacion,
            ));
        } catch (Throwable $e) {
            Log::error("[notificaciones] Error enviando email de cotización {$this->cotizacion->codigo}: {$e->getMessage()}");
        }
    }
}
