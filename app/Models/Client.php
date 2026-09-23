<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cuenta empresarial cliente, espejo de la tabla `clientes` del dump.
 *
 * El código no inventa columnas: usa razon_social, nombre_fantasia,
 * tipo, estado y el contacto principal de `cliente_contactos`. Para
 * no reescribir el frontend, expone alias de lectura (empresa,
 * nombre_contacto, is_active) que salen de esos datos reales.
 *
 * @property int $id
 * @property int $tipo_cliente_id
 * @property int $estado_id
 * @property string $razon_social
 * @property string|null $nombre_fantasia
 * @property string $cuit
 * @property string|null $email
 * @property string|null $telefono
 * @property string|null $direccion
 * @property int|null $localidad_id
 * @property string|null $logo_url
 * @property string|null $observaciones
 */
#[Fillable([
    'tipo_cliente_id',
    'estado_id',
    'razon_social',
    'nombre_fantasia',
    'cuit',
    'email',
    'telefono',
    'direccion',
    'localidad_id',
    'logo_url',
    'observaciones',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    /** La tabla real del dump, no la `clients` del prototipo. */
    protected $table = 'clientes';

    /**
     * Contacto principal de la empresa (uno solo por índice único).
     *
     * @return HasOne<ClienteContacto, $this>
     */
    public function contactoPrincipal(): HasOne
    {
        return $this->hasOne(ClienteContacto::class, 'cliente_id')->where('es_principal', true);
    }

    /**
     * Estado comercial de la empresa (activo, moroso...).
     *
     * @return BelongsTo<EstadoCliente, $this>
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoCliente::class, 'estado_id');
    }

    /**
     * Tipo de cliente (público, B2B, premium...).
     *
     * @return BelongsTo<TipoCliente, $this>
     */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoCliente::class, 'tipo_cliente_id');
    }

    /**
     * Todos los contactos de la empresa.
     *
     * @return HasMany<ClienteContacto, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(ClienteContacto::class, 'cliente_id');
    }

    /**
     * Comprobantes de la empresa.
     *
     * @return HasMany<Documento, $this>
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'cliente_id');
    }

    /**
     * Usuarios que entran con esta empresa (contacto principal incluido).
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'cliente_id');
    }

    /**
     * Invitaciones pendientes de esta empresa.
     *
     * @return HasMany<Invitacion, $this>
     */
    public function invitaciones(): HasMany
    {
        return $this->hasMany(Invitacion::class);
    }

    /**
     * Lo que el frontend conoce como "empresa": fantasía o razón social.
     *
     * @return Attribute<string, never>
     */
    protected function empresa(): Attribute
    {
        return Attribute::get(fn () => $this->nombre_fantasia ?: $this->razon_social);
    }

    /**
     * Nombre del contacto principal para mostrar.
     *
     * @return Attribute<string, never>
     */
    protected function nombreContacto(): Attribute
    {
        return Attribute::get(fn () => $this->contactoPrincipal?->nombre ?? '—');
    }

    /**
     * Si la empresa puede operar, según su estado.
     *
     * @return Attribute<bool, never>
     */
    protected function isActive(): Attribute
    {
        return Attribute::get(fn () => (bool) $this->estado?->permite_operar);
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
