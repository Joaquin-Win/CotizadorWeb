<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tipo_cliente_id', 'estado_id', 'razon_social', 'nombre_fantasia',
        'cuit', 'email_facturacion', 'telefono', 'direccion',
        'localidad_id', 'created_by', 'updated_by',
    ];

    // cuit_unico es columna GENERADA (STORED), nunca fillable

    // -----------------------------------------------
    // Relaciones
    // -----------------------------------------------
    public function tipoCliente()  { return $this->belongsTo(TipoCliente::class); }
    public function estado()       { return $this->belongsTo(EstadoCliente::class, 'estado_id'); }
    public function localidad()    { return $this->belongsTo(Localidad::class); }
    public function usuarios()     { return $this->hasMany(User::class); }
    public function contactos()    { return $this->hasMany(ClienteContacto::class); }
    public function integraciones(){ return $this->hasMany(ClienteIntegracion::class); }
    public function acuerdos()     { return $this->hasMany(AcuerdoComercial::class); }
    public function cotizaciones() { return $this->hasMany(Cotizacion::class); }
    public function pedidos()      { return $this->hasMany(Pedido::class); }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------
    public function acuerdoVigente(): ?AcuerdoComercial
    {
        return $this->acuerdos()
            ->where('vigente_desde', '<=', today())
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', today()))
            ->whereNull('deleted_at')
            ->latest('vigente_desde')
            ->first();
    }
}