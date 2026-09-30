<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionEnvio extends Model
{
    protected $table = 'cotizacion_envios';

    protected $fillable = [
        'cotizacion_id', 'provincia_origen_id', 'localidad_origen_id',
        'provincia_destino_id', 'localidad_destino_id',
        'solicita_retiro', 'solicita_entrega', 'retira_en_sucursal',
        'valor_declarado', 'dias_almacenamiento',
    ];

    protected $casts = [
        'solicita_retiro'    => 'boolean',
        'solicita_entrega'   => 'boolean',
        'retira_en_sucursal' => 'boolean',
        'valor_declarado'    => 'decimal:2',
    ];

    public function provinciaOrigen()  { return $this->belongsTo(Provincia::class, 'provincia_origen_id'); }
    public function provinciaDestino() { return $this->belongsTo(Provincia::class, 'provincia_destino_id'); }
    public function localidadOrigen()  { return $this->belongsTo(Localidad::class, 'localidad_origen_id'); }
    public function localidadDestino() { return $this->belongsTo(Localidad::class, 'localidad_destino_id'); }
}

