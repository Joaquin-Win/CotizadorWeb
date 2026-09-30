<?php

namespace App\Services\Cotizador;

use App\Data\Cotizador\ResultadoCotizacion;
use App\Models\Cotizacion;
use App\Models\CotizacionBulto;
use App\Models\CotizacionCodigo;
use App\Models\CotizacionCostoAdicional;
use App\Models\CotizacionEnvio;
use App\Models\CotizacionResultado;
use Illuminate\Support\Facades\DB;

/**
 * Persiste una cotización calculada en la BD.
 *
 * Maneja la transacción completa:
 *  1. Cotizacion (header)
 *  2. CotizacionCodigo (tabla de codigos)
 *  3. CotizacionEnvio (datos del envío)
 *  4. CotizacionBulto (cada bulto)
 *  5. CotizacionResultado (resultado versionado)
 *  6. CotizacionCostoAdicional (costos adicionales con snapshot)
 *
 * NUNCA modifica datos existentes. Para recotizar, incrementa numero_version.
 */
class CotizacionRepository
{
    /**
     * Persiste una cotización completa desde cero dentro de una transacción.
     *
     * @param  array              $payload     Datos normalizados del request
     * @param  ResultadoCotizacion $resultado  DTO con el resultado del cálculo
     * @param  string             $codigo      Código generado (SET-XXXX-XXXXXX)
     * @return Cotizacion
     */
    public function guardar(
        array              $payload,
        ResultadoCotizacion $resultado,
        string             $codigo
    ): Cotizacion {
        return DB::transaction(function () use ($payload, $resultado, $codigo) {

            // 1. Cotizacion header
            $cotizacion = Cotizacion::create([
                'codigo'          => $codigo,
                'origen_id'       => $payload['origen_id'],
                'tipo_cliente_id' => $payload['tipo_cliente_id'],
                'cliente_id'      => $payload['cliente_id'] ?? null,
                'usuario_id'      => $payload['usuario_id'] ?? null,
                'acuerdo_id'      => $resultado->acuerdoId ?? null,
                'estado_id'       => $payload['estado_id'],
            ]);

            // 2. Código de cotización (tabla cotizacion_codigos)
            CotizacionCodigo::create([
                'codigo'        => $codigo,
                'cotizacion_id' => $cotizacion->id,
            ]);

            // 3. Envío
            CotizacionEnvio::create([
                'cotizacion_id'         => $cotizacion->id,
                'provincia_origen_id'   => $payload['provincia_origen_id'],
                'localidad_origen_id'   => $payload['localidad_origen_id'] ?? null,
                'provincia_destino_id'  => $payload['provincia_destino_id'],
                'localidad_destino_id'  => $payload['localidad_destino_id'] ?? null,
                'solicita_retiro'       => $payload['solicita_retiro'] ?? false,
                'solicita_entrega'      => $payload['solicita_entrega'] ?? false,
                'retira_en_sucursal'    => $payload['retira_en_sucursal'] ?? false,
                'valor_declarado'       => $payload['valor_declarado'] ?? null,
                'dias_almacenamiento'   => 0,
            ]);

            // 4. Bultos
            foreach ($payload['bultos'] as $bultoData) {
                CotizacionBulto::create([
                    'cotizacion_id'        => $cotizacion->id,
                    'tipo_bulto_id'        => $bultoData['tipo_bulto_id'],
                    'largo_cm'             => $bultoData['largo_cm'],
                    'ancho_cm'             => $bultoData['ancho_cm'],
                    'alto_cm'              => $bultoData['alto_cm'],
                    'peso_kg'              => $bultoData['peso_kg'],
                    'cantidad'             => $bultoData['cantidad'],
                    'palletizado'          => $bultoData['palletizado'] ?? false,
                    'pallets_equivalentes' => $bultoData['pallets_equivalentes'] ?? null,
                    'costo_individual'     => $bultoData['costo_individual'] ?? null,
                ]);
            }

            // 5. Resultado (versión 1 siempre para cotización nueva)
            $cotizacionResultado = CotizacionResultado::create([
                'cotizacion_id'         => $cotizacion->id,
                'numero_version'        => 1,
                'margen_id'             => $resultado->margenId ?? null,
                'margen_porcentaje'     => $resultado->margenPorcentaje ?? 0,
                'costo_troncal'         => $resultado->costoTroncal ?? 0,
                'costo_primera_milla'   => $resultado->costoPrimeraMilla ?? 0,
                'costo_ultima_milla'    => $resultado->costoUltimaMilla ?? 0,
                'costo_puerta_puerta'   => $resultado->costoPuertaPuerta ?? 0,
                'subtotal_flete'        => $resultado->subtotalFlete ?? 0,
                'costo_seguro'          => $resultado->costoSeguro ?? 0,
                'costo_carga_descarga'  => $resultado->costoCargaDescarga ?? 0,
                'incluye_carga_descarga'=> ($resultado->costoCargaDescarga ?? 0) > 0,
                'margen_ganancia'       => $resultado->margenMonto ?? 0,
                'descuento_porcentaje'  => $resultado->descuentoPorcentaje ?? 0,
                'descuento_monto'       => $resultado->descuentoMonto ?? 0,
                'iva'                   => $resultado->iva ?? 0,
                'total_final'           => $resultado->totalFinal ?? 0,
                'tiempo_estimado_min'   => $resultado->tiempoMin ?? null,
                'tiempo_estimado_max'   => $resultado->tiempoMax ?? null,
                'version_algoritmo'     => $resultado->versionAlgoritmo,
                'calculado_at'          => now(),
            ]);

            // 6. Costos adicionales con snapshot
            foreach ($resultado->adicionales as $adicional) {
                CotizacionCostoAdicional::create([
                    'cotizacion_id'       => $cotizacion->id,
                    'costo_adicional_id'  => $adicional['id'],
                    'nombre_snapshot'     => $adicional['nombre'],
                    'unidad_snapshot'     => $adicional['unidad'],
                    'monto_aplicado'      => $adicional['monto_calculado'],
                ]);
            }

            return $cotizacion->fresh(['envio', 'bultos', 'resultados', 'costosAdicionales']);
        });
    }

