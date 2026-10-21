<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AjusteSistema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Configuración global del sistema (solo admin). */
class AjustesSistemaController extends Controller
{
    public function index(): Response
    {
        $a = AjusteSistema::todos();

        return Inertia::render('admin/configuracion', [
            'ajustes' => [
                'admin_email'       => $a['admin_email'] ?? '',
                'admin_whatsapp'    => $a['admin_whatsapp'] ?? '',
                'smtp_host'         => $a['smtp_host'] ?? '',
                'smtp_port'         => $a['smtp_port'] ?? '',
                'smtp_username'     => $a['smtp_username'] ?? '',
                'whatsapp_phone_id' => $a['whatsapp_phone_id'] ?? '',
            ],
            // Los secretos nunca viajan al navegador: solo se informa si están cargados.
            'secretosCargados' => [
                'smtp_password'  => filled($a['smtp_password'] ?? null),
                'whatsapp_token' => filled($a['whatsapp_token'] ?? null),
                'maps_api_key'   => filled($a['maps_api_key'] ?? null),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'admin_email'       => ['nullable', 'email', 'max:150'],
            'admin_whatsapp'    => ['nullable', 'string', 'max:25', 'regex:/^[+0-9\s\-()]*$/'],
            'smtp_host'         => ['nullable', 'string', 'max:150'],
            'smtp_port'         => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username'     => ['nullable', 'string', 'max:150'],
            'smtp_password'     => ['nullable', 'string', 'max:255'],
            'whatsapp_token'    => ['nullable', 'string', 'max:500'],
            'whatsapp_phone_id' => ['nullable', 'string', 'max:60'],
            'maps_api_key'      => ['nullable', 'string', 'max:255'],
        ], [
            'admin_email.email'        => 'El correo del administrador no es válido.',
            'admin_whatsapp.regex'     => 'El WhatsApp solo puede contener números, +, espacios y guiones.',
        ]);

        foreach ($data as $clave => $valor) {
            // Secretos: vacío = conservar el valor actual.
            if (in_array($clave, AjusteSistema::SECRETOS, true) && ($valor === null || $valor === '')) {
                continue;
            }
            AjusteSistema::guardar($clave, $valor === null ? null : (string) $valor);
        }

        // Permite "borrar" un secreto explícitamente
        foreach ((array) $request->input('borrar', []) as $clave) {
            if (in_array($clave, AjusteSistema::SECRETOS, true)) {
                AjusteSistema::guardar($clave, null);
            }
        }

        return back()->with('success', 'Configuración guardada.');
    }
}
