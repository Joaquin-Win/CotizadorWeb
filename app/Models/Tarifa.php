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
        'proveedor_id', 'user_id', 'provincia_origen_id', 'provincia_destino_id',
        'localidad_destino_id', 'tipo_servicio_id', 'unidad_medida_id',
        'costo_unitario', 'maximo', 'max_m3_bulto', 'max_unidades',
        'min_unidades', 'min_m3_bulto', 'max_kg_bulto', 'tiempo_estimado',
        'vigente_desde', 'vigente_hasta',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'costo_unitario'=> 'decimal:2',
        'maximo'        => 'decimal:2',
        'max_m3_bulto'  => 'decimal:3',
        'max_unidades'  => 'integer',
        'min_unidades'  => 'integer',
        'min_m3_bulto'  => 'decimal:3',
        'max_kg_bulto'  => 'decimal:2',
        'tiempo_estimado' => 'integer',   // horas
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

    /** Cliente dueño de la tarifa (NULL = tarifa general). */
    public function user()               { return $this->belongsTo(User::class, 'user_id'); }

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------
    /** Solo tarifas generales (user_id NULL). */
    public function scopeGenerales($query)
    {
        return $query->whereNull($query->getModel()->getTable() . '.user_id');
    }

    /** Solo tarifas exclusivas de un cliente. */
    public function scopeDeUsuario($query, int $userId)
    {
        return $query->where($query->getModel()->getTable() . '.user_id', $userId);
    }

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
