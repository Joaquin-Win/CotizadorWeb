<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provincia extends Model
{
    use SoftDeletes;

    protected $table = 'provincias';

    protected $fillable = [
        'nombre',
        'codigo_georef',
        'tiene_deposito',
        'activo',
    ];

    protected $casts = [
        'activo'         => 'boolean',
        'tiene_deposito' => 'boolean',
    ];

    public function localidades()
    {
        return $this->hasMany(Localidad::class, 'provincia_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
}
