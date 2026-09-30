<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionLead extends Model
{
    protected $table = 'cotizacion_leads';

    protected $fillable = [
        'cotizacion_id', 'nombre_cliente', 'email_cliente',
        'telefono_cliente', 'empresa',
    ];
}

