<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Estado comercial del cliente, espejo de `estados_cliente`.
 *
 * `permite_operar` dice si la empresa puede operar (activa o
 * prospecto) o no (morosa, suspendida, inactiva).
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property bool $permite_operar
 */
class EstadoCliente extends Model
{
    protected $table = 'estados_cliente';

    protected function casts(): array
    {
        return ['permite_operar' => 'boolean'];
    }
}
