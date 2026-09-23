<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Configuración editable de la integración Transoft.
 * Los valores con encriptado=true se almacenan cifrados via Laravel encrypt().
 *
 * @property int    $id
 * @property string $clave       — base_url | username | password | operation_id | webhook_secret
 * @property string|null $valor
 * @property bool   $encriptado
 * @property string|null $descripcion
 */
class TransoftConfiguracion extends Model
{
    protected $table = 'transoft_configuracion';

    protected $fillable = ['clave', 'valor', 'encriptado', 'descripcion'];

    protected $casts = [
        'encriptado' => 'boolean',
    ];

    // ---------------------------------------------------------------
    // Accessors / Mutators
    // ---------------------------------------------------------------

    /**
     * Al leer, si está encriptado lo desencripta automáticamente.
     */
    public function getValorAttribute(?string $value): ?string
    {
        if ($value !== null && $this->encriptado) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable) {
                return null; // Valor corrupto o clave cambiada
            }
        }
        return $value;
    }

    /**
     * Al escribir, si está marcado como encriptado lo cifra.
     */
    public function setValorAttribute(?string $value): void
    {
        if ($value !== null && ($this->encriptado ?? false)) {
            $this->attributes['valor'] = Crypt::encryptString($value);
        } else {
            $this->attributes['valor'] = $value;
        }
    }

    // ---------------------------------------------------------------
    // Helper estático
    // ---------------------------------------------------------------

    /**
     * Obtiene el valor de una clave de configuración.
     * @param string $clave
     * @param string $default
     * @return string
     */
    public static function obtener(string $clave, string $default = ''): string
    {
        $row = static::where('clave', $clave)->first();
        return $row?->valor ?? $default;
    }

    /**
     * Actualiza o inserta un valor de configuración.
     */
    public static function establecer(string $clave, ?string $valor): void
    {
        static::where('clave', $clave)->update(['valor' => $valor]);
    }
}
