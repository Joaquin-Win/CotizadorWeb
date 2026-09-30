<?php

namespace App\Services\Cotizador;

use App\Models\AcuerdoComercial;
use App\Models\Cliente;

/**
 * Resuelve el acuerdo comercial vigente para un cliente y calcula
 * descuentos y recargos.
 *
 * Prioridad de descuento:
 *   1. Acuerdo comercial vigente del cliente (si tiene)
 *   2. Descuento base del tipo_cliente (descuento_base_porcentaje)
 *   3. Sin descuento (0%)
 *
 * El recargo solo viene del acuerdo comercial.
 */
class AcuerdoComercialService
{
    /**
     * Obtiene el acuerdo comercial vigente del cliente, si aplica.
     *
     * Regla: el cliente debe tener tipo_cliente con permite_acuerdo_comercial = true.
     */
    public function acuerdoVigente(?int $clienteId): ?AcuerdoComercial
    {
        if (! $clienteId) {
            return null;
        }

        $cliente = Cliente::with('tipoCliente')->find($clienteId);

        if (! $cliente) {
            return null;
        }

        // Solo clientes cuyo tipo permite acuerdo comercial
        if (! $cliente->tipoCliente?->permite_acuerdo_comercial) {
            return null;
        }

        return $cliente->acuerdoVigente();
    }

    /**
     * Calcula el porcentaje de descuento neto que se debe aplicar.
     *
     * Retorna: descuento en porcentaje (ej: 15.00 = -15%)
     * El recargo resta del descuento (si recargo > descuento → no hay descuento neto).
     */
    public function calcularDescuentoNeto(?AcuerdoComercial $acuerdo, ?int $clienteId): float
    {
        if ($acuerdo) {
            $descuento = (float) $acuerdo->descuento_porcentaje;
            $recargo   = (float) $acuerdo->recargo_porcentaje;
            return max(0, $descuento - $recargo);
        }

        // Sin acuerdo: usar descuento base del tipo_cliente
        if ($clienteId) {
            $cliente = Cliente::with('tipoCliente')->find($clienteId);
            return (float) ($cliente?->tipoCliente?->descuento_base_porcentaje ?? 0);
        }

        return 0;
    }

    /**
     * Calcula el monto de descuento sobre el subtotal.
     */
    public function calcularMontoDescuento(float $subtotal, float $descuentoPorcentaje): float
    {
        if ($descuentoPorcentaje <= 0 || $subtotal <= 0) {
            return 0;
        }
        return round($subtotal * ($descuentoPorcentaje / 100), 2);
    }

    /**
     * Indica si el seguro está incluido en el acuerdo (no se cobra aparte).
     */
    public function tieneSeguroIncluido(?AcuerdoComercial $acuerdo): bool
    {
        return $acuerdo?->tieneSeguroIncluido() ?? false;
    }
}
