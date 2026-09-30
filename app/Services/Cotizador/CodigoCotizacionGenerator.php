<?php

namespace App\Services\Cotizador;

use Illuminate\Support\Str;

/**
 * Genera el código público único de una cotización.
 *
 * Formato según especificación: SET-{AÑO}-{6 chars alfanuméricos mayúsculas}
 * Ejemplo: SET-2026-A3X9KQ
 *
 * Garantiza unicidad verificando contra la tabla cotizacion_codigos.
 * Máximo 10 intentos antes de lanzar excepción (probabilidad de colisión ínfima).
 */
class CodigoCotizacionGenerator
{
    /**
     * Genera un código único para la cotización.
     *
     * @throws \RuntimeException Si no puede generar un código único en 10 intentos
     */
    public function generar(): string
    {
        $anio = now()->year;
        $maxIntentos = 10;

        for ($i = 0; $i < $maxIntentos; $i++) {
            $sufijo = strtoupper(Str::random(6));
            $codigo = "SET-{$anio}-{$sufijo}";

            if ($this->esUnico($codigo)) {
                return $codigo;
            }
        }

        throw new \RuntimeException(
            "No se pudo generar un código de cotización único después de {$maxIntentos} intentos."
        );
    }

    /**
     * Verifica que el código no exista en cotizacion_codigos ni en cotizaciones.
     */
    private function esUnico(string $codigo): bool
    {
        // Verificar en tabla cotizacion_codigos (PK)
        $existeEnCodigos = \App\Models\CotizacionCodigo::where('codigo', $codigo)->exists();

        if ($existeEnCodigos) {
            return false;
        }

        // Verificar en tabla cotizaciones (columna codigo, recién agregada)
        $existeEnCotizacion = \App\Models\Cotizacion::where('codigo', $codigo)->exists();

        return ! $existeEnCotizacion;
    }
}
