<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcuerdoComercial extends Model
{
    use SoftDeletes;

    protected $table = 'acuerdos_comerciales';

    protected $fillable = [
        'cliente_id', 'codigo', 'nombre', 'descuento_porcentaje',
        'recargo_porcentaje', 'vigente_desde', 'vigente_hasta',
        'observaciones', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'vigente_desde'         => 'date',
        'vigente_hasta'         => 'date',
        'descuento_porcentaje'  => 'decimal:2',
        'recargo_porcentaje'    => 'decimal:2',
    ];

    // -----------------------------------------------
    // Relaciones
    // -----------------------------------------------
    public function cliente()    { return $this->belongsTo(Cliente::class); }
    public function condiciones(){ return $this->hasMany(AcuerdoCondicion::class, 'acuerdo_id'); }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    /**
     * Indica si el seguro está incluido en el acuerdo.
     * Lee la condición SEGURO_INCLUIDO de acuerdo_condiciones.
     */
    public function tieneSeguroIncluido(): bool
    {
        $tipo = TipoCondicionComercial::where('codigo', 'SEGURO_INCLUIDO')->first();
        if (! $tipo) {
            return false;
        }

        return $this->condiciones()
            ->where('tipo_condicion_id', $tipo->id)
            ->where('valor_booleano', true)
            ->exists();
    }

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopeVigente($query, ?string $fecha = null)
    {
        $hoy = $fecha ?? now()->toDateString();
        return $query
            ->where('vigente_desde', '<=', $hoy)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy));
    }
}

