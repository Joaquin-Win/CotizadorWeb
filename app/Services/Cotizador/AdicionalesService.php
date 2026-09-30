<?php

namespace App\Services\Cotizador;

use App\Models\CostoAdicional;

/**
 * Calcula los costos adicionales (carga, descarga, etc.) aplicables a una cotización.
 *
 * Los costos adicionales se identifican por nombre en la tabla costos_adicionales.
 * El admin puede editar sus montos desde el panel.
 *
 * Tipo de unidad:
 *  '$' → monto fijo en pesos
 *  '%' → porcentaje sobre el subtotal_flete
 *
 * IMPORTANTE: los costos adicionales obligatorios (es_obligatorio = true, NO existe
 * en el schema real — se usa el nombre para identificarlos) se agregan siempre.
 * Los opcionales (carga/descarga) solo si el cliente los solicitó.
 */
class AdicionalesService
{
    /**
     * Calcula el total de costos adicionales aplicables.
     *
     * @param  bool  $solicitaCarga     El cliente solicitó servicio de carga
     * @param  bool  $solicitaDescarga  El cliente solicitó servicio de descarga
     * @param  float $subtotalFlete     Para calcular adicionales en porcentaje
     * @return array{total: float, items: array}
     */
    public function calcular(
        bool  $solicitaCarga,
        bool  $solicitaDescarga,
        float $subtotalFlete
    ): array {
        $costos = CostoAdicional::activo()->get();

        $total = 0;
        $items = [];

        foreach ($costos as $costo) {
            $aplicar = false;

            // Identificamos carga/descarga por nombre (según seeder y schema real)
            $nombre = strtolower($costo->nombre);
            if ($nombre === 'carga' && $solicitaCarga) {
                $aplicar = true;
            } elseif ($nombre === 'descarga' && $solicitaDescarga) {
                $aplicar = true;
            } elseif ($nombre !== 'carga' && $nombre !== 'descarga') {
                // Cualquier otro costo activo se aplica siempre
                $aplicar = true;
            }

            if (! $aplicar) {
                continue;
            }

            $monto = $this->calcularMonto($costo->monto, $costo->unidad, $subtotalFlete);
            $total += $monto;

            $items[] = [
                'id'              => $costo->id,
                'nombre'          => $costo->nombre,
                'unidad'          => $costo->unidad,
                'monto_calculado' => $monto,
            ];
        }

        return [
            'total' => round($total, 2),
            'items' => $items,
        ];
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    private function calcularMonto(float $monto, string $unidad, float $subtotal): float
    {
        if ($unidad === '%') {
            return round($subtotal * ($monto / 100), 2);
        }

        // '$' → monto fijo
        return $monto;
    }
}
