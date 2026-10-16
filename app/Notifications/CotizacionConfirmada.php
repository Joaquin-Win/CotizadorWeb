<?php

namespace App\Notifications;

use App\Models\Cotizacion;
use App\Models\CotizacionLead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificacion disparada cuando el cliente confirma y envia su cotizacion.
 *
 * Canales activos:
 *   - mail      => Correo al cliente/lead con el resumen y codigo
 *   - whatsapp  => Mensaje via servicio WhatsApp (App\Services\WhatsApp)
 *
 * Para encolar las notificaciones (recomendado en produccion):
 *   implements ShouldQueue  <- descomenta y configura QUEUE_CONNECTION en .env
 */
class CotizacionConfirmada extends Notification /* implements ShouldQueue */
{
    use Queueable;

    public function __construct(
        public readonly Cotizacion     $cotizacion,
        public readonly CotizacionLead $lead,
        public readonly float          $totalFinal,
        public readonly string         $estadoCotizacion, // 'CALCULADO' | 'ATENCION_PERSONALIZADA'
    ) {}

    // ---------------------------------------------------------------
    // Canales
    // ---------------------------------------------------------------

    public function via(object $notifiable): array
    {
        return ['mail']; // Agrega 'whatsapp' cuando este configurado
    }

    // ---------------------------------------------------------------
    // Canal: Mail
    // ---------------------------------------------------------------

    public function toMail(object $notifiable): MailMessage
    {
        $codigo     = $this->cotizacion->codigo;
        $esRevision = $this->estadoCotizacion === 'ATENCION_PERSONALIZADA';

        $mail = (new MailMessage())
            ->subject($esRevision
                ? "Cotizacion {$codigo} - Recibida, en revision"
                : "Cotizacion {$codigo} - Recibida con exito!")
            ->greeting("Hola, {$this->lead->nombre_cliente}!")
            ->line($esRevision
                ? 'Recibimos tu solicitud de cotizacion. Nuestro equipo la revisara y te contactara a la brevedad con el precio final.'
                : 'Tu cotizacion fue generada exitosamente. A continuacion encontras el resumen:')
            ->line("Codigo de referencia: {$codigo}")
            ->line('Estado: ' . ($esRevision ? 'En revision por nuestro equipo' : 'Cotizacion calculada'))
            ->line('Total estimado: ' . ($esRevision ? 'A confirmar' : '$' . number_format($this->totalFinal, 2, ',', '.')))
            ->line("Email: {$this->lead->email_cliente}")
            ->line("Telefono: {$this->lead->telefono_cliente}");

        if (! $esRevision) {
            $mail->action('Ver cotizacion', url("/cotizador/{$codigo}"));
        }

        return $mail
            ->line('Si tenes alguna pregunta, no dudes en contactarnos.')
            ->salutation('Equipo SET Logistica');
    }

    // ---------------------------------------------------------------
    // Canal: WhatsApp (implementacion generica)
    // Conectar con Twilio o Meta Cloud API en
    // App\Services\WhatsApp\WhatsAppSender cuando este disponible.
    // ---------------------------------------------------------------

    public function toWhatsApp(object $notifiable): array
    {
        $codigo     = $this->cotizacion->codigo;
        $esRevision = $this->estadoCotizacion === 'ATENCION_PERSONALIZADA';

        $cuerpo = $esRevision
            ? "Hola {$this->lead->nombre_cliente},\n\nRecibimos tu solicitud de cotizacion ({$codigo}). La estamos revisando y te contactaremos con el precio final a la brevedad.\n\nEquipo SET Logistica"
            : "Hola {$this->lead->nombre_cliente},\n\nTu cotizacion {$codigo} fue generada.\nTotal estimado: $" . number_format($this->totalFinal, 2, ',', '.') . "\n\nVer cotizacion: " . url("/cotizador/{$codigo}") . "\n\nEquipo SET Logistica";

        return [
            'to'   => $this->lead->telefono_cliente,
            'body' => $cuerpo,
        ];
    }
}
