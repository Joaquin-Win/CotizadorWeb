<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Margen de ganancia aplicado sobre el costo de flete.
 * porcentaje = 30 → precio = costo × 1.30
 * Resolución: más específico gana (tipo_cliente + tipo_servicio > tipo_cliente > global)
 */
class MargenGanancia extends Model
{
    use SoftDeletes;

    protected $table = 'margenes_ganancia';

    protected $fillable = [
        'tipo_cliente_id', 'tipo_servicio_id',
        'porcentaje', 'vigente_desde', 'vigente_hasta',
        'motivo', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'porcentaje'    => 'decimal:2',
    ];

    // -----------------------------------------------
    // Relaciones
    // -----------------------------------------------
    public function tipoCliente() { return $this->belongsTo(TipoCliente::class); }
    public function tipoServicio(){ return $this->belongsTo(TipoServicio::class); }

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    public function scopeVigente($query, ?\DateTimeInterface $fecha = null)
    {
        $f = $fecha ?? now()->toDateString();
        return $query->where('vigente_desde', '<=', $f)
                     ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $f));
    }

    /**
     * Obtiene el margen más específico vigente para un segmento de cliente y servicio.
     * Prioridad: (tipo_cliente + tipo_servicio) > (solo tipo_cliente) > (global)
     */
    public static function paraSegmento(int $tipoClienteId, ?int $tipoServicioId = null): ?static
    {
        return static::vigente()
            ->orderByRaw('
                CASE
                    WHEN tipo_cliente_id IS NOT NULL AND tipo_servicio_id IS NOT NULL THEN 1
                    WHEN tipo_cliente_id IS NOT NULL AND tipo_servicio_id IS NULL THEN 2
                    ELSE 3
                END ASC
            ')
            ->where(function ($q) use ($tipoClienteId, $tipoServicioId) {
                // Global
                $q->whereNull('tipo_cliente_id')
                  // Solo por segmento
                  ->orWhere(fn ($q2) => $q2->where('tipo_cliente_id', $tipoClienteId)->whereNull('tipo_servicio_id'))
                  // Segmento + servicio
                  ->orWhere(fn ($q2) => $q2->where('tipo_cliente_id', $tipoClienteId)->where('tipo_servicio_id', $tipoServicioId));
            })
            ->first();
    }
}
