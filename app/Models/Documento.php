<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comprobante de una empresa, espejo de `documentos`.
 *
 * Guarda el archivo (pdf, png o jpg) y lo clasifica por tipo
 * (remito, factura...). Un comprobante fiscal sobrevive al pedido.
 *
 * @property int $id
 * @property int $cliente_id
 * @property int|null $pedido_id
 * @property int $tipo_documento_id
 * @property int $punto_venta
 * @property string $numero_documento
 * @property string $fecha
 * @property string $url_archivo
 */
class Documento extends Model
{
    protected $table = 'documentos';

    protected $fillable = [
        'cliente_id', 'pedido_id', 'tipo_documento_id', 'punto_venta',
        'numero_documento', 'fecha', 'url_archivo',
    ];

    /** Empresa dueña del comprobante. */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'cliente_id');
    }

    /** Tipo (remito, factura...). */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }
}