    /**
     * Incrementa el resultado de una cotización existente (recotización).
     * Agrega una nueva versión sin tocar las anteriores.
     */
    public function recotizar(
        Cotizacion         $cotizacion,
        ResultadoCotizacion $resultado
    ): CotizacionResultado {
        return DB::transaction(function () use ($cotizacion, $resultado) {
            $ultimaVersion = $cotizacion->resultados()->max('numero_version') ?? 0;

            return CotizacionResultado::create([
                'cotizacion_id'         => $cotizacion->id,
                'numero_version'        => $ultimaVersion + 1,
                'margen_id'             => $resultado->margenId ?? null,
                'margen_porcentaje'     => $resultado->margenPorcentaje ?? 0,
                'costo_troncal'         => $resultado->costoTroncal ?? 0,
                'costo_primera_milla'   => $resultado->costoPrimeraMilla ?? 0,
                'costo_ultima_milla'    => $resultado->costoUltimaMilla ?? 0,
                'costo_puerta_puerta'   => $resultado->costoPuertaPuerta ?? 0,
                'subtotal_flete'        => $resultado->subtotalFlete ?? 0,
                'costo_seguro'          => $resultado->costoSeguro ?? 0,
                'costo_carga_descarga'  => $resultado->costoCargaDescarga ?? 0,
                'incluye_carga_descarga'=> ($resultado->costoCargaDescarga ?? 0) > 0,
                'margen_ganancia'       => $resultado->margenMonto ?? 0,
                'descuento_porcentaje'  => $resultado->descuentoPorcentaje ?? 0,
                'descuento_monto'       => $resultado->descuentoMonto ?? 0,
                'iva'                   => $resultado->iva ?? 0,
                'total_final'           => $resultado->totalFinal ?? 0,
                'tiempo_estimado_min'   => $resultado->tiempoMin ?? null,
                'tiempo_estimado_max'   => $resultado->tiempoMax ?? null,
                'version_algoritmo'     => $resultado->versionAlgoritmo,
                'calculado_at'          => now(),
            ]);
        });
    }
}
