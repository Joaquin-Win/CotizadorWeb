<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoComercial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoComercial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoComercial query()
 */
	class AcuerdoComercial extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $acuerdo_id
 * @property int $tipo_condicion_id
 * @property numeric|null $valor_numerico
 * @property string|null $valor_texto
 * @property int|null $valor_booleano
 * @property int|null $zona_id
 * @property int|null $tipo_servicio_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion whereAcuerdoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion whereTipoCondicionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion whereTipoServicioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion whereValorBooleano($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion whereValorNumerico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion whereValorTexto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AcuerdoCondicion whereZonaId($value)
 */
	class AcuerdoCondicion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $tipo_cliente_id
 * @property int $estado_id
 * @property string $razon_social
 * @property string|null $nombre_fantasia
 * @property string $cuit Formato: XX-XXXXXXXX-X
 * @property string|null $cuit_unico
 * @property string|null $email_facturacion
 * @property string|null $telefono
 * @property string|null $direccion
 * @property int|null $localidad_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AcuerdoComercial> $acuerdos
 * @property-read int|null $acuerdos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClienteContacto> $contactos
 * @property-read int|null $contactos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cotizacion> $cotizaciones
 * @property-read int|null $cotizaciones_count
 * @property-read \App\Models\EstadoCliente $estado
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClienteIntegracion> $integraciones
 * @property-read int|null $integraciones_count
 * @property-read \App\Models\Localidad|null $localidad
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pedido> $pedidos
 * @property-read int|null $pedidos_count
 * @property-read \App\Models\TipoCliente $tipoCliente
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $usuarios
 * @property-read int|null $usuarios_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCuit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereCuitUnico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereDireccion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereEmailFacturacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereEstadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereLocalidadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereNombreFantasia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereRazonSocial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereTipoClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cliente withoutTrashed()
 */
	class Cliente extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cliente_id
 * @property string $nombre
 * @property string|null $cargo
 * @property string|null $email
 * @property string|null $telefono
 * @property int $principal
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto whereCargo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto wherePrincipal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteContacto whereUpdatedAt($value)
 */
	class ClienteContacto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cliente_id
 * @property int $tipo_integracion_id
 * @property int $estado_integracion_id
 * @property string|null $nombre_tienda
 * @property string|null $referencia_externa
 * @property string|null $credenciales_ref
 * @property string|null $conectado_at
 * @property int|null $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereConectadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereCredencialesRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereEstadoIntegracionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereNombreTienda($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereReferenciaExterna($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereTipoIntegracionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClienteIntegracion whereUpdatedAt($value)
 */
	class ClienteIntegracion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $tipo_servicio_id
 * @property string $codigo
 * @property string $nombre
 * @property string|null $descripcion
 * @property numeric $monto
 * @property string $unidad $ = fijo | % = porcentaje
 * @property int $es_obligatorio
 * @property int $activo
 * @property int|null $created_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereEsObligatorio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereTipoServicioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereUnidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CostoAdicional whereUpdatedAt($value)
 */
	class CostoAdicional extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $origen_id
 * @property int $tipo_cliente_id
 * @property int|null $cliente_id
 * @property int|null $usuario_id
 * @property int|null $acuerdo_id
 * @property int $estado_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\AcuerdoComercial|null $acuerdo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CotizacionBulto> $bultos
 * @property-read int|null $bultos_count
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\CotizacionCodigo|null $codigo
 * @property-read \App\Models\CotizacionEnvio|null $envio
 * @property-read \App\Models\EstadoCotizacion $estado
 * @property-read \App\Models\CotizacionLead|null $lead
 * @property-read \App\Models\OrigenCotizacion $origen
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CotizacionResultado> $resultados
 * @property-read int|null $resultados_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CotizacionSeguimiento> $seguimientos
 * @property-read int|null $seguimientos_count
 * @property-read \App\Models\TipoCliente $tipoCliente
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereAcuerdoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereEstadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereOrigenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereTipoClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion whereUsuarioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cotizacion withoutTrashed()
 */
	class Cotizacion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cotizacion_id
 * @property int $tipo_bulto_id
 * @property int $cantidad
 * @property numeric $largo_cm
 * @property numeric $ancho_cm
 * @property numeric $alto_cm
 * @property numeric $peso_kg
 * @property int $palletizado
 * @property numeric|null $volumen_m3
 * @property numeric|null $peso_total_kg
 * @property numeric|null $pallets_equivalentes Calculado: volumen / 1.1 m3 por pallet
 * @property numeric|null $costo_individual Snapshot del costo por este bulto
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereAltoCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereAnchoCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereCantidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereCostoIndividual($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereLargoCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto wherePalletizado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto wherePalletsEquivalentes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto wherePesoKg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto wherePesoTotalKg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereTipoBultoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionBulto whereVolumenM3($value)
 */
	class CotizacionBulto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cotizacion_id
 * @property string $codigo COT-YYYY-NNNNNN
 * @property \Carbon\CarbonImmutable $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCodigo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCodigo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCodigo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCodigo whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCodigo whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCodigo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCodigo whereId($value)
 */
	class CotizacionCodigo extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cotizacion_id
 * @property int $resultado_id
 * @property int|null $costo_adicional_id
 * @property string $nombre Snapshot del nombre al momento de calcular
 * @property numeric $monto_aplicado Monto ya calculado (si era % se convirtió a $)
 * @property string $tipo $ | %
 * @property \Carbon\CarbonImmutable $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional whereCostoAdicionalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional whereMontoAplicado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional whereResultadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionCostoAdicional whereTipo($value)
 */
	class CotizacionCostoAdicional extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cotizacion_id
 * @property int $provincia_origen_id
 * @property int|null $localidad_origen_id
 * @property int $provincia_destino_id
 * @property int|null $localidad_destino_id
 * @property int $solicita_retiro Cliente pide que SET retire en su domicilio
 * @property int $solicita_entrega Cliente pide entrega a domicilio en destino
 * @property int $retira_en_sucursal Destinatario retira en sucursal SET
 * @property numeric|null $valor_declarado Para cálculo de seguro
 * @property int $dias_almacenamiento Días en depósito (si aplica)
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereDiasAlmacenamiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereLocalidadDestinoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereLocalidadOrigenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereProvinciaDestinoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereProvinciaOrigenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereRetiraEnSucursal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereSolicitaEntrega($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereSolicitaRetiro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionEnvio whereValorDeclarado($value)
 */
	class CotizacionEnvio extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cotizacion_id
 * @property string|null $nombre
 * @property string|null $email
 * @property string|null $empresa
 * @property string|null $telefono
 * @property string|null $ip
 * @property string|null $user_agent
 * @property \Carbon\CarbonImmutable $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead whereEmpresa($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionLead whereUserAgent($value)
 */
	class CotizacionLead extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cotizacion_id
 * @property int $numero_version Versión del cálculo; incrementa en cada recalculo
 * @property numeric|null $peso_total_kg
 * @property numeric|null $volumen_total_m3
 * @property numeric|null $pallets_equivalentes
 * @property numeric $subtotal_flete Costo de transporte sin márgenes ni descuentos
 * @property numeric $margen_porcentaje
 * @property numeric $margen_monto
 * @property numeric $descuento_porcentaje
 * @property numeric $descuento_monto
 * @property numeric $subtotal_adicionales
 * @property numeric $seguro_monto
 * @property numeric $total_final
 * @property int|null $margen_id
 * @property int|null $tarifa_id
 * @property string|null $unidad_cobro KG | M3 | PALLET | BULTO
 * @property numeric|null $cantidad_cobrada
 * @property string $calculado_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereCalculadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereCantidadCobrada($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereDescuentoMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereDescuentoPorcentaje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereMargenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereMargenMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereMargenPorcentaje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereNumeroVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado wherePalletsEquivalentes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado wherePesoTotalKg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereSeguroMonto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereSubtotalAdicionales($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereSubtotalFlete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereTarifaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereTotalFinal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereUnidadCobro($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionResultado whereVolumenTotalM3($value)
 */
	class CotizacionResultado extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cotizacion_id
 * @property int $estado_id
 * @property int|null $usuario_id
 * @property string|null $observacion
 * @property \Carbon\CarbonImmutable $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionSeguimiento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionSeguimiento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionSeguimiento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionSeguimiento whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionSeguimiento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionSeguimiento whereEstadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionSeguimiento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionSeguimiento whereObservacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CotizacionSeguimiento whereUsuarioId($value)
 */
	class CotizacionSeguimiento extends \Eloquent {}
}

namespace App\Models{
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
 * @property-read \App\Models\TipoDocumento|null $tipo
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Documento query()
 */
	class Documento extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property int $permite_operar 1 = puede cotizar y hacer pedidos
 * @property int $activo
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cliente> $clientes
 * @property-read int|null $clientes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCliente newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCliente newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCliente query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCliente whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCliente whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCliente whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCliente whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCliente whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCliente wherePermiteOperar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCliente whereUpdatedAt($value)
 */
	class EstadoCliente extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property int $orden
 * @property int $es_final
 * @property int $activo
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion whereEsFinal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion whereOrden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoCotizacion whereUpdatedAt($value)
 */
	class EstadoCotizacion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property int $es_operativo 1 = la integración puede sincronizar
 * @property int $activo
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoIntegracion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoIntegracion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoIntegracion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoIntegracion whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoIntegracion whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoIntegracion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoIntegracion whereEsOperativo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoIntegracion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoIntegracion whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoIntegracion whereUpdatedAt($value)
 */
	class EstadoIntegracion extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoPedido newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoPedido newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EstadoPedido query()
 */
	class EstadoPedido extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $importacion_id
 * @property int $numero_fila
 * @property string|null $columna
 * @property string $mensaje_error
 * @property string|null $datos_fila
 * @property \Carbon\CarbonImmutable $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionError newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionError newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionError query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionError whereColumna($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionError whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionError whereDatosFila($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionError whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionError whereImportacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionError whereMensajeError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionError whereNumeroFila($value)
 */
	class ImportacionError extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $operador_id
 * @property int $cliente_id
 * @property int $tipo_importacion_id
 * @property int $estado_id
 * @property string $nombre_archivo
 * @property string $ruta_archivo
 * @property int $total_filas
 * @property int $filas_exitosas
 * @property int $filas_con_error
 * @property string|null $observaciones
 * @property string|null $procesado_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereEstadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereFilasConError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereFilasExitosas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereNombreArchivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereObservaciones($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereOperadorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereProcesadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereRutaArchivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereTipoImportacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereTotalFilas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImportacionExcel whereUpdatedAt($value)
 */
	class ImportacionExcel extends \Eloquent {}
}

namespace App\Models{
/**
 * Invitación a sumarse como usuario de una empresa.
 *
 * Se crea desde Mi Cuenta, viaja por email con un token de un solo
 * uso que vence en 7 días y al aceptarse nace el usuario atado
 * a esa empresa.
 *
 * @property int $id
 * @property int $client_id
 * @property string $email
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $accepted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitacion query()
 */
	class Invitacion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $provincia_id
 * @property string $nombre
 * @property string|null $codigo_postal
 * @property string $codigo_georef
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cliente> $clientes
 * @property-read int|null $clientes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad whereCodigoGeoref($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad whereCodigoPostal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad whereProvinciaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Localidad whereUpdatedAt($value)
 */
	class Localidad extends \Eloquent {}
}

namespace App\Models{
/**
 * Margen de ganancia aplicado sobre el costo de flete.
 *
 * porcentaje = 30 → precio = costo × 1.30
 * Resolución: más específico gana (tipo_cliente + tipo_servicio > tipo_cliente > global)
 *
 * @property int $id
 * @property int|null $tipo_cliente_id
 * @property int|null $tipo_servicio_id
 * @property numeric $porcentaje Recargo sobre costo. 25.00 = costo × 1.25
 * @property \Carbon\CarbonImmutable $vigente_desde
 * @property \Carbon\CarbonImmutable|null $vigente_hasta NULL = sin vencimiento
 * @property string|null $motivo
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\TipoCliente|null $tipoCliente
 * @property-read \App\Models\TipoServicio|null $tipoServicio
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia vigente(?\DateTimeInterface $fecha = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereMotivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia wherePorcentaje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereTipoClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereTipoServicioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereVigenteDesde($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia whereVigenteHasta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MargenGanancia withoutTrashed()
 */
	class MargenGanancia extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property int $requiere_login
 * @property int $activo
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrigenCotizacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrigenCotizacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrigenCotizacion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrigenCotizacion whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrigenCotizacion whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrigenCotizacion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrigenCotizacion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrigenCotizacion whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrigenCotizacion whereRequiereLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrigenCotizacion whereUpdatedAt($value)
 */
	class OrigenCotizacion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $cliente_id
 * @property int $localidad_destino_id
 * @property int $estado_id
 * @property string $numero_pedido
 * @property \Carbon\CarbonImmutable $fecha
 * @property int|null $cotizacion_id
 * @property string|null $transoft_tracking
 * @property string|null $transoft_operation_id
 * @property string|null $transoft_estado_codigo
 * @property string|null $transoft_etiqueta_url
 * @property string|null $transoft_seguimiento_url
 * @property \Carbon\CarbonImmutable|null $transoft_sync_at
 * @property array<array-key, mixed>|null $transoft_payload_json
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Cliente|null $cliente
 * @property-read \App\Models\Cotizacion|null $cotizacion
 * @property-read \App\Models\EstadoPedido $estado
 * @property-read \App\Models\Localidad $localidadDestino
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Seguimiento> $seguimientos
 * @property-read int|null $seguimientos_count
 * @property-read \App\Models\TransoftEstado|null $transoftEstado
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TransoftWebhookEvento> $webhookEventos
 * @property-read int|null $webhook_eventos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereEstadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereFecha($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereLocalidadDestinoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereNumeroPedido($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereTransoftEstadoCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereTransoftEtiquetaUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereTransoftOperationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereTransoftPayloadJson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereTransoftSeguimientoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereTransoftSyncAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereTransoftTracking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pedido withoutTrashed()
 */
	class Pedido extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Proveedor query()
 */
	class Proveedor extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $nombre
 * @property string $codigo_georef Código GeoRef INDEC
 * @property int $tiene_deposito
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Provincia newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Provincia newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Provincia query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Provincia whereCodigoGeoref($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Provincia whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Provincia whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Provincia whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Provincia whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Provincia whereTieneDeposito($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Provincia whereUpdatedAt($value)
 */
	class Provincia extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol query()
 */
	class Rol extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pedido_id
 * @property int $estado_id
 * @property int $localidad_destino_id
 * @property string $numero_seguimiento
 * @property string $fecha_actualizacion
 * @property string|null $observacion
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento whereEstadoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento whereFechaActualizacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento whereLocalidadDestinoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento whereNumeroSeguimiento($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento whereObservacion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento wherePedidoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Seguimiento whereUpdatedAt($value)
 */
	class Seguimiento extends \Eloquent {}
}

namespace App\Models{
/**
 * Tarifa de flete por ruta, servicio y unidad de medida.
 *
 * maximo = NULL → escalón abierto (sin tope).
 *
 * @property int $id
 * @property int $proveedor_id
 * @property int $provincia_origen_id
 * @property int $provincia_destino_id
 * @property int|null $localidad_destino_id
 * @property int $tipo_servicio_id
 * @property int $unidad_medida_id
 * @property numeric $costo_unitario
 * @property numeric|null $maximo Tope de cobro / escaón. NULL = escalón abierto.
 * @property \Carbon\CarbonImmutable $vigente_desde
 * @property \Carbon\CarbonImmutable|null $vigente_hasta
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Localidad|null $localidadDestino
 * @property-read \App\Models\Proveedor $proveedor
 * @property-read \App\Models\Provincia $provinciaDestino
 * @property-read \App\Models\Provincia $provinciaOrigen
 * @property-read \App\Models\TipoServicio $tipoServicio
 * @property-read \App\Models\UnidadMedida $unidadMedida
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa paraRuta(int $origenId, int $destinoId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa vigente(?\DateTimeInterface $fecha = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereCostoUnitario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereLocalidadDestinoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereMaximo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereProveedorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereProvinciaDestinoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereProvinciaOrigenId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereTipoServicioId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereUnidadMedidaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereVigenteDesde($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa whereVigenteHasta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tarifa withoutTrashed()
 */
	class Tarifa extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifaMinima newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifaMinima newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TarifaMinima query()
 */
	class TarifaMinima extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TiempoEstimado newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TiempoEstimado newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TiempoEstimado query()
 */
	class TiempoEstimado extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoBulto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoBulto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoBulto query()
 */
	class TipoBulto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string|null $descripcion
 * @property int $nivel 0=Público, 1=B2B, 2=B2B Premium
 * @property int $requiere_usuario 0 = puede cotizar sin login
 * @property int $permite_acuerdo_comercial
 * @property numeric $descuento_base_porcentaje
 * @property int $activo
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cliente> $clientes
 * @property-read int|null $clientes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente whereDescuentoBasePorcentaje($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente whereNivel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente wherePermiteAcuerdoComercial($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente whereRequiereUsuario($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCliente whereUpdatedAt($value)
 */
	class TipoCliente extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string|null $descripcion
 * @property string $tipo_dato NUMERO | TEXTO | BOOLEANO | ZONA | SERVICIO
 * @property string|null $unidad Unidad legible del valor numérico
 * @property int $activo
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial whereTipoDato($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial whereUnidad($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoCondicionComercial whereUpdatedAt($value)
 */
	class TipoCondicionComercial extends \Eloquent {}
}

namespace App\Models{
/**
 * Tipo de comprobante, espejo de `tipos_documento`.
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property int $es_fiscal 1 = comprobante fiscal, nunca se borra
 * @property int $activo
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoDocumento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoDocumento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoDocumento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoDocumento whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoDocumento whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoDocumento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoDocumento whereEsFiscal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoDocumento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoDocumento whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoDocumento whereUpdatedAt($value)
 */
	class TipoDocumento extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoServicio newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoServicio newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TipoServicio query()
 */
	class TipoServicio extends \Eloquent {}
}

namespace App\Models{
/**
 * Configuración editable de la integración Transoft.
 *
 * Los valores con encriptado=true se almacenan cifrados via Laravel encrypt().
 *
 * @property int    $id
 * @property string $clave       — base_url | username | password | operation_id | webhook_secret
 * @property string|null $valor
 * @property bool   $encriptado
 * @property string|null $descripcion
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftConfiguracion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftConfiguracion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftConfiguracion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftConfiguracion whereClave($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftConfiguracion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftConfiguracion whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftConfiguracion whereEncriptado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftConfiguracion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftConfiguracion whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftConfiguracion whereValor($value)
 */
	class TransoftConfiguracion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $codigo PC, TT, ED...
 * @property string $descripcion
 * @property int|null $estado_pedido_id
 * @property int|null $estado_cotizacion_id
 * @property bool $es_final
 * @property bool $activo
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\EstadoCotizacion|null $estadoCotizacion
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado whereEsFinal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado whereEstadoCotizacionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado whereEstadoPedidoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftEstado whereUpdatedAt($value)
 */
	class TransoftEstado extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $tracking
 * @property string|null $estado_codigo
 * @property string $payload
 * @property string|null $headers
 * @property string|null $firma_recibida
 * @property int $firma_valida
 * @property int $procesado
 * @property string|null $error
 * @property int|null $pedido_id
 * @property \Carbon\CarbonImmutable $created_at
 * @property string|null $procesado_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento whereEstadoCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento whereFirmaRecibida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento whereFirmaValida($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento wherePedidoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento whereProcesado($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento whereProcesadoAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransoftWebhookEvento whereTracking($value)
 */
	class TransoftWebhookEvento extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnidadMedida query()
 */
	class UnidadMedida extends \Eloquent {}
}

namespace App\Models{
/**
 * Modelo de autenticación.
 *
 * Tabla: usuarios (del SQL cotizador_set, NO la tabla 'users' estándar de Laravel).
 *
 * Columnas notables:
 *  - rol_id          → FK a roles
 *  - cliente_id      → FK a clientes (NULL para ADMIN)
 *  - activo          → suspensión temporal sin borrar cuenta
 *  - ultimo_acceso   → timestamp
 *  - email_unico     → columna GENERADA (STORED), no fillable
 *
 * @property int         $id
 * @property int         $rol_id
 * @property int|null    $cliente_id
 * @property string      $name
 * @property string      $email
 * @property string|null $telefono
 * @property bool        $activo
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $ultimo_acceso
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Rol|null      $rol
 * @property-read Cliente|null  $cliente
 * @property string|null $email_unico
 * @property string $password Hash bcrypt/argon2. Nunca texto plano.
 * @property string|null $remember_token
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereClienteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailUnico($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTelefono($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUltimoAcceso($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property string|null $descripcion
 * @property int $activo
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona whereActivo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona whereCodigo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona whereDescripcion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona whereNombre($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zona whereUpdatedAt($value)
 */
	class Zona extends \Eloquent {}
}

