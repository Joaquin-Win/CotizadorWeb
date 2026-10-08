<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteContacto extends Model
{
    protected $fillable = ['cliente_id', 'nombre', 'cargo', 'email', 'telefono', 'es_principal'];

    protected function casts(): array
    {
        return ['es_principal' => 'boolean'];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
