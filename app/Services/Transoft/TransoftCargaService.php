<?php

namespace App\Services\Transoft;

use App\Models\Pedido;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Servicio de Cargas Transoft.
 *
 * Gestiona la creación, consulta y actualización de cargas
 * en la API Transoft v3 (endpoints con username/operationId en la URL).
 *
 * Referencia documental: Transoftweb Integraciones v1.4.1
 *
 * Endpoints gestionados:
 *   POST /api/v3/cargas/add/{username}/{operationId}
 *   PUT  /api/cargas/edit/{tracking}/{username}/{operationId}
 *   GET  /api/cargas/v2/{username}/{operationId}/{tracking}
 *   GET  /api/cargas/{username}/{operationId}/{tracking}
 *   GET  /api/cargas/find/document/{username}/{operationId}/{documentNumber}
 *
 * Regla de arquitectura:
 *   - Este servicio NO debe ser usado desde CotizadorService.
 *   - Solo se invoca DESPUÉS de que la cotización se convierte en pedido.
 *   - Toda comunicación HTTP pasa por TransoftClient.
 */
class TransoftCargaService
{
    public function __construct(private readonly TransoftClient $client) {}

    // ---------------------------------------------------------------
    // Crear carga
    // ---------------------------------------------------------------

    /**
     * Registra una nueva carga en Transoft.
     *
     * POST /api/v3/cargas/add/{username}/{operationId}
     *
     * @param  array  $payload  Payload construido externamente (ver buildPayload())
     * @return array            Respuesta de Transoft con CodigoSeguimiento, etc.
     *
     * @throws RuntimeException Si la respuesta no es exitosa
     */
    public function crearCarga(array $payload): array
    {
        Log::info('[TransoftCargaService] Creando carga en Transoft', [
            'codigo_seguimiento' => $payload['CodigoSeguimiento'] ?? null,
        ]);

        $response = $this->client->crearCargaV3($payload);

        $this->validarRespuesta($response, 'crearCarga');

        return $response;
    }

    // ---------------------------------------------------------------
    // Consultar carga
    // ---------------------------------------------------------------

    /**
     * Consulta una carga por tracking (v2, datos extendidos).
     *
     * GET /api/cargas/v2/{username}/{operationId}/{tracking}
     *
     * @param  string $tracking
     * @return array
     *
     * @throws RuntimeException Si la respuesta no es exitosa
     */
    public function consultarCargaV2(string $tracking): array
    {
        Log::debug('[TransoftCargaService] Consultando carga v2', ['tracking' => $tracking]);

        $response = $this->client->getCargaV2($tracking);

        $this->validarRespuesta($response, 'consultarCargaV2');

        return $response;
    }

    /**
     * Consulta una carga por tracking (v1, datos básicos).
     *
     * GET /api/cargas/{username}/{operationId}/{tracking}
     *
     * @param  string $tracking
     * @return array
     *
     * @throws RuntimeException Si la respuesta no es exitosa
     */
    public function consultarCarga(string $tracking): array
    {
        Log::debug('[TransoftCargaService] Consultando carga', ['tracking' => $tracking]);

        $response = $this->client->getCargaLegacy($tracking);

        $this->validarRespuesta($response, 'consultarCarga');

        return $response;
    }

    /**
     * Busca una carga por número de documento.
     *
     * GET /api/cargas/find/document/{username}/{operationId}/{documentNumber}
     *
     * @param  string $documentNumber  Nro. de documento (remito, guía, etc.)
     * @return array
     *
     * @throws RuntimeException Si la respuesta no es exitosa
     */
    public function buscarPorDocumento(string $documentNumber): array
    {
        Log::debug('[TransoftCargaService] Buscando carga por documento', [
            'document_number' => $documentNumber,
        ]);

        $response = $this->client->buscarCargaPorDocumentoLegacy($documentNumber);

        $this->validarRespuesta($response, 'buscarPorDocumento');

        return $response;
    }

    // ---------------------------------------------------------------
    // Actualizar carga
    // ---------------------------------------------------------------

    /**
     * Actualiza una carga existente en Transoft.
     *
     * PUT /api/cargas/edit/{tracking}/{username}/{operationId}
     *
     * @param  string $tracking
     * @param  array  $payload
     * @return array
     *
     * @throws RuntimeException Si la respuesta no es exitosa
     */
    public function actualizarCarga(string $tracking, array $payload): array
    {
        Log::info('[TransoftCargaService] Actualizando carga', ['tracking' => $tracking]);

        $response = $this->client->actualizarCargaLegacy($tracking, $payload);

        $this->validarRespuesta($response, 'actualizarCarga');

        return $response;
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Valida que la respuesta de Transoft no contenga un error conocido.
     *
     * La API Transoft puede retornar HTTP 200 con StatusCode != 200 en el body.
     *
     * @throws RuntimeException
     */
    private function validarRespuesta(array $response, string $operacion): void
    {
        // Transoft puede devolver un campo StatusCode con el error real
        $statusCode = $response['StatusCode'] ?? null;
        $message    = $response['Message'] ?? null;

        if ($statusCode !== null && $statusCode !== 200 && $statusCode !== 201) {
            $msg = "[TransoftCargaService] {$operacion} retornó error. StatusCode={$statusCode}, Message={$message}";
            Log::error($msg, ['response' => $response]);
            throw new RuntimeException($msg);
        }
    }
}
