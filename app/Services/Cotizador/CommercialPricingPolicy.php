<?php

namespace App\Services\Cotizador;

use App\Data\Cotizador\ResultadoCotizacion;

/**
 * CommercialPricingPolicy — define y aplica el orden del pipeline de precios.
 *
 * El pipeline es FIJO en este orden:
 *
 *  1.  Calcular subtotal de tramos (TRONCAL + PRIMERA_MILLA + ULTIMA_MILLA ó PUERTA_PUERTA)
 *  2.  Aplicar mínimos por tramo
 *  3.  Calcular costos adicionales (carga/descarga + otros activos)
 *  4.  Subtotal flete = suma de tramos + adicionales
 *  5.  Calcular seguro sobre valor declarado
 *  6.  Calcular margen de ganancia sobre subtotal flete + seguro
 *  7.  Aplicar descuento del acuerdo comercial o tipo_cliente
 *  8.  Calcular IVA (actualmente 0% — tarifas son precio final)
 *  9.  Total final
 *
 * Este servicio recibe el ResultadoCotizacion parcialmente construido
 * (con tramos ya calculados) y aplica los pasos 4–9.
 */
class CommercialPricingPolicy
{
    public function __construct(
        private readonly SeguroService           $seguroService,
        private readonly MargenService           $margenService,
        private readonly AcuerdoComercialService $acuerdoService,
    ) {}

    /**
     * Aplica el pipeline completo de precios y retorna el ResultadoCotizacion finalizado.
     *
     * @param  ResultadoCotizacion $resultado  DTO con tramos ya calculados
     * @param  array               $context    Contexto: tipoClienteId, tipoServicioId, etc.
     * @return ResultadoCotizacion             DTO con todos los campos calculados
     */
    public function aplicar(ResultadoCotizacion $resultado, array $context): ResultadoCotizacion
    {
        // Paso 4: subtotal_flete = suma de todos los tramos
        // (Los tramos ya vienen calculados en el DTO)
        $subtotalTramos = $resultado->costoPrimeraMilla
                        + $resultado->costoTroncal
                        + $resultado->costoUltimaMilla
                        + $resultado->costoPuertaPuerta;

        // El subtotal inicial de flete (sin adicionales)
        $subtotalFlete = $subtotalTramos;

        // Paso 5: seguro (sobre valor declarado, no sobre subtotal)
        $seguroIncluido = $context['seguro_incluido'] ?? false;
        $costoSeguro    = $this->seguroService->calcular(
            $context['valor_declarado'] ?? null,
            $seguroIncluido
        );
        $resultado->costoSeguro = $costoSeguro;

        // Paso 6: margen de ganancia
        $datosMargen = $this->margenService->calcular(
            $subtotalFlete,
            $context['tipo_cliente_id'],
            $context['tipo_servicio_id'] ?? null
        );
        $margenMonto      = $datosMargen['monto'];
        $margenPorcentaje = $datosMargen['porcentaje'];
        $margenModel      = $datosMargen['margen'];

        // Subtotal antes de descuento = flete + adicionales + seguro + margen
        $subtotalConMargen = $subtotalFlete
                           + $resultado->costoCargaDescarga
                           + $costoSeguro
                           + $margenMonto;

        // Paso 7: descuento
        $descuentoPorcentaje = $context['descuento_porcentaje'] ?? 0;
        $descuentoMonto      = $this->acuerdoService->calcularMontoDescuento(
            $subtotalConMargen,
            $descuentoPorcentaje
        );

        // Paso 8: IVA
        $baseIva     = $subtotalConMargen - $descuentoMonto;
        $ivaPorcentaje = (float) ($context['iva_porcentaje'] ?? 0);
        $ivaMonto    = round($baseIva * ($ivaPorcentaje / 100), 2);

        // Paso 9: total final
        $totalFinal = $baseIva + $ivaMonto;

        // Completar el DTO
        $resultado->subtotalFlete      = round($subtotalFlete, 2);
        $resultado->margenMonto        = $margenMonto;
        $resultado->margenPorcentaje   = $margenPorcentaje;
        $resultado->margenId           = $margenModel?->id;
        $resultado->descuentoPorcentaje = $descuentoPorcentaje;
        $resultado->descuentoMonto     = $descuentoMonto;
        $resultado->iva                = $ivaMonto;
        $resultado->totalFinal         = round(max(0, $totalFinal), 2);

        return $resultado;
    }
}
