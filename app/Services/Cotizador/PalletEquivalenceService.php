<?php

namespace App\Services\Cotizador;

use App\Data\Cotizador\BultoData;

/**
 * Calcula la equivalencia en pallets de una carga no palletizada.
 *
 * ESTADO: preparado para fórmula futura de SET.
 *
 * La lógica exacta de estiba será definida por SET Logística.
 * Por ahora este servicio retorna null (equivalencia desconocida).
 *
 * Cuando SET defina la regla, SOLO este archivo debe modificarse.
 * No existe lógica de estiba en AforoService ni en CotizadorService.
 *
 * TODO (SET pendiente): implementar regla de equivalencia pallet.
 * Ejemplo posible: 1 pallet = 1.10 m³ de volumen o 1000 kg de peso.
 */
class PalletEquivalenceService
{
    /**
     * Calcula cuántos pallets equivale un bulto no palletizado.
     *
     * Retorna null si la regla de estiba aún no está definida por SET.
     * El caller (CotizadorService) debe guardarlo como null en la BD.
     *
     * @param  float $volumenM3     Volumen total del conjunto de bultos
     * @param  float $pesoTotalKg   Peso total del conjunto de bultos
     * @return float|null           Pallets equivalentes o null si no aplica
     */
    public function calcular(float $volumenM3, float $pesoTotalKg): ?float
    {
        /*
         * PLACEHOLDER: SET aún no definió la fórmula oficial.
         * Descomentar y ajustar cuando SET entregue la especificación.
         *
         * Ejemplo de implementación futura:
         *   $m3PorPallet  = 1.10;  // m³ por pallet estándar
         *   $kgPorPallet  = 1000;  // kg por pallet estándar
         *
         *   $palletsPorM3  = $volumenM3  / $m3PorPallet;
         *   $palletsPorKg  = $pesoTotalKg / $kgPorPallet;
         *
         *   return round(max($palletsPorM3, $palletsPorKg), 2);
         */

        return null;
    }
}
