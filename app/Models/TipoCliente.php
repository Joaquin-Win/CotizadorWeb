<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de cliente, espejo de `tipos_cliente`.
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 */
class TipoCliente extends Model
{
    protected $table = 'tipos_cliente';
}
