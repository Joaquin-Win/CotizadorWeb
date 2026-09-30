<?php

namespace App\Services\Cotizador;

use App\Models\TiempoEstimado;

/**
 * Resuelve el tiempo estimado de entrega para una ruta.
 *
 * Busca el tiempo estimado con fallback de granularidad:
 *  1. Provincia origen + Provincia destino + Localidad origen + Localidad destino
 *  2. Provincia origen + Provincia destino + Localidad destino
 *  3. Provincia origen + Provincia destino
 *
 * Retorna null si no hay dato configurado para la ruta.
 */
class TiempoEntregaService
{
    /**
     * Resuelve el tiempo estimado de entrega para una ruta.
     *
     * @return array{min: int|null, max: int|null}
     */
    public function resolver(
        int   $provinciaOrigenId,
        int   $provinciaDestinoId,
        ?int  $localidadOrigenId  = null,
        ?int  $localidadDestinoId = null
    ): array {
        // Intento 1: todas las dimensiones
        if ($localidadOrigenId && $localidadDestinoId) {
            $tiempo = TiempoEstimado::where('provincia_origen_id', $provinciaOrigenId)
                ->where('provincia_destino_id', $provinciaDestinoId)
                ->where('localidad_origen_id', $localidadOrigenId)
                ->where('localidad_destino_id', $localidadDestinoId)
                ->whereNull('deleted_at')
                ->first();

            if ($tiempo) {
                return $this->formato($tiempo);
            }
        }

        // Intento 2: sin localidad origen
        if ($localidadDestinoId) {
            $tiempo = TiempoEstimado::where('provincia_origen_id', $provinciaOrigenId)
                ->where('provincia_destino_id', $provinciaDestinoId)
                ->whereNull('localidad_origen_id')
                ->where('localidad_destino_id', $localidadDestinoId)
                ->whereNull('deleted_at')
                ->first();

            if ($tiempo) {
                return $this->formato($tiempo);
            }
        }

        // Intento 3: solo provincias
        $tiempo = TiempoEstimado::where('provincia_origen_id', $provinciaOrigenId)
            ->where('provincia_destino_id', $provinciaDestinoId)
            ->whereNull('localidad_origen_id')
            ->whereNull('localidad_destino_id')
            ->whereNull('deleted_at')
            ->first();

        return $tiempo ? $this->formato($tiempo) : ['min' => null, 'max' => null];
    }

    private function formato(TiempoEstimado $tiempo): array
    {
        return [
            'min' => $tiempo->dias_min,
            'max' => $tiempo->dias_max,
        ];
    }
}
