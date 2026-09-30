<?php

namespace App\Services\Transoft;

use App\Models\Pedido;
use App\Models\Seguimiento;
use App\Models\TransoftEstado;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Servicio de Tracking Transoft.
 *
 * Centraliza la consulta de estados y el guardado del historial
 * de seguimiento en la tabla `seguimientos`.
 *
 * Referencia documental: Transoftweb Integraciones v1.4.1
 *
 * Endpoints gestionados:
 *   GET /api/cargas/{tracking}/states/{username}/{operationId}
 *
 * Flujo típico:
 *   1. SET crea la carga en Transoft (TransoftCargaService::crearCarga).
 *   2. Transoft devuelve un CodigoSeguimiento (tracking).
 *   3. El tracking se guarda en pedidos.transoft_tracking.
 *   4. Este servicio consulta el estado del tracking periódicamente.
 *   5. El resultado se guarda en la tabla `seguimientos`.
 */
class TransoftTrackingService
{
    public function __construct(
        private readonly TransoftClient      $client,
        private readonly TransoftEstadoMapper $estadoMapper,
    ) {}

    // ---------------------------------------------------------------
    // Consulta de estados
    // ---------------------------------------------------------------

    /**
     * Consulta todos los estados de una carga en Transoft por tracking.
     *
     * GET /api/cargas/{tracking}/states/{username}/{operationId}
     *
     * Retorna el array crudo de estados de Transoft tal como viene de la API.
     * El caller decide cómo presentarlos o persistirlos.
     *
     * @param  string $tracking  Código de seguimiento Transoft
     * @return array             Lista de estados [ {codigo, descripcion, fecha}, ... ]
     *
     * @throws RuntimeException Si la llamada a la API falla
     */
    public function consultarEstados(string $tracking): array
    {
        Log::debug('[TransoftTrackingService] Consultando estados', ['tracking' => $tracking]);

        $response = $this->client->obtenerEstadosLegacy($tracking);

        // La API puede devolver un array directamente o anidado bajo Results
        if (isset($response['Results'])) {
            return $response['Results'];
        }

        return is_array($response) ? $response : [];
    }

    // ---------------------------------------------------------------
    // Sincronización de estados
    // ---------------------------------------------------------------

    /**
     * Sincroniza el estado más reciente de un pedido con Transoft.
     *
     * Pasos:
     *   1. Consulta la API de Transoft para obtener el estado más reciente.
     *   2. Mapea el código Transoft a un estado interno SET.
     *   3. Actualiza pedido.transoft_estado_codigo y transoft_sync_at.
     *
     * No guarda en la tabla seguimientos (eso es responsabilidad del Job/Queue).
     * No lanza excepción si el pedido no tiene tracking — retorna false silenciosamente.
     *
     * @param  Pedido $pedido
     * @return bool           True si se actualizó correctamente, false si no tenía tracking
     */
    public function sincronizarPedido(Pedido $pedido): bool
    {
        if (! $pedido->tieneSyncTransoft()) {
            Log::debug('[TransoftTrackingService] Pedido sin tracking, se omite sincronización.', [
                'pedido_id' => $pedido->id,
            ]);
            return false;
        }

        try {
            $estados = $this->consultarEstados($pedido->transoft_tracking);

            if (empty($estados)) {
                return false;
            }

            // El último estado es el más reciente
            $ultimo = end($estados);
            $codigoTransoft = $ultimo['Codigo'] ?? $ultimo['codigo'] ?? null;

            if ($codigoTransoft === null) {
                Log::warning('[TransoftTrackingService] Estado sin código en respuesta', [
                    'tracking' => $pedido->transoft_tracking,
                    'estado'   => $ultimo,
                ]);
                return false;
            }

            $pedido->update([
                'transoft_estado_codigo' => $codigoTransoft,
                'transoft_sync_at'       => now(),
            ]);

            Log::info('[TransoftTrackingService] Estado sincronizado', [
                'pedido_id'    => $pedido->id,
                'tracking'     => $pedido->transoft_tracking,
                'estado'       => $codigoTransoft,
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('[TransoftTrackingService] Error al sincronizar pedido', [
                'pedido_id' => $pedido->id,
                'tracking'  => $pedido->transoft_tracking,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ---------------------------------------------------------------
    // Obtener estado legible
    // ---------------------------------------------------------------

    /**
     * Retorna el estado Transoft más reciente de un pedido en formato legible.
     *
     * Útil para mostrar al cliente sin exponer el código interno.
     *
     * @param  Pedido $pedido
     * @return array{codigo: string|null, descripcion: string|null, es_final: bool}
     */
    public function estadoActual(Pedido $pedido): array
    {
        if (! $pedido->transoft_estado_codigo) {
            return [
                'codigo'      => null,
                'descripcion' => null,
                'es_final'    => false,
            ];
        }

        $estado = TransoftEstado::find($pedido->transoft_estado_codigo);

        return [
            'codigo'      => $pedido->transoft_estado_codigo,
            'descripcion' => $estado?->descripcion ?? $pedido->transoft_estado_codigo,
            'es_final'    => $estado?->es_final ?? false,
        ];
    }
}
