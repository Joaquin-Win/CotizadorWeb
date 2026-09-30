<?php

namespace App\Services\Cotizador;

use App\Data\Cotizador\BultoData;

/**
 * Calcula las medidas de un bulto y determina la unidad de cobro.
 *
 * La unidad de cobro se determina por la regla de aforo:
 * - Si el bulto está palletizado → unidad PALLET (siempre que exista tarifa PALLET)
 * - Si no está palletizado → comparar costo por M3 vs costo por KG (toneladas), usar el mayor
 */
class AforoService
{
    /**
     * Calcula el volumen en m³ de un BultoData.
     */
    public function calcularVolumenM3(BultoData $bulto): float
    {
        return ($bulto->largoCm / 100)
             * ($bulto->anchoCm / 100)
             * ($bulto->altoCm  / 100)
             * $bulto->cantidad;
    }

    /**
     * Calcula el peso total en kg de un BultoData.
     */
    public function calcularPesoTotalKg(BultoData $bulto): float
    {
        return $bulto->pesoKg * $bulto->cantidad;
    }

    /**
     * Calcula las toneladas métricas de un BultoData.
     */
    public function calcularToneladas(BultoData $bulto): float
    {
        return $this->calcularPesoTotalKg($bulto) / 1000;
    }

    /**
     * Determina la unidad de cobro a usar para un bulto dado
     * las tarifas disponibles (en formato ['codigo' => costo_unitario]).
     *
     * Retorna: 'PALLET' | 'M3' | 'KG'
     *
     * Regla:
     *  - Palletizado + tarifa PALLET disponible → PALLET
     *  - No palletizado → MAX(costo_m3, costo_tn)
     *    - costo_m3 = volumen_m3 × tarifa_m3
     *    - costo_tn = toneladas × tarifa_kg (la tarifa KG aplica por tonelada cuando se cobran KG)
     *
     * Importante: esta función NO calcula el costo final; solo decide la unidad.
     * El costo real lo calcula EscalonResolver usando la unidad devuelta aquí.
     */
    public function determinarUnidadCobro(
        BultoData $bulto,
        array $tarifasPorUnidad  // ['M3' => float, 'KG' => float, 'PALLET' => float|null]
    ): string {
        // Bulto palletizado con tarifa PALLET disponible → usar PALLET
        if ($bulto->paletizado && isset($tarifasPorUnidad['PALLET'])) {
            return 'PALLET';
        }

        // Para bultos no palletizados: comparar M3 vs KG
        $tarifaM3 = $tarifasPorUnidad['M3'] ?? 0;
        $tarifaKg = $tarifasPorUnidad['KG'] ?? 0;

        if ($tarifaM3 <= 0 && $tarifaKg <= 0) {
            // Sin tarifas disponibles — el caller debe manejar este caso (ATENCION_PERSONALIZADA)
            return 'M3';
        }

        $volumenM3  = $this->calcularVolumenM3($bulto);
        $toneladas  = $this->calcularToneladas($bulto);

        $costoM3 = $volumenM3  * $tarifaM3;
        $costoTn = $toneladas  * $tarifaKg;

        // Usar la unidad que genere mayor costo (aforo: cobra lo que más pesa)
        return $costoM3 >= $costoTn ? 'M3' : 'KG';
    }
}
