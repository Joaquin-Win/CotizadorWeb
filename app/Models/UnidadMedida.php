<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    protected $table = 'unidades_medida';

    protected $fillable = ['codigo', 'nombre', 'descripcion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    /** Buscar por código (KG, M3, PALLET, BULTO…) */
    public static function porCodigo(string $codigo): ?static
    {
        return static::where('codigo', $codigo)->first();
    }
}

