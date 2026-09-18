<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cuenta empresarial cliente de un equipo.
 *
 * @property int $id
 * @property int $team_id
 * @property string $empresa
 * @property string|null $cuit
 * @property string $nombre_contacto
 * @property string|null $apellido_contacto
 * @property string $email
 * @property string|null $telefono
 * @property string|null $direccion
 * @property string|null $notas
 * @property bool $is_active
 */
#[Fillable([
    'team_id',
    'empresa',
    'cuit',
    'nombre_contacto',
    'apellido_contacto',
    'email',
    'telefono',
    'direccion',
    'notas',
    'is_active',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the team that owns the client.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
