<?php

namespace App\Services\Transoft;

use App\Models\Cotizacion;

class TransoftPrecargaService
{
    public function __construct(private TransoftClient $client) {}

    public function enviar(Cotizacion $cotizacion): array
    {
        $payload = $this->construirPayload($cotizacion);
        $r = $this->client->addPrecarga($payload);

        return match ($r->status()) {
            201     => $this->procesarAlta($cotizacion, $r->json()),
            409     => throw new \RuntimeException('La precarga ya existía en Transoft.'),
            406     => throw new \RuntimeException('Dador o transportista inválido.'),
            401     => throw new \RuntimeException('Credenciales Transoft inválidas.'),
            403     => throw new \RuntimeException('Sin permiso para esta operación.'),
            default => throw new \RuntimeException("Respuesta inesperada: {$r->status()} - {$r->body()}"),
        };
    }

    private function construirPayload(Cotizacion $c): array
    {
        // TODO: ajustar cada propiedad cuando el modelo Cotizacion esté definido.
        return [
            // ── Identificación ───────────────────────────────────────────────
            'CodigoSeguimiento'       => $c->codigo,                           // TODO: campo del modelo
            'CuitTransportista'       => ['Numero' => config('services.transoft.transportista_cuit')],
            'RazonSocialDadorDeCarga' => config('services.transoft.dador_razon_social'),
            'CuitDadorDeCarga'        => ['Numero' => config('services.transoft.dador_cuit')],
            'RazonSocialRemitente'    => config('services.transoft.dador_razon_social'),

            // ── Origen ───────────────────────────────────────────────────────
            'PaisOrigen'          => 'ARG',
            'CodigoPostalOrigen'  => config('services.transoft.origen_cp'),    // TODO: o del modelo
            'CalleOrigen'         => config('services.transoft.origen_calle'),
            'NumeroOrigen'        => config('services.transoft.origen_numero'),
            'PisoOrigen'          => '',
            'DepartamentoOrigen'  => '',
            'ObservacionOrigen'   => '',

            // ── Destino ──────────────────────────────────────────────────────
            // TODO: mapear desde la relación de envío/destino de la cotización
            'RazonSocialDestinatario' => $c->cliente?->razon_social ?? 'Consumidor Final',
            'PaisDestino'             => 'ARG',
            'CodigoPostalDestino'     => $c->envio?->localidad_destino?->codigo_postal,
            'CalleDestino'            => $c->envio?->calle_destino,
            'NumeroDestino'           => $c->envio?->numero_destino,
            'PisoDestino'             => $c->envio?->piso_destino ?? '',
            'DepartamentoDestino'     => $c->envio?->departamento_destino ?? '',
            'ObservacionDestino'      => $c->envio?->observacion_destino ?? '',

            // ── Contacto ─────────────────────────────────────────────────────
            'ContactoNombre'    => $c->cliente?->nombre_contacto,
            'ContactoDocumento' => $c->cliente?->nro_documento,
            'ContactoTelefono'  => $c->cliente?->telefono,
            'ContactoEmail'     => $c->cliente?->email,

            'Observacion' => $c->observaciones ?? '',

            // ── Detalles de bultos (formato v3) ──────────────────────────────
            // TODO: mapear desde la colección de bultos de la cotización
            'Detalles' => $c->bultos->map(fn ($b) => [
                'Bultos'         => (int)   $b->cantidad,
                'Kilos'          => (float) $b->peso_kg,
                'M3'             => (float) $b->volumen_m3,    // TODO: calcular o usar campo
                'ValorDeclarado' => (float) $b->valor_declarado,
                'Descripcion'    => $b->descripcion ?? 'Mercadería',
            ])->all(),

            // ── Documentos asociados (remitos) ────────────────────────────────
            // TODO: mapear desde la colección de remitos / documentos de la cotización
            'DocumentosAsociados' => $c->documentos?->map(fn ($d) => [
                'Letra'  => $d->letra,
                'Centro' => $d->centro,
                'Numero' => $d->numero,
            ])->all() ?? [],
        ];
    }

    private function procesarAlta(Cotizacion $c, array $resp): array
    {
        // La respuesta v3 tiene forma: { StatusCode, Message, Results: [{ CodigoSeguimiento, URLTracking, URLEtiqueta }] }
        $result = $resp['Results'][0] ?? [];

        $c->update([
            'transoft_codigo_seguimiento' => $result['CodigoSeguimiento'] ?? null,
            'transoft_url_tracking'       => $result['URLTracking']       ?? null,
            'transoft_url_etiqueta'       => $result['URLEtiqueta']       ?? null,
            'transoft_sync_at'            => now(),
            'transoft_payload_json'       => $resp,
        ]);

        return $result;
    }
}
