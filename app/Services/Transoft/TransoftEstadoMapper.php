<?php

namespace App\Services\Transoft;

use App\Models\TransoftEstado;

/**
 * Mapea códigos de estado de Transoft a estados internos de SET.
 *
 * La tabla `transoft_estados` ya contiene la configuración de mapeo.
 * Este mapper es un punto de acceso centralizado para esa lógica.
 *
 * Referencia documental: Transoftweb Integraciones v1.4.1
 *
 * Códigos Transoft conocidos (ejemplos — verificar con documentación oficial):
 *   INGR  = Ingresada
 *   RTRD  = En retiro
 *   RTDO  = Retirada
 *   ENTR  = En tránsito
 *   DEST  = En destino
 *   ENTG  = Entregada
 *   DEVL  = Devuelta
 *   CANC  = Cancelada
 */
class TransoftEstadoMapper
{
    /**
     * Resuelve el modelo TransoftEstado a partir de un código Transoft.
     *
     * Retorna null si el código no está configurado en la tabla.
     *
     * @param  string $codigoTransoft  Código de estado Transoft (ej: 'ENTG')
     * @return TransoftEstado|null
     */
    public function resolver(string $codigoTransoft): ?TransoftEstado
    {
        return TransoftEstado::find($codigoTransoft);
    }

    /**
     * Retorna la descripción legible de un código Transoft.
     *
     * Si no existe en la tabla, retorna el código tal cual.
     *
     * @param  string $codigoTransoft
     * @return string
     */
    public function descripcion(string $codigoTransoft): string
    {
        $estado = $this->resolver($codigoTransoft);
        return $estado?->descripcion ?? $codigoTransoft;
    }

    /**
     * Indica si el estado es final (no habrá más actualizaciones para esta carga).
     *
     * @param  string $codigoTransoft
     * @return bool
     */
    public function esFinal(string $codigoTransoft): bool
    {
        $estado = $this->resolver($codigoTransoft);
        return $estado?->es_final ?? false;
    }

    /**
     * Retorna el id del estado de cotización SET asociado al estado Transoft.
     *
     * @param  string $codigoTransoft
     * @return int|null
     */
    public function estadoCotizacionId(string $codigoTransoft): ?int
    {
        $estado = $this->resolver($codigoTransoft);
        return $estado?->estado_cotizacion_id;
    }
}
