<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modelo de autenticación.
 * Tabla: usuarios (del SQL cotizador_set, NO la tabla 'users' estándar de Laravel).
 *
 * Columnas notables:
 *  - rol_id          → FK a roles
 *  - cliente_id      → FK a clientes (NULL para ADMIN)
 *  - activo          → suspensión temporal sin borrar cuenta
 *  - ultimo_acceso   → timestamp
 *  - email_unico     → columna GENERADA (STORED), no fillable
 *
 * @property int         $id
 * @property int         $rol_id
 * @property int|null    $cliente_id
 * @property string      $name
 * @property string      $email
 * @property string|null $telefono
 * @property bool        $activo
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $ultimo_acceso
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Rol|null      $rol
 * @property-read Cliente|null  $cliente
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Tabla real en la BD (cotizador_set).
     */
    protected $table = 'usuarios';

    /**
     * Campos asignables masivamente.
     * email_unico es columna GENERADA por MySQL → NO incluir.
     */
    protected $fillable = [
        'rol_id',
        'cliente_id',
        'name',
        'email',
        'password',
        'telefono',
        'activo',
        'ultimo_acceso',
        'created_by',
        'updated_by',
    ];

    /**
     * Campos ocultos en serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts de atributos.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'activo'            => 'boolean',
            'ultimo_acceso'     => 'datetime',
        ];
    }

    // ---------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------

    public function rol(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function cliente(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /** @return bool */
    public function esAdmin(): bool
    {
        // rol_id = 1 → ADMIN (ver tabla roles)
        return $this->rol_id === 1;
    }

    /** @return bool */
    public function esCliente(): bool
    {
        return $this->rol_id === 2;
    }
}
