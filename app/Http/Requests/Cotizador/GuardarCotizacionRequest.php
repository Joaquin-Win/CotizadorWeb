<?php

namespace App\Http\Requests\Cotizador;

use App\Services\WhatsApp\SeguimientoCotizacionService;
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
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value && app(SeguimientoCotizacionService::class)->normalizarTelefono((string) $value) === null) {
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
