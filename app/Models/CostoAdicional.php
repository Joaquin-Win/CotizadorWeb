<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostoAdicional extends Model
{
    use SoftDeletes;

    protected $table = 'costos_adicionales';

    protected $fillable = [
        'codigo', 'nombre', 'monto', 'unidad', 'descripcion', 'activo', 'created_by',
    ];

    protected $casts = [
        'monto'  => 'decimal:2',
        'activo' => 'boolean',
    ];

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------

    /** Solo costos activos y no eliminados */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
}
