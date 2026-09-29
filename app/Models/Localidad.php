<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localidad extends Model
{
    protected $table = 'localidades';
    public $timestamps = false;

    protected $fillable = ['nombre', 'provincia_id'];

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'localidad_id');
    }
}