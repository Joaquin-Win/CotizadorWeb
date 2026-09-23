<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cotizacion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'origen_id', 'tipo_cliente_id', 'cliente_id',
        'usuario_id', 'acuerdo_id', 'estado_id',
    ];

    protected $casts = [];

    // -----------------------------------------------
    // Relaciones
    // -----------------------------------------------
    public function origen()       { return $this->belongsTo(OrigenCotizacion::class); }
    public function tipoCliente()  { return $this->belongsTo(TipoCliente::class); }
    public function cliente()      { return $this->belongsTo(Cliente::class); }
    public function usuario()      { return $this->belongsTo(User::class, 'usuario_id'); }
    public function acuerdo()      { return $this->belongsTo(AcuerdoComercial::class); }
    public function estado()       { return $this->belongsTo(EstadoCotizacion::class, 'estado_id'); }

    public function envio()        { return $this->hasOne(CotizacionEnvio::class); }
    public function bultos()       { return $this->hasMany(CotizacionBulto::class); }
    public function resultados()   { return $this->hasMany(CotizacionResultado::class); }
    public function seguimientos() { return $this->hasMany(CotizacionSeguimiento::class); }
    public function codigo()       { return $this->hasOne(CotizacionCodigo::class); }
    public function lead()         { return $this->hasOne(CotizacionLead::class); }

    /** Último cálculo vigente */
    public function resultadoActual(): ?CotizacionResultado
    {
        return $this->resultados()->latest('numero_version')->first();
    }
}
