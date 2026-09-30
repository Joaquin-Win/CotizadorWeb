<?php

namespace App\Services\Cotizador;

use App\Models\ConfiguracionCotizador;

/**
 * Calcula el costo del seguro sobre el valor declarado del envío.
 *
 * El porcentaje se lee desde la tabla configuracion_cotizador (clave: seguro_porcentaje),
 * editable por el admin desde el panel.
 *
 * Regla:
 *  - Si no hay valor declarado → seguro = 0
 *  - Si el acuerdo incluye el seguro → seguro = 0 (se cobra por separado en el acuerdo)
 *  - Caso normal: seguro = valor_declarado × (porcentaje / 100)
 */
class SeguroService
{
    /**
     * Calcula el costo del seguro.
     *
     * @param  float|null $valorDeclarado   Valor declarado del envío en $
     * @param  bool       $incluidoEnAcuerdo Si true, el seguro está cubierto por el acuerdo
     * @return float                        Costo del seguro en $
     */
    public function calcular(?float $valorDeclarado, bool $incluidoEnAcuerdo = false): float
    {
        if ($incluidoEnAcuerdo || ! $valorDeclarado || $valorDeclarado <= 0) {
            return 0;
        }

        $porcentaje = ConfiguracionCotizador::numero(
            'seguro_porcentaje',
            config('cotizador.defaults.seguro_porcentaje', 0.80)
        );

        return round($valorDeclarado * ($porcentaje / 100), 2);
    }

    /**
     * Retorna el porcentaje de seguro vigente (para mostrar en el resultado).
     */
    public function porcentajeVigente(): float
    {
        return ConfiguracionCotizador::numero(
            'seguro_porcentaje',
            config('cotizador.defaults.seguro_porcentaje', 0.80)
        );
    }
}
