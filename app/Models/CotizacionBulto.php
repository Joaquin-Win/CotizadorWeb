<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionBulto extends Model
{
    protected $table = 'cotizacion_bultos';

    protected $fillable = [
        'cotizacion_id', 'tipo_bulto_id', 'largo_cm', 'ancho_cm', 'alto_cm',
        'peso_kg', 'cantidad', 'palletizado', 'pallets_equivalentes', 'costo_individual',
    ];

    protected $casts = [
        'largo_cm'             => 'decimal:2',
        'ancho_cm'             => 'decimal:2',
        'alto_cm'              => 'decimal:2',
        'peso_kg'              => 'decimal:2',
        // volumen_m3 y peso_total_kg son GENERATED STORED — no se insertan, sí se leen
        'volumen_m3'           => 'decimal:4',
        'peso_total_kg'        => 'decimal:4',
        'pallets_equivalentes' => 'decimal:2',
        'costo_individual'     => 'decimal:2',
        'palletizado'          => 'boolean',
    ];

    public function tipoBulto() { return $this->belongsTo(TipoBulto::class); }
}

