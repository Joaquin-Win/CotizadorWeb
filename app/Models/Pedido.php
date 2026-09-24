<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cliente_id', 'localidad_destino_id', 'estado_id',
        'numero_pedido', 'fecha', 'cotizacion_id',
        'transoft_tracking', 'transoft_operation_id', 'transoft_estado_codigo',
        'transoft_etiqueta_url', 'transoft_seguimiento_url',
        'transoft_sync_at', 'transoft_payload_json',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'fecha'                 => 'date',
        'transoft_sync_at'      => 'datetime',
        'transoft_payload_json' => 'array',
    ];

    // -----------------------------------------------
    // Relaciones
    // -----------------------------------------------
    public function cliente()           { return $this->belongsTo(Cliente::class); }
    public function localidadDestino()  { return $this->belongsTo(Localidad::class, 'localidad_destino_id'); }
    public function estado()            { return $this->belongsTo(EstadoPedido::class, 'estado_id'); }
    public function cotizacion()        { return $this->belongsTo(Cotizacion::class); }
    public function seguimientos()      { return $this->hasMany(Seguimiento::class); }
    public function transoftEstado()    { return $this->belongsTo(TransoftEstado::class, 'transoft_estado_codigo', 'codigo'); }
    public function webhookEventos()    { return $this->hasMany(TransoftWebhookEvento::class); }

    /** Indica si el pedido ya tiene tracking de Transoft asignado */
    public function tieneSyncTransoft(): bool
    {
        return ! empty($this->transoft_tracking);
    }
}
