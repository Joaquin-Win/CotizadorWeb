<?php

namespace App\Http\Requests\Cotizador;

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
            'nombre_cliente'    => ['nullable', 'string', 'max:255'],
            'email_cliente'     => ['nullable', 'email', 'max:255'],
            'telefono_cliente'  => ['nullable', 'string', 'max:50'],
            'empresa'           => ['nullable', 'string', 'max:255'],

            // Origen del request
            'origen_cotizacion_id' => ['nullable', 'integer', 'exists:origenes_cotizacion,id'],
        ];
    }
}
