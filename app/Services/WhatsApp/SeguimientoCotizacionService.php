<?php

namespace App\Services\WhatsApp;

use App\Models\Cotizacion;
use App\Models\CotizacionContactoWhatsapp;
use Illuminate\Support\Carbon;

/**
 * Seguimiento de cotizaciones pendientes por WhatsApp (modo manual wa.me).
 *
 * No envía nada por sí mismo: arma el enlace con el mensaje y registra que
 * un usuario del backoffice lo generó. El envío real lo hace la persona desde WhatsApp.
 */
class SeguimientoCotizacionService
{
    /**
     * Normaliza un teléfono argentino al formato internacional de wa.me (549XXXXXXXXXX).
     * Devuelve null si no se puede interpretar como un móvil argentino válido.
     */
    public function normalizarTelefono(?string $telefono): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $telefono) ?? '';
        $d = preg_replace('/^00/', '', $d) ?? '';

        if ($d === '') {
            return null;
        }

        $pais = (string) config('whatsapp.pais_por_defecto', '54');

        if (str_starts_with($d, $pais)) {
            $rest = substr($d, strlen($pais));
        } elseif (str_starts_with($d, '0')) {
            $rest = $d;
        } elseif (strlen($d) <= 10 || strlen($d) === 12) {
            $rest = $d; // nacional sin 0 (ej. 3513849993 o 351 15 3849993)
        } else {
            return null; // posible número de otro país: no se asume
        }

        $rest = ltrim($rest, '0');

        // Indicador de móvil "9" tras el código de país (54 9 ...)
        if (strlen($rest) === 11 && str_starts_with($rest, '9')) {
            $rest = substr($rest, 1);
        }

        // Prefijo "15" después del código de área (formato local de celulares)
        if (strlen($rest) === 12) {
            foreach ([2, 3, 4] as $area) {
                if (substr($rest, $area, 2) === '15') {
                    $rest = substr($rest, 0, $area).substr($rest, $area + 2);
                    break;
                }
            }
        }

        if (strlen($rest) !== 10 || ! ctype_digit($rest)) {
            return null;
        }

        return $pais.'9'.$rest;
    }

    /** Teléfono crudo del cliente: empresa → lead (visitante) → usuario. Primero que sea válido. */
    public function resolverContacto(Cotizacion $cotizacion): array
    {
        $cotizacion->loadMissing(['cliente', 'lead', 'usuario']);

        $candidatos = [
            [$cotizacion->cliente?->telefono, $cotizacion->cliente?->nombre_fantasia ?: $cotizacion->cliente?->razon_social],
            [$cotizacion->lead?->telefono_cliente, $cotizacion->lead?->nombre_cliente],
            [$cotizacion->usuario?->telefono, $cotizacion->usuario?->name],
        ];

        $nombreFallback = null;
        foreach ($candidatos as [$tel, $nombre]) {
            $nombreFallback ??= $nombre;
            $normalizado = $this->normalizarTelefono($tel);
            if ($normalizado !== null) {
                return ['telefono' => $normalizado, 'nombre' => $nombre ?: $nombreFallback];
            }
        }

        return ['telefono' => null, 'nombre' => $nombreFallback];
    }

    public function esEstadoSeguible(Cotizacion $cotizacion): bool
    {
        $cotizacion->loadMissing('estado');

        return $cotizacion->estado !== null
            && ! $cotizacion->estado->es_final
            && in_array($cotizacion->estado->codigo, config('whatsapp.estados_seguimiento', []), true);
    }

    public function ultimoContacto(Cotizacion $cotizacion): ?CotizacionContactoWhatsapp
    {
        return CotizacionContactoWhatsapp::where('cotizacion_id', $cotizacion->id)->latest('created_at')->latest('id')->first();
    }

    public function dentroDeVentanaAntiDuplicado(?CotizacionContactoWhatsapp $contacto): bool
    {
        if (! $contacto) {
            return false;
        }

        return $contacto->created_at->gt(Carbon::now()->subHours((int) config('whatsapp.horas_entre_contactos', 24)));
    }

    public function construirMensaje(Cotizacion $cotizacion, ?string $nombre, float $total): string
    {
        $importe = '$ '.number_format($total, 2, ',', '.');

        return strtr((string) config('whatsapp.mensaje_seguimiento'), [
            '{nombre}' => $nombre ?: 'cliente',
            '{codigo}' => $cotizacion->codigo,
            '{importe}' => $importe,
            '{enlace}' => route('cotizador.resultado', ['codigo' => $cotizacion->codigo]),
        ]);
    }

    public function construirEnlace(string $telefono, string $mensaje): string
    {
        return 'https://wa.me/'.$telefono.'?text='.rawurlencode($mensaje);
    }

    /**
     * Estado de seguimiento para el listado:
     * pendiente | contactado | sin_telefono | no_aplica
     */
    public function estadoSeguimiento(Cotizacion $cotizacion, ?string $telefono, ?CotizacionContactoWhatsapp $ultimo): string
    {
        if (! $this->esEstadoSeguible($cotizacion)) {
            return 'no_aplica';
        }
        if ($telefono === null) {
            return 'sin_telefono';
        }

        return $ultimo ? 'contactado' : 'pendiente';
    }

    /**
     * Genera el enlace y registra el contacto. Lanza \DomainException con mensaje claro si no corresponde.
     *
     * @return array{url:string, telefono:string, mensaje:string}
     */
    public function generarContacto(Cotizacion $cotizacion, ?int $usuarioId, bool $forzar = false): array
    {
        if (! $this->esEstadoSeguible($cotizacion)) {
            throw new \DomainException('La cotización ya no está pendiente de confirmación (confirmada, cancelada o finalizada).');
        }

        $contacto = $this->resolverContacto($cotizacion);
        if ($contacto['telefono'] === null) {
            throw new \DomainException('El cliente no tiene un teléfono válido registrado.');
        }

        $ultimo = $this->ultimoContacto($cotizacion);
        if (! $forzar && $this->dentroDeVentanaAntiDuplicado($ultimo)) {
            throw new \DomainException('Ya se contactó a este cliente el '.$ultimo->created_at->format('d/m/Y H:i').'. Confirmá para volver a contactarlo.');
        }

        $total = (float) ($cotizacion->resultadoActual()?->total_final ?? 0);
        $mensaje = $this->construirMensaje($cotizacion, $contacto['nombre'], $total);

        CotizacionContactoWhatsapp::create([
            'cotizacion_id' => $cotizacion->id,
            'usuario_id' => $usuarioId,
            'telefono' => $contacto['telefono'],
            'mensaje' => $mensaje,
            'canal' => 'wa_me',
            'created_at' => now(),
        ]);

        return [
            'url' => $this->construirEnlace($contacto['telefono'], $mensaje),
            'telefono' => $contacto['telefono'],
            'mensaje' => $mensaje,
        ];
    }
}
