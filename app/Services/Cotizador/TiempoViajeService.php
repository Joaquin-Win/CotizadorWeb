<?php

namespace App\Services\Cotizador;

use App\Models\Localidad;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tiempo estimado de viaje entre dos localidades.
 *
 *  - Con API Key de Google Maps (Configuración → Tiempo de viaje): Distance Matrix.
 *  - Sin API Key o si la API falla: aproximación Haversine × factor de ruta / velocidad media.
 */
class TiempoViajeService
{
    private const FACTOR_RUTA = 1.25;   // la ruta real es ~25% más larga que la línea recta
    private const VELOCIDAD_KMH = 70.0; // velocidad media de camión

    /** @return array{minutos:int, distancia_km:float, texto:string, fuente:string}|null */
    public function calcular(int $origenId, int $destinoId): ?array
    {
        $o = Localidad::find($origenId, ['id', 'lat', 'lon']);
        $d = Localidad::find($destinoId, ['id', 'lat', 'lon']);

        if (! $o || ! $d || $o->lat === null || $d->lat === null) {
            return null;
        }

        $key = 'tiempo_viaje:' . md5(config('services.google_maps.key') . "|{$origenId}|{$destinoId}");

        return Cache::remember($key, now()->addDay(), function () use ($o, $d) {
            $apiKey = config('services.google_maps.key');

            if ($apiKey) {
                $r = $this->consultarGoogle($o, $d, $apiKey);
                if ($r) {
                    return $r;
                }
            }

            return $this->haversine($o, $d);
        });
    }

    private function consultarGoogle(Localidad $o, Localidad $d, string $apiKey): ?array
    {
        try {
            $resp = Http::timeout(6)->get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins'      => "{$o->lat},{$o->lon}",
                'destinations' => "{$d->lat},{$d->lon}",
                'mode'         => 'driving',
                'language'     => 'es',
                'key'          => $apiKey,
            ]);

            $el = $resp->json('rows.0.elements.0');
            if (! $resp->ok() || ($el['status'] ?? '') !== 'OK') {
                Log::warning('[TiempoViaje] Google Maps sin resultado', ['status' => $resp->json('status'), 'error' => $resp->json('error_message')]);

                return null;
            }

            $min = (int) round($el['duration']['value'] / 60);

            return [
                'minutos'      => $min,
                'distancia_km' => round($el['distance']['value'] / 1000, 1),
                'texto'        => $this->formatear($min),
                'fuente'       => 'google_maps',
            ];
        } catch (\Throwable $e) {
            Log::warning('[TiempoViaje] Error consultando Google Maps: ' . $e->getMessage());

            return null;
        }
    }

    private function haversine(Localidad $o, Localidad $d): array
    {
        $r = 6371.0;
        $dLat = deg2rad($d->lat - $o->lat);
        $dLon = deg2rad($d->lon - $o->lon);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($o->lat)) * cos(deg2rad($d->lat)) * sin($dLon / 2) ** 2;
        $km = 2 * $r * asin(min(1, sqrt($a))) * self::FACTOR_RUTA;
        $min = (int) round($km / self::VELOCIDAD_KMH * 60);

        return [
            'minutos'      => $min,
            'distancia_km' => round($km, 1),
            'texto'        => $this->formatear($min),
            'fuente'       => 'aproximado',
        ];
    }

    private function formatear(int $min): string
    {
        $h = intdiv($min, 60);
        $m = $min % 60;

        return $h > 0 ? "{$h} h " . ($m > 0 ? "{$m} min" : '') : "{$m} min";
    }
}
