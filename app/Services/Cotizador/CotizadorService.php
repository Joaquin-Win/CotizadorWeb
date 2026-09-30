<?php

namespace App\Services\Cotizador;

use App\Data\Cotizador\BultoData;
use App\Data\Cotizador\CotizacionRequestData;
use App\Data\Cotizador\CostoTramo;
use App\Data\Cotizador\ResultadoCotizacion;
use App\Models\ConfiguracionCotizador;
use App\Models\Proveedor;
use App\Models\TipoServicio;
use Illuminate\Support\Facades\Log;

/**
 * CotizadorService — Orquestador del motor de cotización.
 *
 * Pipeline de ejecución:
 *  1.  Obtener proveedor activo (SET Logística)
 *  2.  Resolver acuerdo comercial del cliente
 *  3.  Determinar tramos candidatos (RutaResolver)
 *  4.  Para cada bulto:
 *       a. Buscar tarifas por tramo (TarifaResolver)
 *       b. Determinar unidad de cobro (AforoService)
 *       c. Calcular costo por escalón (EscalonResolver)
 *       d. Aplicar mínimo (MinimoService)
 *  5.  Calcular costos adicionales (AdicionalesService)
 *  6.  Resolver tiempo estimado (TiempoEntregaService)
 *  7.  Aplicar pipeline de precios (CommercialPricingPolicy)
 *  8.  Si algún tramo faltó tarifa → estado = ATENCION_PERSONALIZADA
 *
 * NUNCA calcula precios en el Controller. Nunca confía en datos del frontend.
 */
class CotizadorService
{
    public function __construct(
        private readonly RutaResolver              $rutaResolver,
        private readonly TarifaResolver            $tarifaResolver,
        private readonly AforoService              $aforoService,
        private readonly EscalonResolver           $escalonResolver,
        private readonly MinimoService             $minimoService,
        private readonly AcuerdoComercialService   $acuerdoService,
        private readonly AdicionalesService        $adicionalesService,
        private readonly TiempoEntregaService      $tiempoEntregaService,
        private readonly MargenService             $margenService,
        private readonly SeguroService             $seguroService,
        private readonly CommercialPricingPolicy   $pricingPolicy,
        private readonly PalletEquivalenceService  $palletEquivalenceService,
    ) {}

