<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionCodigo extends Model
{
    protected $table = 'cotizacion_codigos';

    // PK es el código string, no autoincremental
    protected $primaryKey = 'codigo';
    public    $incrementing = false;
    protected $keyType      = 'string';

    // Solo created_at per schema real
    public $timestamps  = false;
    public $dates       = ['created_at'];

    protected $fillable = ['codigo', 'cotizacion_id'];

    protected $casts = ['created_at' => 'datetime'];
}

