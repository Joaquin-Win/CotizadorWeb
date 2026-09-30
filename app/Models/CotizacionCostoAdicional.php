<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionCostoAdicional extends Model
{
    protected $table = 'cotizacion_costos_adicionales';

    // Solo created_at; sin updated_at per schema real
    public $timestamps    = false;
    public $dates         = ['created_at'];

    protected $fillable = [
        'cotizacion_id', 'costo_adicional_id',
        'nombre_snapshot', 'unidad_snapshot',
        'monto_aplicado',
    ];

    protected $casts = [
        'monto_aplicado' => 'decimal:2',
        'created_at'     => 'datetime',
    ];

    public function costoAdicional() { return $this->belongsTo(CostoAdicional::class); }
}

