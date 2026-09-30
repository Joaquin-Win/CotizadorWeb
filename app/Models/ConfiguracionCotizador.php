<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Parámetros de configuración del motor de cotización, editables desde el panel admin.
 *
 * Claves disponibles:
 *  - seguro_porcentaje  (NUMBER)
 *  - iva_porcentaje     (NUMBER)
 *  - algoritmo_version  (STRING, no editable)
 */
class ConfiguracionCotizador extends Model
{
    protected $table = 'configuracion_cotizador';

    protected $fillable = [
        'clave', 'valor', 'tipo', 'descripcion', 'grupo', 'editable',
    ];

    protected $casts = [
        'editable' => 'boolean',
    ];

    /**
     * Obtener el valor numérico de una clave, con fallback al config de Laravel.
     */
    public static function numero(string $clave, float $fallback = 0): float
    {
        $row = static::where('clave', $clave)->first();
        if ($row && $row->valor !== null) {
            return (float) $row->valor;
        }

        // Fallback al array de defaults del config
        return (float) config("cotizador.defaults.$clave", $fallback);
    }

    /**
     * Obtener el valor de texto de una clave.
     */
    public static function texto(string $clave, string $fallback = ''): string
    {
        $row = static::where('clave', $clave)->first();
        return $row?->valor ?? $fallback;
    }
}
