<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Notificación persistida (tabla notifications) para el panel admin. */
class NuevaCotizacionAdmin extends Notification
{
    public function __construct(
        public string $codigo,
        public string $cliente,
        public ?int $cotizacionId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'codigo'        => $this->codigo,
            'cliente'       => $this->cliente,
            'cotizacion_id' => $this->cotizacionId,
            'url'           => "/cotizador/{$this->codigo}",
            'mensaje'       => "¡Nueva cotización recibida! (Cliente: {$this->cliente})",
        ];
    }
}
