<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tarifa de flete por ruta, servicio y unidad de medida.
 * maximo = NULL → escalón abierto (sin tope).
 */
class Tarifa extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'proveedor_id', 'provincia_origen_id', 'provincia_destino_id',
        'localidad_destino_id', 'tipo_servicio_id', 'unidad_medida_id',
        'costo_unitario', 'maximo', 'vigente_desde', 'vigente_hasta',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'costo_unitario'=> 'decimal:2',
        'maximo'        => 'decimal:2',
    ];

    // -----------------------------------------------
    // Relaciones
    // -----------------------------------------------
    public function proveedor()          { return $this->belongsTo(Proveedor::class); }
    public function provinciaOrigen()    { return $this->belongsTo(Provincia::class, 'provincia_origen_id'); }
    public function provinciaDestino()   { return $this->belongsTo(Provincia::class, 'provincia_destino_id'); }
    public function localidadDestino()   { return $this->belongsTo(Localidad::class, 'localidad_destino_id'); }
    public function tipoServicio()       { return $this->belongsTo(TipoServicio::class); }
    public function unidadMedida()       { return $this->belongsTo(UnidadMedida::class); }

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    /** Tarifas vigentes a una fecha dada (default: hoy) */
    public function scopeVigente($query, ?\DateTimeInterface $fecha = null)
    {
        $f = $fecha ?? now()->toDateString();
        return $query->where('vigente_desde', '<=', $f)
                     ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $f));
    }

    /** Tarifas para una ruta origen→destino */
    public function scopeParaRuta($query, int $origenId, int $destinoId)
    {
        return $query->where('provincia_origen_id', $origenId)
                     ->where('provincia_destino_id', $destinoId);
    }
}
