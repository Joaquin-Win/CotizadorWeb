<?php

namespace App\Http\Requests\Cotizador;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el request para guardar una cotización ya calculada.
 *
 * Si el usuario está autenticado, los datos personales son opcionales
 * porque el controller los toma directamente del perfil/cliente del usuario.
 * Para usuarios anónimos son obligatorios.
 */
class GuardarCotizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $esAnonimo = ! auth()->check();

        return [
            // Datos personales: obligatorios solo para usuarios anónimos.
            // Para autenticados el controller los toma del perfil.
            'nombre_cliente'    => [$esAnonimo ? 'required' : 'nullable', 'string', 'max:100'],
            'apellido_cliente'  => ['nullable', 'string', 'max:100'],
            // cuit_cliente es alias del campo que envía el front para usuarios autenticados
            'cuit_cliente'      => ['nullable', 'string', 'max:30'],
            'dni_cliente'       => [$esAnonimo ? 'required' : 'nullable', 'nullable', 'regex:/^\d{7,8}$/'],
            'email_cliente'     => [$esAnonimo ? 'required' : 'nullable', 'nullable', 'email', 'max:255'],
            'telefono_cliente'  => [
                $esAnonimo ? 'required' : 'nullable',
                'nullable',
                'string',
                'max:50',
                // Acepta cualquier número argentino (con o sin +54, con o sin espacios/guiones).
                // Mínimo 7 dígitos numéricos, máximo 15 (estándar E.164).
                // La normalización para WhatsApp se realiza por separado en el seguimiento.
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! $value) {
                        return;
                    }
                    $digitos = preg_replace('/\D+/', '', $value) ?? '';
                    if (strlen($digitos) < 7 || strlen($digitos) > 15) {
                        $fail('Ingresá un teléfono válido con código de área (ej. 3482 586567 o +54 3482 586567).');
                    }
                },
            ],
            'empresa'           => ['nullable', 'string', 'max:255'],

            // Origen del request
            'origen_cotizacion_id' => ['nullable', 'integer', 'exists:origenes_cotizacion,id'],
        ];
    }
}
