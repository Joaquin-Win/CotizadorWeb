<?php

namespace App\Services\Transoft;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class TransoftClient
{
    private string $baseUrl;
    private string $username;
    private string $operationId;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl     = rtrim(config('services.transoft.base_url'), '/');
        $this->username    = config('services.transoft.username');
        $this->operationId = config('services.transoft.operation_id');
        $this->timeout     = config('services.transoft.timeout', 15);
    }

    public function addPrecarga(array $payload): Response
    {
        return Http::timeout($this->timeout)
            ->acceptJson()
            ->post("{$this->baseUrl}/api/precargas/v3/add/{$this->username}/{$this->operationId}", $payload);
    }

    public function getPrecarga(string $tracking): Response
    {
        return Http::timeout($this->timeout)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/precargas/{$this->username}/{$this->operationId}/{$tracking}");
    }

    public function getCarga(string $tracking): Response
    {
        return Http::timeout($this->timeout)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/cargas/v2/{$this->username}/{$this->operationId}/{$tracking}");
    }

    public function getEstados(string $tracking): Response
    {
        return Http::timeout($this->timeout)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/cargas/{$tracking}/states/{$this->username}/{$this->operationId}");
    }
}