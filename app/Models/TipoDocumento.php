<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de comprobante, espejo de `tipos_documento`.
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 */
class TipoDocumento extends Model
{
    protected $table = 'tipos_documento';
}
