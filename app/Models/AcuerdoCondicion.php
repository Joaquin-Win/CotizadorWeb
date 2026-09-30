<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcuerdoCondicion extends Model
{
    protected $table = 'acuerdo_condiciones';

    protected $fillable = [
        'acuerdo_id', 'tipo_condicion_id', 'valor_numerico',
        'valor_texto', 'valor_booleano', 'zona_id', 'tipo_servicio_id', 'observacion',
    ];

    protected $casts = [
        'valor_numerico'  => 'decimal:2',
        'valor_booleano'  => 'boolean',
    ];

    public function acuerdo()      { return $this->belongsTo(AcuerdoComercial::class); }
    public function tipoCondicion(){ return $this->belongsTo(TipoCondicionComercial::class, 'tipo_condicion_id'); }
}

