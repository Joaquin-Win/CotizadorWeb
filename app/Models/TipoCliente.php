<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCliente extends Model
{
    protected $table = 'tipos_cliente';
    public $timestamps = false;

    protected $fillable = ['nombre', 'codigo'];

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'tipo_cliente_id');
    }
}