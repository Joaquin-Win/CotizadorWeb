<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransoftEstado extends Model
{
    //protected $table = 'transoft_estados';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
        'estado_cotizacion_id',
        'es_final',
        'activo',
    ];

    protected $casts = [
        'es_final' => 'boolean',
        'activo'   => 'boolean',
    ];

    public function estadoCotizacion()
    {
        return $this->belongsTo(EstadoCotizacion::class, 'estado_cotizacion_id');
    }
}
