<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteContacto extends Model
{
    protected $fillable = ['cliente_id', 'nombre', 'cargo', 'email', 'telefono', 'es_principal'];

    protected function casts(): array
    {
        return ['es_principal' => 'boolean'];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Un solo principal por empresa (índice único en BD): al marcar
     * uno se desmarcan los demás ANTES, con un query separado
     * (los triggers MySQL no pueden tocar la misma tabla).
     */
    protected static function booted(): void
    {
        $desmarcarOtros = function (ClienteContacto $contacto) {
            if ($contacto->es_principal) {
                static::where('cliente_id', $contacto->cliente_id)
                    ->where('id', '!=', $contacto->id ?? 0)
                    ->where('es_principal', true)
                    ->update(['es_principal' => false]);
            }
        };

        static::creating($desmarcarOtros);
        static::updating(function (ClienteContacto $contacto) use ($desmarcarOtros) {
            if ($contacto->isDirty('es_principal')) {
                $desmarcarOtros($contacto);
            }
        });
    }

    /** Marca este contacto como el principal de su empresa. */
    public function markAsPrincipal(): bool
    {
        return $this->update(['es_principal' => true]);
    }
}
