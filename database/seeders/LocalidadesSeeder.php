<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocalidadesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/localidades.json');

        if (!file_exists($path)) {
            throw new RuntimeException("No se encontró el archivo GeoRef: {$path}");
        }

        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("No se pudo leer el archivo GeoRef: {$path}");
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Error al decodificar localidades.json: ' . json_last_error_msg());
        }

        if (!isset($data['localidades']) || !is_array($data['localidades'])) {
            throw new RuntimeException(
                'El archivo localidades.json no contiene una colección válida en la clave "localidades".'
            );
        }

        // Construir mapa codigo_georef (2 dígitos, zero-padded) → provincia_id
        $provincias = DB::table('provincias')
            ->pluck('id', 'codigo_georef')
            ->mapWithKeys(function ($id, $codigo) {
                return [str_pad((string) $codigo, 2, '0', STR_PAD_LEFT) => $id];
            })
            ->toArray();

        if (empty($provincias)) {
            throw new RuntimeException(
                'No existen provincias cargadas. Ejecutá ProvinciasSeeder antes de LocalidadesSeeder.'
            );
        }

        $localidades = [];
        $now = now();

        foreach ($data['localidades'] as $l) {
            if (!isset($l['id']) || !isset($l['nombre']) || !isset($l['provincia']['id'])) {
                continue;
            }

            $codigoProv = str_pad((string) $l['provincia']['id'], 2, '0', STR_PAD_LEFT);

            if (!isset($provincias[$codigoProv])) {
                throw new RuntimeException(
                    "No se encontró la provincia con código GeoRef '{$codigoProv}' " .
                    "para la localidad '{$l['nombre']}' (GeoRef ID: {$l['id']})."
                );
            }

            $localidades[] = [
                'provincia_id'  => $provincias[$codigoProv],
                'nombre'        => $l['nombre'],
                'codigo_postal' => null,
                'codigo_georef' => (string) $l['id'],
                'activo'        => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        // Idempotente: insertOrIgnore por UNIQUE(codigo_georef)
        foreach (array_chunk($localidades, 1000) as $chunk) {
            DB::table('localidades')->insertOrIgnore($chunk);
        }

        $this->command->info('Localidades insertadas/omitidas correctamente: ' . count($localidades));
    }
}
