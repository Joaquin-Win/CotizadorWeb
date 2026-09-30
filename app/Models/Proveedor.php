<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre', 'cuit', 'email', 'telefono', 'direccion', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /** Proveedor activo y no eliminado */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /** Obtiene el proveedor activo principal (SET Logística) */
    public static function principal(): ?static
    {
        return static::activo()->first();
    }
}

