<?php

namespace App\Services\Transoft;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Cliente HTTP para la API Transoft v4.
 *
 * Autenticación: Bearer token con expiración.
 * El token se cachea en la tabla `cache` de Laravel (driver=database) o Redis.
 * Las credenciales y URL base son configurables via la tabla `transoft_configuracion`.
 *
 * Regla de arquitectura: este servicio NO debe ser inyectado en CotizadorService.
 * Solo se usa para pedidos, tracking y seguimiento (post-cotización).
 */
class TransoftClient
{
    private const CACHE_KEY   = 'transoft_bearer_token';
    private const CACHE_TTL   = 3300; // 55 min (tokens duran ~60 min, margen de seguridad)
    private const API_VERSION = 'v4';

    private string $baseUrl;
    private string $username;
    private string $operationId;

    public function __construct()
    {
        $this->baseUrl     = $this->config('base_url', config('services.transoft.base_url', ''));
        $this->username    = $this->config('username',  config('services.transoft.username',  ''));
        $this->operationId = $this->config('operation_id', config('services.transoft.operation_id', ''));
    }

    // ---------------------------------------------------------------
    // Cargas v4
    // ---------------------------------------------------------------

    /**
     * GET /api/v4/cargas/{tracking}
     */
    public function getCarga(string $tracking): array
    {
        return $this->request()->get("api/v4/cargas/{$tracking}")->json();
    }

    /**
     * POST /api/v4/cargas
     */
    public function crearCarga(array $payload): array
    {
        return $this->request()->post('api/v4/cargas', $payload)->json();
    }

    /**
     * PUT /api/v4/cargas/{tracking}
     */
    public function actualizarCarga(string $tracking, array $payload): array
    {
        return $this->request()->put("api/v4/cargas/{$tracking}", $payload)->json();
    }

    /**
     * GET /api/v4/cargas
     */
    public function listarCargas(array $params = []): array
    {
        return $this->request()->get('api/v4/cargas', $params)->json();
    }

    /**
     * GET /api/v4/cargas/document/{documentNumber}
     */
    public function buscarCargaPorDocumento(string $documentNumber): array
    {
        return $this->request()->get("api/v4/cargas/document/{$documentNumber}")->json();
    }

    // ---------------------------------------------------------------
    // Estados v4
    // ---------------------------------------------------------------

    /**
     * GET /api/v4/cargas/{tracking}/states
     */
    public function obtenerEstados(string $tracking): array
    {
        return $this->request()->get("api/v4/cargas/{$tracking}/states")->json();
    }

    /**
     * POST /api/v4/cargas/{tracking}/states
     */
    public function registrarEstado(string $tracking, array $payload): array
    {
        return $this->request()->post("api/v4/cargas/{$tracking}/states", $payload)->json();
    }

    // ---------------------------------------------------------------
    // Evidencia v4
    // ---------------------------------------------------------------

    /** GET /api/v4/cargas/{tracking}/images */
    public function obtenerImagenes(string $tracking): array
    {
        return $this->request()->get("api/v4/cargas/{$tracking}/images")->json();
    }

    /** POST /api/v4/cargas/{tracking}/images */
    public function subirImagen(string $tracking, array $payload): array
    {
        return $this->request()->post("api/v4/cargas/{$tracking}/images", $payload)->json();
    }

    /** GET /api/v4/cargas/{tracking}/signature */
    public function obtenerFirma(string $tracking): array
    {
        return $this->request()->get("api/v4/cargas/{$tracking}/signature")->json();
    }

    // ---------------------------------------------------------------
    // Precargas (legacy, se mantiene para compatibilidad)
    // ---------------------------------------------------------------

    /** POST /api/precargas/v3/add/{username}/{operationId} */
    public function crearPrecargaLegacy(array $payload): array
    {
        return $this->httpLegacy()->post(
            "api/precargas/v3/add/{$this->username}/{$this->operationId}",
            $payload
        )->json();
    }

    // ---------------------------------------------------------------
    // Webhooks
    // ---------------------------------------------------------------

    /** POST /api/v4/transportistas/suscripcion-webhook */
    public function suscribirWebhook(array $payload): array
    {
        return $this->request()->post('api/v4/transportistas/suscripcion-webhook', $payload)->json();
    }

    // ---------------------------------------------------------------
    // Gestión del token Bearer
    // ---------------------------------------------------------------

    /**
     * Obtiene el token desde cache o lo renueva llamando a la API.
     * Se almacena en el driver de cache configurado (database/redis).
     */
    public function obtenerToken(): string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->renovarToken();
        });
    }

    /**
     * Fuerza la renovación del token (útil ante 401).
     */
    public function renovarToken(): string
    {
        $password = $this->config('password', config('services.transoft.password', ''));

        $response = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->post("api/v4/credenciales/{$this->username}", [
                'password' => $password,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Transoft: no se pudo obtener token. HTTP {$response->status()}: {$response->body()}"
            );
        }

        $token = $response->json('token') ?? $response->json('access_token');

        if (! $token) {
            throw new RuntimeException('Transoft: respuesta de autenticación sin token.');
        }

        // Guardar también la fecha de expiración si la API la devuelve
        $expiresIn = $response->json('expires_in');
        if ($expiresIn) {
            Cache::put(self::CACHE_KEY, $token, $expiresIn - 60);
        }

        return $token;
    }

    /**
     * Invalida el token cacheado (útil ante cambio de credenciales).
     */
    public function invalidarToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ---------------------------------------------------------------
    // Helpers privados
    // ---------------------------------------------------------------

    /**
     * HTTP client v4 autenticado con Bearer.
     * Reintenta automáticamente si recibe 401 (renueva token una vez).
     */
    private function request(): PendingRequest
    {
        try {
            $token = $this->obtenerToken();
        } catch (\Throwable $e) {
            Log::error('Transoft: error al obtener token', ['error' => $e->getMessage()]);
            throw $e;
        }

        return Http::baseUrl($this->baseUrl)
            ->withToken($token)
            ->acceptJson()
            ->retry(2, 500, function (\Exception $e, PendingRequest $request) {
                // Si el servidor responde 401, renovar token y reintentar
                if ($e instanceof \Illuminate\Http\Client\RequestException && $e->response?->status() === 401) {
                    $this->invalidarToken();
                    $newToken = $this->renovarToken();
                    $request->withToken($newToken);
                    return true;
                }
                return false;
            });
    }

    /**
     * HTTP client para endpoints legacy (sin versión / con username+operationId en URL).
     */
    private function httpLegacy(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)->acceptJson();
    }

    /**
     * Lee un valor de configuración desde la tabla `transoft_configuracion`.
     * Si no existe en BD, usa el fallback.
     */
    private function config(string $clave, string $default = ''): string
    {
        try {
            $row = \App\Models\TransoftConfiguracion::where('clave', $clave)->first();
            return $row?->valor ?? $default;
        } catch (\Throwable) {
            // Si la tabla no existe aún (primera instalación), usar config
            return $default;
        }
    }
}