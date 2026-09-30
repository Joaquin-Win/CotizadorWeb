<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoServicio extends Model
{
    protected $table = 'tipos_servicio';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /** Buscar por código (TRONCAL, PRIMERA_MILLA, etc.) */
    public static function porCodigo(string $codigo): ?static
    {
        return static::where('codigo', $codigo)->first();
    }
}

