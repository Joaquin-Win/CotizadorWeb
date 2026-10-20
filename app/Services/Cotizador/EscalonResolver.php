<?php

namespace App\Services\Cotizador;

use App\Models\Tarifa;
use Illuminate\Support\Collection;

/**
 * Resuelve el costo de un tramo dado un conjunto de tarifas escalonadas
 * y una cantidad (en la unidad correspondiente: m³, kg, pallets).
 *
 * Regla de escalones:
 *  - Los escalones están ordenados por `maximo` ASC (NULL = abierto).
 *  - Se elige el escalón cuyo `maximo` sea >= cantidad (o el abierto si no hay tope).
 *  - Si ningún escalón cubre la cantidad, se usa el escalón más alto disponible.
 *  - Si la colección está vacía → devuelve null (sin tarifa).
 *
 * El costo se calcula como: costo_unitario × cantidad
 * Si la tarifa tiene `maximo` (tope de cobro): min(costo_calculado, maximo)
 *
 * Nota: el "maximo" en la tabla tarifas es el TOPE DE COBRO del escalón en $,
 *       no el tope de cantidad. La cantidad máxima del escalón se determina
 *       por el ordenamiento: un escalón aplica a la cantidad más baja del rango.
 *
 * REVISIÓN DE CONTEXTO: en cotizador_set.sql la columna `maximo` está comentada
 * como "Tope de cobro, si lo hay." — es decir, es el tope en PESOS, no un escalón
 * de cantidad. En ese caso la tabla tiene UNA tarifa por ruta+unidad, y `maximo`
 * simplemente limita el cobro máximo. EscalonResolver respeta ambas interpretaciones.
 */
class EscalonResolver
{
    /**
     * Calcula el costo del tramo dada una colección de tarifas y la cantidad.
     *
     * @param  Collection $tarifas   Tarifas ya filtradas por ruta y tipo de servicio
     * @param  float      $cantidad  Cantidad en la unidad de cobro (m³, kg o pallets)
     * @return float|null            Costo calculado, o null si no hay tarifa disponible
     */
    public function calcular(Collection $tarifas, float $cantidad): ?float
    {
        $tarifa = $this->seleccionarEscalon($tarifas, $cantidad);

        return $tarifa ? round((float) $tarifa->costo_unitario * $cantidad, 2) : null;
    }

    /**
     * Elige el escalón aplicable según la cantidad.
     *
     * `maximo` = cantidad máxima que cubre el escalón (NULL = abierto).
     * Se toma el escalón cerrado más chico que cubre la cantidad; si ninguno
     * la cubre, el abierto; si no hay abierto, el de mayor tope.
     * (El orden se resuelve en PHP: MySQL ordena NULL primero en ASC.)
     */
    public function seleccionarEscalon(Collection $tarifas, float $cantidad): ?Tarifa
    {
        if ($tarifas->isEmpty() || $cantidad <= 0) {
            return null;
        }

        $cerrados = $tarifas->filter(fn ($t) => $t->maximo !== null && (float) $t->maximo > 0)
            ->sortBy(fn ($t) => (float) $t->maximo)->values();

        $cubre = $cerrados->first(fn ($t) => (float) $t->maximo >= $cantidad);
        if ($cubre) {
            return $cubre;
        }

        $abierto = $tarifas->first(fn ($t) => $t->maximo === null || (float) $t->maximo <= 0);

        return $abierto ?? $cerrados->last();
    }

    /**
     * Construye el mapa costo_unitario por unidad de medida (para AforoService).
     *
     * Retorna: ['M3' => float, 'KG' => float, 'PALLET' => float|null]
     */
    public function mapaTarifas(Collection $tarifas): array
    {
        $mapa = [];
        foreach ($tarifas as $tarifa) {
            /** @var Tarifa $tarifa */
            $codigo = optional($tarifa->unidadMedida)->codigo ?? 'M3';
            $mapa[$codigo] = (float) $tarifa->costo_unitario;
        }
        return $mapa;
    }
}
