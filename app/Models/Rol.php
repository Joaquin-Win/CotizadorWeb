<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rol del usuario, espejo de `roles`.
 *
 * ADMIN es interno SET, CLIENTE pertenece a una empresa.
 * `es_interno` distingue ambos mundos.
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property bool $es_interno
 */
class Rol extends Model
{
    protected $table = 'roles';

    protected function casts(): array
    {
        return ['es_interno' => 'boolean'];
    }
}
