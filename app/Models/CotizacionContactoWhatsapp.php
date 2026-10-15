<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionContactoWhatsapp extends Model
{
    protected $table = 'cotizacion_contactos_whatsapp';

    public $timestamps = false;

    protected $fillable = ['cotizacion_id', 'usuario_id', 'telefono', 'mensaje', 'canal', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
