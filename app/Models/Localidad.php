<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localidad extends Model
{
    protected $table = 'localidades';
    public $timestamps = false;

    protected $fillable = ['nombre', 'provincia_id', 'codigo_postal', 'codigo_georef', 'departamento_georef', 'departamento', 'activo', 'tiene_deposito'];

    protected $casts = [
        'activo' => 'boolean',
        'tiene_deposito' => 'boolean',
    ];

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /** Localidades donde la empresa tiene depósito (y están habilitadas). */
    public function scopeConDeposito($query)
    {
        return $query->where('tiene_deposito', true)->where('activo', true)->whereNull('deleted_at');
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'localidad_id');
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }
}