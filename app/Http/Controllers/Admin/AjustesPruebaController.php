<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AjusteSistema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Health checks de la configuración global: prueban las credenciales con los
 * valores del formulario (aunque no estén guardados) y, para secretos vacíos,
 * con los ya guardados. Envían mensajes reales de prueba al admin.
 */
class AjustesPruebaController extends Controller
{
    public function probar(Request $request, string $servicio): JsonResponse
    {
        try {
            return match ($servicio) {
                'smtp'     => $this->probarSmtp($request),
                'whatsapp' => $this->probarWhatsapp($request),
                'contacto' => $this->probarContacto($request),
                default    => response()->json(['ok' => false, 'mensaje' => 'Servicio desconocido.'], 404),
            };
        } catch (Throwable $e) {
            Log::error('[AjustesPrueba] Error al probar servicio', [
                'servicio' => $servicio,
                'error'    => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'mensaje' => 'Error al probar el servicio. Revisá los logs del servidor para más detalles.']);
        }
    }

    /** Valor del formulario o, si viene vacío, el guardado. */
    private function valor(Request $r, string $clave): ?string
    {
        $v = $r->input($clave);

        return ($v === null || $v === '') ? AjusteSistema::obtener($clave) : (string) $v;
    }

    private function probarSmtp(Request $r): JsonResponse
    {
        $host  = $this->valor($r, 'smtp_host');
        $email = $this->valor($r, 'admin_email');
        if (! $host) {
            return response()->json(['ok' => false, 'mensaje' => 'Falta el host SMTP.']);
        }
        if (! $email) {
            return response()->json(['ok' => false, 'mensaje' => 'Falta el correo del administrador (destino de la prueba).']);
        }

        $usuario = $this->valor($r, 'smtp_username');
        config([
            'mail.mailers.prueba' => [
                'transport' => 'smtp',
                'host'      => $host,
                'port'      => (int) ($this->valor($r, 'smtp_port') ?: 587),
                'username'  => $usuario,
                'password'  => $this->valor($r, 'smtp_password'),
                'timeout'   => 10,
            ],
        ]);

        $desde = config('mail.from.address') ?: $usuario;
        Mail::mailer('prueba')->raw(
            'Este es un correo de prueba del Cotizador. Si lo recibiste, el SMTP está bien configurado.',
            function ($m) use ($email, $desde) {
                $m->to($email)->subject('Prueba de correo - Cotizador');
                if ($desde) {
                    $m->from($desde, config('mail.from.name') ?: 'Cotizador');
                }
            }
        );

        return response()->json(['ok' => true, 'mensaje' => "Correo de prueba enviado a {$email}. Revisá la bandeja."]);
    }

    private function probarWhatsapp(Request $r): JsonResponse
    {
        $token   = $this->valor($r, 'whatsapp_token');
        $phoneId = $this->valor($r, 'whatsapp_phone_id');
        $numero  = preg_replace('/\D+/', '', (string) $this->valor($r, 'admin_whatsapp'));

        if (! $token || ! $phoneId) {
            return response()->json(['ok' => false, 'mensaje' => 'Faltan el token o el Phone ID de WhatsApp.']);
        }
        if (! $numero) {
            return response()->json(['ok' => false, 'mensaje' => 'Falta el WhatsApp del administrador (destino de la prueba).']);
        }

        $url = "https://graph.facebook.com/v20.0/{$phoneId}/messages";
        $base = ['messaging_product' => 'whatsapp', 'to' => $numero];

        $resp = Http::withToken($token)->timeout(10)->post($url, $base + [
            'type' => 'text',
            'text' => ['body' => 'Prueba de notificaciones del Cotizador: si lees esto, WhatsApp está bien configurado.'],
        ]);

        // Fuera de la ventana de 24 h solo se permiten plantillas: se reintenta con "hello_world".
        if (! $resp->successful() && (int) data_get($resp->json(), 'error.code') === 131047) {
            $resp = Http::withToken($token)->timeout(10)->post($url, $base + [
                'type'     => 'template',
                'template' => ['name' => 'hello_world', 'language' => ['code' => 'en_US']],
            ]);
        }

        if ($resp->successful()) {
            return response()->json(['ok' => true, 'mensaje' => "WhatsApp de prueba enviado a +{$numero}."]);
        }

        return response()->json([
            'ok' => false,
            'mensaje' => 'WhatsApp rechazó la solicitud: ' . (data_get($resp->json(), 'error.message') ?? $resp->status()),
        ]);
    }

    /** Dispara correo + WhatsApp al admin con los datos actuales del formulario. */
    private function probarContacto(Request $r): JsonResponse
    {
        $hayEmail = filled($this->valor($r, 'admin_email'));
        $hayWa    = filled(preg_replace('/\D+/', '', (string) $this->valor($r, 'admin_whatsapp')));

        if (! $hayEmail && ! $hayWa) {
            return response()->json(['ok' => false, 'mensaje' => 'No hay ningún método de notificación configurado: cargá un correo o un WhatsApp.']);
        }

        $partes = [];
        $ok     = true;

        foreach ([['Correo', $hayEmail, 'probarSmtp'], ['WhatsApp', $hayWa, 'probarWhatsapp']] as [$nombre, $hay, $metodo]) {
            if (! $hay) {
                $partes[] = "{$nombre}: sin configurar (omitido)";
                continue;
            }
            try {
                $res = $this->{$metodo}($r)->getData(true);
            } catch (Throwable $e) {
                Log::error('[AjustesPrueba] Error en probarContacto', [
                    'metodo' => $metodo,
                    'error'  => $e->getMessage(),
                ]);
                $res = ['ok' => false, 'mensaje' => "Error al probar {$nombre}. Revisá los logs del servidor."];
            }
            $ok &= (bool) $res['ok'];
            $partes[] = "{$nombre}: {$res['mensaje']}";
        }

        return response()->json(['ok' => (bool) $ok, 'mensaje' => implode(' | ', $partes)]);
    }
}
