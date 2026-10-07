<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localidad extends Model
{
    protected $table = 'localidades';
    public $timestamps = false;

    protected $fillable = ['nombre', 'provincia_id', 'codigo_postal', 'codigo_georef', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
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