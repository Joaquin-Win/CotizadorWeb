<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoBulto extends Model
{
    // Eloquent pluralizaría a 'tipo_bultos' (incorrecto).
    // La tabla real se llama 'tipos_bulto' (prefijo plural en español).
    protected $table = 'tipos_bulto';
}
