<?php

namespace App\Services\Cotizador;

use App\Data\Cotizador\CotizacionRequestData;
use App\Models\Proveedor;
use App\Models\TipoServicio;

/**
 * Determina qué tramos logísticos aplican para una cotización y en qué orden.
 *
 * Tramos posibles:
 *  - PUERTA_PUERTA:  aplica cuando origen y destino están informados con localidades
 *                    Y se solicita tanto retiro como entrega. Reemplaza a los tres tramos.
 *  - PRIMERA_MILLA:  aplica cuando solicita_retiro = true y hay localidad_origen
 *  - TRONCAL:        aplica siempre (provincia a provincia)
 *  - ULTIMA_MILLA:   aplica cuando solicita_entrega = true (sin retira_en_sucursal)
 *                    o hay localidad_destino informada
 *
 * Regla importante: PUERTA_PUERTA excluye a los tramos individuales.
 * Si no hay tarifa PUERTA_PUERTA, se intentan los tramos individuales.
 * (La decisión final la toma CotizadorService tras consultar TarifaResolver.)
 *
 * Este servicio solo informa QUÉ tramos son candidatos, no si existe tarifa para ellos.
 */
class RutaResolver
{
    /**
     * Retorna los tipos de tramo candidatos para la cotización, en orden de cálculo.
     *
     * Retorna array de códigos de tipos_servicio: ['TRONCAL', 'PRIMERA_MILLA', 'ULTIMA_MILLA']
     * o ['PUERTA_PUERTA'] si aplica como servicio unificado.
     */
    public function resolverTramos(CotizacionRequestData $data): array
    {
        $origen  = $data->origen;
        $destino = $data->destino;

        $solicitaRetiro  = $origen->solicitaRetiro ?? false;
        $solicitaEntrega = $destino->solicitaEntrega ?? false;
        $retiraEnSucursal = $destino->retiroEnSucursal ?? false;

        $tieneLocalidadOrigen  = isset($origen->localidadId)  && $origen->localidadId  > 0;
        $tieneLocalidadDestino = isset($destino->localidadId) && $destino->localidadId > 0;

        $tramos = [];

        // Primera milla: retiro a domicilio con localidad de origen informada
        if ($solicitaRetiro && $tieneLocalidadOrigen) {
            $tramos[] = 'PRIMERA_MILLA';
        }

        // Troncal: siempre
        $tramos[] = 'TRONCAL';

        // Última milla: entrega a domicilio (sin retira_en_sucursal)
        if ($solicitaEntrega && ! $retiraEnSucursal) {
            $tramos[] = 'ULTIMA_MILLA';
        }

        return $tramos;
    }

    /**
     * Indica si la configuración candidatea a PUERTA_PUERTA.
     * El caller (CotizadorService) verifica si existe tarifa disponible.
     */
    public function candidatoPuertaPuerta(CotizacionRequestData $data): bool
    {
        $origen  = $data->origen;
        $destino = $data->destino;

        return ($origen->solicitaRetiro  ?? false)
            && ($destino->solicitaEntrega ?? false)
            && isset($origen->localidadId)  && $origen->localidadId  > 0
            && isset($destino->localidadId) && $destino->localidadId > 0;
    }
}