    /**
     * Calcula la cotización completa.
     *
     * @param  CotizacionRequestData $data  DTO con todos los datos del request
     * @return ResultadoCotizacion          DTO con el resultado calculado
     */
    public function calcular(CotizacionRequestData $data): ResultadoCotizacion
    {
        $resultado = new ResultadoCotizacion();
        $resultado->versionAlgoritmo = ConfiguracionCotizador::texto(
            'algoritmo_version',
            config('cotizador.algorithm_version', 'v2.0.0')
        );

        try {
            // 1. Proveedor activo
            $proveedor = Proveedor::activo()->first();
            if (! $proveedor) {
                return $this->atencionPersonalizada($resultado, 'No hay proveedor activo configurado.');
            }

            // 2. Acuerdo comercial del cliente
            $acuerdo          = $this->acuerdoService->acuerdoVigente($data->clienteId);
            $resultado->acuerdoId  = $acuerdo?->id;
            $seguroIncluido   = $this->acuerdoService->tieneSeguroIncluido($acuerdo);
            $descuentoPorcentaje = $this->acuerdoService->calcularDescuentoNeto($acuerdo, $data->clienteId);

            // 3. Determinar tramos candidatos
            $tramos = $this->rutaResolver->resolverTramos($data);

            // Verificar si aplica PUERTA_PUERTA
            $usarPuertaPuerta = false;
            if ($this->rutaResolver->candidatoPuertaPuerta($data)) {
                $tarifasPP = $this->tarifaResolver->puertaPuerta(
                    $data->origen->provinciaId,
                    $data->destino->provinciaId,
                    $data->destino->localidadId ?? null,
                    $proveedor->id
                );
                if ($tarifasPP->isNotEmpty()) {
                    $usarPuertaPuerta = true;
                }
            }

            // 4. Calcular costo por tramo
            $costoTroncal      = 0;
            $costoPrimeraMilla = 0;
            $costoUltimaMilla  = 0;
            $costoPuertaPuerta = 0;
            $tramosFaltantes   = [];

            // Totales físicos de los bultos
            $volumenTotalM3 = 0;
            $pesoTotalKg    = 0;

            foreach ($data->bultos as $bulto) {
                $volumenTotalM3 += $this->aforoService->calcularVolumenM3($bulto);
                $pesoTotalKg    += $this->aforoService->calcularPesoTotalKg($bulto);
            }

            if ($usarPuertaPuerta) {
                // Ruta PUERTA_PUERTA unificada
                $tarifasPP = $this->tarifaResolver->puertaPuerta(
                    $data->origen->provinciaId,
                    $data->destino->provinciaId,
                    $data->destino->localidadId ?? null,
                    $proveedor->id
                );

                $costoPuertaPuerta = $this->calcularCostoTramo(
                    $tarifasPP, $volumenTotalM3, $pesoTotalKg, $data->bultos,
                    'PUERTA_PUERTA',
                    $data->origen->provinciaId, $data->destino->provinciaId,
                    $data->destino->localidadId ?? null
                );

                if ($costoPuertaPuerta === null) {
                    $tramosFaltantes[] = 'PUERTA_PUERTA';
                    $costoPuertaPuerta = 0;
                }

            } else {
                // Tramos individuales
                foreach ($tramos as $tramo) {
                    switch ($tramo) {
                        case 'TRONCAL':
                            $tarifas = $this->tarifaResolver->troncal(
                                $data->origen->provinciaId,
                                $data->destino->provinciaId,
                                $proveedor->id
                            );
                            $costo = $this->calcularCostoTramo(
                                $tarifas, $volumenTotalM3, $pesoTotalKg, $data->bultos,
                                'TRONCAL',
                                $data->origen->provinciaId, $data->destino->provinciaId,
                                null
                            );
                            if ($costo === null) {
                                $tramosFaltantes[] = 'TRONCAL';
                            } else {
                                $costoTroncal = $costo;
                            }
                            break;

                        case 'PRIMERA_MILLA':
                            if (! ($data->origen->localidadId ?? null)) {
                                break;
                            }
                            $tarifas = $this->tarifaResolver->primeraMilla(
                                $data->origen->provinciaId,
                                $data->origen->localidadId,
                                $proveedor->id
                            );
                            $costo = $this->calcularCostoTramo(
                                $tarifas, $volumenTotalM3, $pesoTotalKg, $data->bultos,
                                'PRIMERA_MILLA',
                                $data->origen->provinciaId, $data->origen->provinciaId,
                                $data->origen->localidadId
                            );
                            if ($costo === null) {
                                $tramosFaltantes[] = 'PRIMERA_MILLA';
                            } else {
                                $costoPrimeraMilla = $costo;
                            }
                            break;

                        case 'ULTIMA_MILLA':
                            $tarifas = $this->tarifaResolver->ultimaMilla(
                                $data->destino->provinciaId,
                                $data->destino->localidadId ?? null,
                                $proveedor->id
                            );
                            $costo = $this->calcularCostoTramo(
                                $tarifas, $volumenTotalM3, $pesoTotalKg, $data->bultos,
                                'ULTIMA_MILLA',
                                $data->destino->provinciaId, $data->destino->provinciaId,
                                $data->destino->localidadId ?? null
                            );
                            if ($costo === null) {
                                $tramosFaltantes[] = 'ULTIMA_MILLA';
                            } else {
                                $costoUltimaMilla = $costo;
                            }
                            break;
                    }
                }
            }

            // Si falta algún tramo → atención personalizada
            if (! empty($tramosFaltantes)) {
                $error = 'Sin tarifa para: ' . implode(', ', $tramosFaltantes);
                return $this->atencionPersonalizada($resultado, $error);
            }

            // Cargar costos de tramos en el DTO
            $resultado->costoPrimeraMilla = $costoPrimeraMilla;
            $resultado->costoTroncal      = $costoTroncal;
            $resultado->costoUltimaMilla  = $costoUltimaMilla;
            $resultado->costoPuertaPuerta = $costoPuertaPuerta;

            // 5. Costos adicionales
            $adicionales = $this->adicionalesService->calcular(
                $data->solicitaCarga    ?? false,
                $data->solicitaDescarga ?? false,
                $costoPrimeraMilla + $costoTroncal + $costoUltimaMilla + $costoPuertaPuerta
            );
            $resultado->costoCargaDescarga = $adicionales['total'];
            $resultado->adicionales        = $adicionales['items'];

            // 6. Tiempo estimado
            $tiempo = $this->tiempoEntregaService->resolver(
                $data->origen->provinciaId,
                $data->destino->provinciaId,
                $data->origen->localidadId  ?? null,
                $data->destino->localidadId ?? null
            );
            $resultado->tiempoMin = $tiempo['min'];
            $resultado->tiempoMax = $tiempo['max'];

            // 7. Aplicar pipeline de precios (seguro, margen, descuento, IVA, total)
            $tipoClienteId = $data->tipoClienteId ?? $this->tipoClientePublicoId();
            $resultado = $this->pricingPolicy->aplicar($resultado, [
                'tipo_cliente_id'    => $tipoClienteId,
                'tipo_servicio_id'   => null,
                'valor_declarado'    => $data->valorDeclarado ?? null,
                'seguro_incluido'    => $seguroIncluido,
                'descuento_porcentaje' => $descuentoPorcentaje,
                'iva_porcentaje'     => ConfiguracionCotizador::numero('iva_porcentaje', 0),
            ]);

            $resultado->estado = 'OK';

        } catch (\Throwable $e) {
            Log::error('[CotizadorService] Error al calcular cotización', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $resultado = $this->atencionPersonalizada(
                $resultado,
                'Error interno al calcular la cotización.'
            );
        }

        return $resultado;
    }

    // -----------------------------------------------
    // Helpers privados
    // -----------------------------------------------

    /**
     * Calcula el costo de un tramo dado un conjunto de tarifas y las medidas totales.
     * Determina la unidad de cobro óptima y aplica el mínimo.
     *
     * Retorna null si no hay tarifas disponibles.
     */
    private function calcularCostoTramo(
        \Illuminate\Support\Collection $tarifas,
        float $volumenTotalM3,
        float $pesoTotalKg,
        array $bultos,
        string $tipoTramo,
        int $provinciaOrigenId,
        int $provinciaDestinoId,
        ?int $localidadId
    ): ?float {
        if ($tarifas->isEmpty()) {
            return null;
        }

        // Construir mapa de tarifas por unidad de medida
        $mapaTarifas = $this->escalonResolver->mapaTarifas($tarifas);

        // Determinar unidad de cobro usando el primer bulto como referencia
        // (simplificación: si todos los bultos son del mismo tipo, usar la misma unidad)
        $primerBulto = $bultos[0] ?? null;
        if ($primerBulto instanceof BultoData) {
            $unidadCobro = $this->aforoService->determinarUnidadCobro($primerBulto, $mapaTarifas);
        } else {
            $unidadCobro = 'M3';
        }

        // Elegir la tarifa correcta para la unidad
        $tarifaParaUnidad = $tarifas->filter(function ($tarifa) use ($unidadCobro) {
            return optional($tarifa->unidadMedida)->codigo === $unidadCobro;
        });

        if ($tarifaParaUnidad->isEmpty()) {
            // Fallback a la primera tarifa disponible
            $tarifaParaUnidad = $tarifas;
            $unidadCobro = optional($tarifas->first()->unidadMedida)->codigo ?? 'M3';
        }

        // Calcular la cantidad en la unidad de cobro
        $cantidad = match ($unidadCobro) {
            'M3'     => $volumenTotalM3,
            'KG'     => $pesoTotalKg,
            'PALLET' => 1.0, // por defecto 1 pallet si no hay equivalencia calculada
            default  => $volumenTotalM3,
        };

        $costo = $this->escalonResolver->calcular($tarifaParaUnidad, $cantidad);

        if ($costo === null) {
            return null;
        }

        // Obtener tipo_servicio_id para el mínimo
        $tipoServicioId = optional($tarifas->first()->tipoServicio)->id
                       ?? TipoServicio::where('codigo', $tipoTramo)->value('id');

        // Aplicar mínimo
        $costo = $this->minimoService->aplicar(
            $costo,
            $provinciaOrigenId,
            $provinciaDestinoId,
            $tipoServicioId ?? 0,
            $localidadId
        );

        return $costo;
    }

    /**
     * Marca el resultado como ATENCION_PERSONALIZADA y registra el error.
     */
    private function atencionPersonalizada(ResultadoCotizacion $resultado, string $motivo): ResultadoCotizacion
    {
        $resultado->estado  = 'ATENCION_PERSONALIZADA';
        $resultado->errores[] = $motivo;

        // Resetear montos
        $resultado->costoPrimeraMilla = 0;
        $resultado->costoTroncal      = 0;
        $resultado->costoUltimaMilla  = 0;
        $resultado->costoPuertaPuerta = 0;
        $resultado->subtotalFlete     = 0;
        $resultado->costoSeguro       = 0;
        $resultado->costoCargaDescarga = 0;
        $resultado->margenMonto       = 0;
        $resultado->descuentoMonto    = 0;
        $resultado->iva               = 0;
        $resultado->totalFinal        = 0;

        return $resultado;
    }

    /**
     * ID del tipo_cliente PUBLICO (para usuarios sin login).
     */
    private function tipoClientePublicoId(): int
    {
        return \App\Models\TipoCliente::where('codigo', 'PUBLICO')->value('id') ?? 1;
    }
}
