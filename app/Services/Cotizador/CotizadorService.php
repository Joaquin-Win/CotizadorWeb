<?php

namespace App\Services\Cotizador;

use App\Data\Cotizador\BultoData;
use App\Data\Cotizador\CotizacionRequestData;
use App\Data\Cotizador\CostoTramo;
use App\Data\Cotizador\ResultadoCotizacion;
use App\Models\ConfiguracionCotizador;
use App\Models\Localidad;
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
*  8.  Si faltaron tarifas o depósitos → esEstimado = true y se avisa en `errores`
 *
 *  El estado siempre es OK: nunca bloquea la cotización, el precio final lo confirma SET Logística.
 *
 *  NUNCA calcula precios en el Controller. Nunca confía en datos del frontend.
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

    /** Desglose por tramo del cálculo en curso (se reinicia en cada calcular()). */
    private array $desgloseTramos = [];

    /**
     * Calcula la cotización completa.
     *
     * @param  CotizacionRequestData $data  DTO con todos los datos del request
     * @return ResultadoCotizacion          DTO con el resultado calculado
     */
    public function calcular(CotizacionRequestData $data): ResultadoCotizacion
    {
        $resultado = new ResultadoCotizacion();
        $this->desgloseTramos = [];
        $resultado->versionAlgoritmo = ConfiguracionCotizador::texto(
            'algoritmo_version',
            config('cotizador.algorithm_version', 'v2.0.0')
        );

        try {
            // 1. Proveedor activo
            $proveedor = Proveedor::activo()->first();
            if (! $proveedor) {
                // Sin proveedor configurado: marcamos estimado, precio = $0.
                // El usuario podrá confirmar y SET Logística le dará el precio real.
                $resultado->esEstimado = true;
                $resultado->errores[]  = 'Sin proveedor activo configurado. Precio estimado — SET Logística confirmará el valor final.';
                $resultado->estado     = 'OK';
                return $resultado;
            }

            // 2. Acuerdo comercial del cliente
            $acuerdo          = $this->acuerdoService->acuerdoVigente($data->clienteId);
            $resultado->acuerdoId  = $acuerdo?->id;
            $seguroIncluido   = $this->acuerdoService->tieneSeguroIncluido($acuerdo);
            $descuentoPorcentaje = $this->acuerdoService->calcularDescuentoNeto($acuerdo, $data->clienteId);

            // 2b. Depósitos: verificamos si hay depósito en las provincias.
            //     Si NO hay depósito, NO abortamos — intentamos calcular igual
            //     usando las tarifas existentes y marcamos el resultado como estimado.
            $sinDeposito = [];
            if (! Localidad::conDeposito()->where('provincia_id', $data->origen->provinciaId)->exists()) {
                $sinDeposito[] = 'origen';
            }
            if (! Localidad::conDeposito()->where('provincia_id', $data->destino->provinciaId)->exists()) {
                $sinDeposito[] = 'destino';
            }
            if (! empty($sinDeposito)) {
                // Sin depósito → marcamos estimado pero continuamos el cálculo
                $resultado->esEstimado = true;
                $resultado->errores[]  = 'Sin depósito configurado en: ' . implode(' y ', $sinDeposito) . '. Precio estimado en base a tarifas disponibles.';
                Log::info('[CotizadorService] Precio estimado — sin depósito en: ' . implode(', ', $sinDeposito));
            }

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

            // Si falta tarifa para algún tramo → NO inventamos precio: se deriva a
            // atención personalizada (un asesor contacta al cliente; el controller
            // dispara las notificaciones de email/WhatsApp al guardar).
            if (! empty($tramosFaltantes)) {
                $resultado->estado       = 'ATENCION_PERSONALIZADA';
                $resultado->esEstimado   = true;
                $resultado->totalFinal   = 0.0;
                $resultado->errores[]    = 'Sin tarifa para: ' . implode(', ', $tramosFaltantes) . '. Un asesor se comunicará a la brevedad.';
                $resultado->desglose     = ['tramos_sin_tarifa' => $tramosFaltantes, 'tramos' => array_values($this->desgloseTramos)];
                Log::info('[CotizadorService] Sin tarifa para tramos: ' . implode(', ', $tramosFaltantes) . ' — ATENCION_PERSONALIZADA.');
                return $resultado;
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

            // 6. Tiempo estimado por tramo
            //    Primera milla: recojo dentro de la provincia origen (pOrigen → pOrigen + loc)
            //    Troncal:        viaje entre depósitos (pOrigen → pDestino)
            //    Última milla:   entrega dentro de la provincia destino (pDestino → pDestino + loc)
            //    Total:          suma de los tres (null = sin dato; se omite del total)

            // — Primera milla (solo si aplica: el cliente solicitó retiro)
            if ($data->origen->solicitaRetiro && $costoPrimeraMilla > 0) {
                $tPM = $this->tiempoEntregaService->resolver(
                    $data->origen->provinciaId,
                    $data->origen->provinciaId,       // ida y vuelta en la misma provincia
                    $data->origen->localidadId ?? null,
                    null
                );
                $resultado->tiempoPrimeraMillaMin = $tPM['min'];
                $resultado->tiempoPrimeraMillaMax = $tPM['max'];
            }

            // — Troncal (siempre que haya precio troncal o puerta a puerta)
            if ($costoTroncal > 0 || $costoPuertaPuerta > 0) {
                $tTR = $this->tiempoEntregaService->resolver(
                    $data->origen->provinciaId,
                    $data->destino->provinciaId,
                    null,
                    null
                );
                $resultado->tiempoTroncalMin = $tTR['min'];
                $resultado->tiempoTroncalMax = $tTR['max'];
            }

            // — Última milla (solo si el cliente solicitó entrega a domicilio)
            if ($data->destino->solicitaEntrega && $costoUltimaMilla > 0) {
                $tUM = $this->tiempoEntregaService->resolver(
                    $data->destino->provinciaId,
                    $data->destino->provinciaId,
                    null,
                    $data->destino->localidadId ?? null
                );
                $resultado->tiempoUltimaMillaMin = $tUM['min'];
                $resultado->tiempoUltimaMillaMax = $tUM['max'];
            }

            // — Total: suma de tramos disponibles
            $totalMin = ($resultado->tiempoPrimeraMillaMin ?? 0)
                      + ($resultado->tiempoTroncalMin      ?? 0)
                      + ($resultado->tiempoUltimaMillaMin  ?? 0);
            $totalMax = ($resultado->tiempoPrimeraMillaMax ?? 0)
                      + ($resultado->tiempoTroncalMax      ?? 0)
                      + ($resultado->tiempoUltimaMillaMax  ?? 0);

            // Solo asignamos si al menos un tramo tiene dato
            $hayTiempo = ($resultado->tiempoPrimeraMillaMin !== null)
                      || ($resultado->tiempoTroncalMin      !== null)
                      || ($resultado->tiempoUltimaMillaMin  !== null);

            if ($hayTiempo) {
                $resultado->tiempoMin = $totalMin > 0 ? $totalMin : null;
                $resultado->tiempoMax = $totalMax > 0 ? $totalMax : null;
            }

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

            // Estado final: si hay estimado, mantenemos OK pero con esEstimado=true
            // El frontend diferencia visualmente OK vs OK+esEstimado
            $resultado->estado = 'OK';

            // Desglose de cálculo para la vista admin
            $resultado->desglose = [
                'tramos'  => array_values($this->desgloseTramos),
                'pipeline' => [
                    'subtotal_tramos'     => round($costoPrimeraMilla + $costoTroncal + $costoUltimaMilla + $costoPuertaPuerta, 2),
                    'carga_descarga'      => $resultado->costoCargaDescarga,
                    'seguro'              => $resultado->costoSeguro,
                    'margen_porcentaje'   => $resultado->margenPorcentaje,
                    'margen_monto'        => $resultado->margenMonto,
                    'descuento_porcentaje'=> $resultado->descuentoPorcentaje,
                    'descuento_monto'     => $resultado->descuentoMonto,
                    'iva'                 => $resultado->iva,
                    'total_final'         => $resultado->totalFinal,
                    'formula'             => 'total = (tramos + carga/descarga + seguro + margen) − descuento + IVA',
                ],
            ];

        } catch (\Throwable $e) {
            Log::error('[CotizadorService] Error al calcular cotización', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            // No devolvemos ATENCION_PERSONALIZADA en excepciones:
            // marcamos esEstimado=true con precio $0 para que el usuario
            // siempre pueda avanzar y SET Logística lo contacte.
            $resultado->esEstimado = true;
            $resultado->estado     = 'OK';
            $resultado->errores[]  = 'No pudimos calcular el precio automáticamente. SET Logística te confirmará el valor final.';
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

        $porUnidad = fn (string $codigo) => $tarifas
            ->filter(fn ($t) => optional($t->unidadMedida)->codigo === $codigo)->values();

        // ── Métricas de la carga ──────────────────────────────────────────
        $cantidadBultos  = 0;
        $cantidadPallets = 0;
        $maxM3PorBulto   = 0.0;
        $maxPesoPorBulto = 0.0;
        foreach ($bultos as $b) {
            if (! $b instanceof BultoData) {
                continue;
            }
            $cantidadBultos += $b->cantidad;
            if ($b->paletizado) {
                $cantidadPallets += $b->cantidad;
            }
            $maxM3PorBulto   = max($maxM3PorBulto, ($b->largoCm / 100) * ($b->anchoCm / 100) * ($b->altoCm / 100));
            $maxPesoPorBulto = max($maxPesoPorBulto, $b->pesoKg);
        }

        $candidatos = [];   // unidad => ['tarifa' => Tarifa, 'cantidad' => float, 'costo' => float]

        // Rango de unidades de una tarifa: "desde min_unidades hasta max_unidades" (NULL = abierto; min NULL = 1).
        $cubreUnidades = fn ($t, int $n) => $n >= (int) ($t->min_unidades ?? 1)
            && ($t->max_unidades === null || $n <= (int) $t->max_unidades);

        // BULTO: importan cantidad (rango), peso y m³ por bulto contra los límites de la tarifa.
        // Si la carga supera los límites de una tarifa se prueba la siguiente (ordenadas de menor a mayor).
        // (Un bulto de 200 m³ NO son 200 bultos: se compara contra max_m3_bulto.)
        $tarifasBulto = $porUnidad('BULTO');
        if ($tarifasBulto->isNotEmpty() && $cantidadBultos > 0) {
            $tarifaBulto = $tarifasBulto
                ->sortBy([
                    fn ($a, $b) => ((int) ($a->min_unidades ?? 1)) <=> ((int) ($b->min_unidades ?? 1)),
                    fn ($a, $b) => ($a->max_unidades ?? INF) <=> ($b->max_unidades ?? INF),
                    fn ($a, $b) => ($a->max_m3_bulto ?? INF) <=> ($b->max_m3_bulto ?? INF),
                    fn ($a, $b) => ($a->max_kg_bulto ?? INF) <=> ($b->max_kg_bulto ?? INF),
                ])
                ->first(function ($t) use ($cantidadBultos, $maxM3PorBulto, $maxPesoPorBulto, $cubreUnidades) {
                    return $cubreUnidades($t, $cantidadBultos)
                        && ($t->min_m3_bulto === null || $maxM3PorBulto >= (float) $t->min_m3_bulto)
                        && ($t->max_m3_bulto === null || $maxM3PorBulto <= (float) $t->max_m3_bulto)
                        && ($t->max_kg_bulto === null || $maxPesoPorBulto <= (float) $t->max_kg_bulto);
                });

            if ($tarifaBulto) {
                $candidatos['BULTO'] = [
                    'tarifa' => $tarifaBulto, 'cantidad' => (float) $cantidadBultos,
                    'costo'  => round((float) $tarifaBulto->costo_unitario * $cantidadBultos, 2),
                ];
            }
        }

        // PALLET (también por rango de unidades)
        $tarifasPallet = $porUnidad('PALLET')->filter(fn ($t) => $cubreUnidades($t, max(1, $cantidadPallets)))->values();
        if ($tarifasPallet->isNotEmpty() && $cantidadPallets > 0) {
            $t = $this->escalonResolver->seleccionarEscalon($tarifasPallet, (float) $cantidadPallets);
            if ($t) {
                $candidatos['PALLET'] = ['tarifa' => $t, 'cantidad' => (float) $cantidadPallets,
                    'costo' => round((float) $t->costo_unitario * $cantidadPallets, 2)];
            }
        }

        // GRANEL (m³): solo importa el volumen
        $tarifasM3 = $porUnidad('M3');
        if ($tarifasM3->isNotEmpty() && $volumenTotalM3 > 0) {
            $t = $this->escalonResolver->seleccionarEscalon($tarifasM3, $volumenTotalM3);
            if ($t) {
                $candidatos['M3'] = ['tarifa' => $t, 'cantidad' => $volumenTotalM3,
                    'costo' => round((float) $t->costo_unitario * $volumenTotalM3, 2)];
            }
        }

        // KG: solo importa el peso
        $tarifasKg = $porUnidad('KG');
        if ($tarifasKg->isNotEmpty() && $pesoTotalKg > 0) {
            $t = $this->escalonResolver->seleccionarEscalon($tarifasKg, $pesoTotalKg);
            if ($t) {
                $candidatos['KG'] = ['tarifa' => $t, 'cantidad' => $pesoTotalKg,
                    'costo' => round((float) $t->costo_unitario * $pesoTotalKg, 2)];
            }
        }

        if ($candidatos === []) {
            return null;   // sin tarifa aplicable → el caller deriva a atención personalizada
        }

        // Prioridad: BULTO / PALLET (precio por unidad logística) y, si no, el mayor entre M3 y KG (aforo).
        if (isset($candidatos['BULTO'])) {
            $unidadCobro = 'BULTO';
        } elseif (isset($candidatos['PALLET'])) {
            $unidadCobro = 'PALLET';
        } else {
            $unidadCobro = collect($candidatos)->sortByDesc('costo')->keys()->first();
        }

        $elegido = $candidatos[$unidadCobro];
        $costoBruto = $elegido['costo'];

        // Mínimo del tramo
        $tipoServicioId = optional($elegido['tarifa']->tipoServicio)->id
                       ?? TipoServicio::where('codigo', $tipoTramo)->value('id');

        $costo = $this->minimoService->aplicar(
            $costoBruto,
            $provinciaOrigenId,
            $provinciaDestinoId,
            $tipoServicioId ?? 0,
            $localidadId
        );

        $tarifa = $elegido['tarifa'];
        $this->desgloseTramos[$tipoTramo] = [
            'tramo'            => $tipoTramo,
            'unidad_cobro'     => $unidadCobro,
            'tarifa_id'        => $tarifa->id,
            'costo_unitario'   => (float) $tarifa->costo_unitario,
            'cantidad'         => round($elegido['cantidad'], 3),
            'limites_tarifa'   => [
                'maximo'       => $tarifa->maximo !== null ? (float) $tarifa->maximo : null,
                'max_m3_bulto' => $tarifa->max_m3_bulto !== null ? (float) $tarifa->max_m3_bulto : null,
                'max_unidades' => $tarifa->max_unidades,
                'min_unidades' => $tarifa->min_unidades,
                'min_m3_bulto' => $tarifa->min_m3_bulto !== null ? (float) $tarifa->min_m3_bulto : null,
                'max_kg_bulto' => $tarifa->max_kg_bulto !== null ? (float) $tarifa->max_kg_bulto : null,
            ],
            'formula'          => sprintf('%s × %s = %s', $tarifa->costo_unitario, round($elegido['cantidad'], 3), number_format($costoBruto, 2, '.', '')),
            'costo_bruto'      => $costoBruto,
            'minimo_aplicado'  => $costo > $costoBruto,
            'costo_final'      => round($costo, 2),
            'alternativas'     => collect($candidatos)->map(fn ($c) => $c['costo'])->all(),
            'medidas'          => [
                'volumen_total_m3' => round($volumenTotalM3, 3),
                'peso_total_kg'    => round($pesoTotalKg, 2),
                'bultos'           => $cantidadBultos,
                'm3_max_por_bulto' => round($maxM3PorBulto, 3),
            ],
        ];

        return $costo;
    }

    /**
     * ID del tipo_cliente PUBLICO (para usuarios sin login).
     */
    private function tipoClientePublicoId(): int
    {
        return \App\Models\TipoCliente::where('codigo', 'PUBLICO')->value('id') ?? 1;
    }
}
