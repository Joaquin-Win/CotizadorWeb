<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteIntegracion extends Model
{
    protected $table = 'cliente_integraciones';

    public function tipoIntegracion()
    {
        return $this->belongsTo(TipoIntegracion::class, 'tipo_integracion_id');
    }
}
