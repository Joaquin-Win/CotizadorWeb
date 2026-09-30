<?php

namespace App\Services\Cotizador;

use App\Models\TarifaMinima;

/**
 * Valida y aplica el mínimo de cotización para un tramo.
 *
 * Si el costo calculado del tramo es menor al mínimo configurado para la ruta,
 * se reemplaza por el mínimo.
 *
 * Busca el mínimo con granularidad de mayor a menor:
 *  1. Provincia origen + Provincia destino + Localidad destino + Tipo servicio
 *  2. Provincia origen + Provincia destino + Tipo servicio (sin localidad)
 */
class MinimoService
{
    /**
     * Aplica el mínimo de tarifa al costo calculado.
     *
     * @param  float  $costoCalculado     Costo bruto del tramo
     * @param  int    $provinciaOrigenId
     * @param  int    $provinciaDestinoId
     * @param  int    $tipoServicioId
     * @param  int|null $localidadDestinoId
     * @return float  El mayor entre el costo calculado y el mínimo configurado
     */
    public function aplicar(
        float $costoCalculado,
        int   $provinciaOrigenId,
        int   $provinciaDestinoId,
        int   $tipoServicioId,
        ?int  $localidadDestinoId = null
    ): float {
        $minimo = $this->obtenerMinimo(
            $provinciaOrigenId,
            $provinciaDestinoId,
            $tipoServicioId,
            $localidadDestinoId
        );

        if ($minimo === null) {
            return $costoCalculado;
        }

        return max($costoCalculado, $minimo);
    }

    /**
     * Obtiene el monto mínimo para la ruta, intentando de más específico a más general.
     */
    public function obtenerMinimo(
        int   $provinciaOrigenId,
        int   $provinciaDestinoId,
        int   $tipoServicioId,
        ?int  $localidadDestinoId = null
    ): ?float {
        // Intento 1: con localidad destino específica
        if ($localidadDestinoId) {
            $tarifa = TarifaMinima::where('provincia_origen_id', $provinciaOrigenId)
                ->where('provincia_destino_id', $provinciaDestinoId)
                ->where('tipo_servicio_id', $tipoServicioId)
                ->where('localidad_destino_id', $localidadDestinoId)
                ->whereNull('deleted_at')
                ->first();

            if ($tarifa) {
                return (float) $tarifa->monto_minimo;
            }
        }

        // Intento 2: solo a nivel de provincia
        $tarifa = TarifaMinima::where('provincia_origen_id', $provinciaOrigenId)
            ->where('provincia_destino_id', $provinciaDestinoId)
            ->where('tipo_servicio_id', $tipoServicioId)
            ->whereNull('localidad_destino_id')
            ->whereNull('deleted_at')
            ->first();

        return $tarifa ? (float) $tarifa->monto_minimo : null;
    }
}
