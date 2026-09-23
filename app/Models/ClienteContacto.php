<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contacto de una empresa, espejo de `cliente_contactos`.
 *
 * Hay uno principal por empresa (índice único) que es quien
 * administra usuarios y datos en el portal.
 *
 * @property int $id
 * @property int $cliente_id
 * @property string $nombre
 * @property string|null $cargo
 * @property string|null $email
 * @property string|null $telefono
 * @property bool $es_principal
 */
class ClienteContacto extends Model
{
    protected $table = 'cliente_contactos';

    protected $fillable = ['cliente_id', 'nombre', 'cargo', 'email', 'telefono', 'es_principal'];

    protected function casts(): array
    {
        return ['es_principal' => 'boolean'];
    }

    /** Empresa a la que pertenece. */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'cliente_id');
    }
}
