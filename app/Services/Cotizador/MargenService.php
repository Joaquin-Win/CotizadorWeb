<?php

namespace App\Services\Cotizador;

use App\Models\MargenGanancia;

/**
 * Calcula el margen de ganancia de SET sobre el subtotal de flete.
 *
 * Delega la resolución de la regla al método MargenGanancia::paraSegmento(),
 * que ya implementa la prioridad:
 *   tipo_cliente + tipo_servicio > solo tipo_cliente > global
 */
class MargenService
{
    /**
     * Calcula el monto de margen de ganancia para el subtotal dado.
     *
     * @param  float    $subtotalFlete     Subtotal bruto del flete (suma de tramos)
     * @param  int      $tipoClienteId     ID del tipo_cliente
     * @param  int|null $tipoServicioId    ID del tipo_servicio principal (puede ser null)
     * @return array{margen: MargenGanancia|null, porcentaje: float, monto: float}
     */
    public function calcular(
        float $subtotalFlete,
        int   $tipoClienteId,
        ?int  $tipoServicioId = null
    ): array {
        $margen = MargenGanancia::paraSegmento($tipoClienteId, $tipoServicioId);

        if (! $margen) {
            return [
                'margen'      => null,
                'porcentaje'  => 0,
                'monto'       => 0,
            ];
        }

        $porcentaje = (float) $margen->porcentaje;
        $monto      = round($subtotalFlete * ($porcentaje / 100), 2);

        return [
            'margen'      => $margen,
            'porcentaje'  => $porcentaje,
            'monto'       => $monto,
        ];
    }
}
