<?php

namespace App\Http\Requests\Cotizador;

use App\Services\WhatsApp\SeguimientoCotizacionService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida el request para guardar una cotización ya calculada.
 *
 * authorize() = true para cotizadores públicos.
 * La cotización se identifica por el codigo generado por el motor.
 */
class GuardarCotizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // El mismo payload del cotizar, más los datos de contacto para el lead
            // Datos personales de quien cotiza (se guardan como lead)
            'nombre_cliente'    => ['required', 'string', 'max:100'],
            'apellido_cliente'  => ['required', 'string', 'max:100'],
            'dni_cliente'       => ['required', 'regex:/^\d{7,8}$/'],
            'email_cliente'     => ['required', 'email', 'max:255'],
            'telefono_cliente'  => [
                'required', 'string', 'max:50',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (app(SeguimientoCotizacionService::class)->normalizarTelefono((string) $value) === null) {
                        $fail('Ingresá un teléfono celular argentino válido (ej. 351 123 4567).');
                    }
                },
            ],
            'empresa'           => ['nullable', 'string', 'max:255'],

            // Origen del request
            'origen_cotizacion_id' => ['nullable', 'integer', 'exists:origenes_cotizacion,id'],
        ];
    }
}
