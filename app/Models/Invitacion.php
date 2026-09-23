<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Invitación a sumarse como usuario de una empresa.
 *
 * Se crea desde Mi Cuenta, viaja por email con un token de un solo
 * uso que vence en 7 días y al aceptarse nace el usuario atado
 * a esa empresa.
 *
 * @property int $id
 * @property int $client_id
 * @property string $email
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 */
class Invitacion extends Model
{
    protected $table = 'invitaciones';

    protected $fillable = ['client_id', 'email', 'token', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /** Empresa que invita. */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Vale si no se usó ni venció. */
    public function vigente(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
