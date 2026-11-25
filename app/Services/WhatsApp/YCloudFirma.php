<?php

namespace App\Services\WhatsApp;

/**
 * Valida el header YCloud-Signature ("t=TIMESTAMP,s=HEX"): HMAC-SHA256 de "{timestamp}.{cuerpo crudo}"
 * con el secreto del endpoint (whsec_...).
 */
class YCloudFirma
{
    private const TOLERANCIA_SEGUNDOS = 300;

    public function valida(string $cuerpo, ?string $header): bool
    {
        $secreto = (string) config('whatsapp.bot.ycloud_webhook_secret');
        parse_str(str_replace(',', '&', (string) $header), $partes);
        $timestamp = $partes['t'] ?? null;
        $firma = $partes['s'] ?? null;

        if ($secreto === '' || ! is_string($timestamp) || ! ctype_digit($timestamp) || ! is_string($firma)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::TOLERANCIA_SEGUNDOS) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp.'.'.$cuerpo, $secreto), $firma);
    }
}
