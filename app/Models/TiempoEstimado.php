<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TiempoEstimado extends Model
{
    use SoftDeletes;

    protected $table = 'tiempos_estimados';

    protected $fillable = [
        'provincia_origen_id', 'provincia_destino_id',
        'localidad_origen_id', 'localidad_destino_id',
        'dias_min', 'dias_max', 'observacion',
    ];

    public function provinciaOrigen()  { return $this->belongsTo(Provincia::class, 'provincia_origen_id'); }
    public function provinciaDestino() { return $this->belongsTo(Provincia::class, 'provincia_destino_id'); }
    public function localidadOrigen()  { return $this->belongsTo(Localidad::class, 'localidad_origen_id'); }
    public function localidadDestino() { return $this->belongsTo(Localidad::class, 'localidad_destino_id'); }
}

