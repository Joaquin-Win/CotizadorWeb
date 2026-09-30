<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TarifaMinima extends Model
{
    use SoftDeletes;

    protected $table = 'tarifas_minimas';

    protected $fillable = [
        'provincia_origen_id', 'provincia_destino_id', 'localidad_destino_id',
        'tipo_servicio_id', 'monto_minimo', 'descripcion',
    ];

    protected $casts = [
        'monto_minimo' => 'decimal:2',
    ];

    public function provinciaOrigen()  { return $this->belongsTo(Provincia::class, 'provincia_origen_id'); }
    public function provinciaDestino() { return $this->belongsTo(Provincia::class, 'provincia_destino_id'); }
    public function localidadDestino() { return $this->belongsTo(Localidad::class, 'localidad_destino_id'); }
    public function tipoServicio()     { return $this->belongsTo(TipoServicio::class); }
}

