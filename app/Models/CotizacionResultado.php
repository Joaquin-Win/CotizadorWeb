<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionResultado extends Model
{
    protected $table = 'cotizacion_resultados';

    protected $fillable = [
        'cotizacion_id', 'numero_version', 'margen_id', 'margen_porcentaje',
        'costo_troncal', 'costo_primera_milla', 'costo_ultima_milla', 'costo_puerta_puerta',
        'subtotal_flete', 'costo_seguro', 'costo_carga_descarga', 'incluye_carga_descarga',
        'margen_ganancia', 'descuento_porcentaje', 'descuento_monto',
        'iva', 'total_final', 'tiempo_estimado_min', 'tiempo_estimado_max', 'version_algoritmo',
        'calculado_at',
    ];

    protected $casts = [
        'costo_troncal'        => 'decimal:2',
        'costo_primera_milla'  => 'decimal:2',
        'costo_ultima_milla'   => 'decimal:2',
        'costo_puerta_puerta'  => 'decimal:2',
        'subtotal_flete'       => 'decimal:2',
        'costo_seguro'         => 'decimal:2',
        'costo_carga_descarga' => 'decimal:2',
        'margen_ganancia'      => 'decimal:2',
        'margen_porcentaje'    => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'descuento_monto'      => 'decimal:2',
        'iva'                  => 'decimal:2',
        'total_final'          => 'decimal:2',
        'incluye_carga_descarga' => 'boolean',
        'calculado_at'         => 'datetime',
    ];

    public function margen() { return $this->belongsTo(MargenGanancia::class); }
}

