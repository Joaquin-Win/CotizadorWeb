<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoCliente extends Model
{
    protected $table = 'estados_cliente';
    public $timestamps = false;

    protected $fillable = ['nombre', 'codigo'];

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'estado_id');
    }
}