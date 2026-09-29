<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ZonasSeeder extends Seeder
{
    public function run(): void
    {
        $zonas = [
            [
                'codigo' => 'NORTE',
                'nombre' => 'Zona Norte',
                'descripcion' => 'Provincias del norte del país.',
            ],
            [
                'codigo' => 'CENTRO',
                'nombre' => 'Zona Centro',
                'descripcion' => 'Región centro.',
            ],
            [
                'codigo' => 'CUYO',
                'nombre' => 'Zona Cuyo',
                'descripcion' => 'Región de Cuyo.',
            ],
            [
                'codigo' => 'SUR',
                'nombre' => 'Zona Sur',
                'descripcion' => 'Patagonia.',
            ],
            [
                'codigo' => 'AMBA',
                'nombre' => 'AMBA',
                'descripcion' => 'CABA y conurbano bonaerense.',
            ],
            [
                'codigo' => 'NEA',
                'nombre' => 'NEA',
                'descripcion' => 'Nordeste argentino.',
            ],
        ];

        foreach ($zonas as $zona) {
            DB::table('zonas')->updateOrInsert(
                ['codigo' => $zona['codigo']],
                [
                    'nombre' => $zona['nombre'],
                    'descripcion' => $zona['descripcion'],
                    'activo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        /*
         * Las relaciones se resuelven mediante codigo_georef,
         * nunca suponiendo que el ID interno de la provincia
         * coincide con su código GeoRef.
         *
         * Relación que existía en la migración anterior:
         * NORTE = Misiones, Corrientes, Chaco y Salta.
         */
        $codigosProvinciaNorte = [
            '54', // Misiones
            '18', // Corrientes
            '22', // Chaco
            '66', // Salta
        ];

        $zonaNorteId = DB::table('zonas')
            ->where('codigo', 'NORTE')
            ->value('id');

        if (!$zonaNorteId) {
            throw new RuntimeException(
                'No se encontró la zona NORTE.'
            );
        }

        $provincias = DB::table('provincias')
            ->whereIn('codigo_georef', $codigosProvinciaNorte)
            ->pluck('id', 'codigo_georef');

        foreach ($codigosProvinciaNorte as $codigoProvincia) {
            if (!isset($provincias[$codigoProvincia])) {
                throw new RuntimeException(
                    "No se encontró la provincia con código GeoRef {$codigoProvincia}."
                );
            }

            DB::table('zona_provincias')->insertOrIgnore([
                'zona_id' => $zonaNorteId,
                'provincia_id' => $provincias[$codigoProvincia],
                'created_at' => now(),
            ]);
        }

        $this->command->info(
            'Zonas creadas correctamente: ' . count($zonas)
        );

        $this->command->info(
            'Provincias asociadas a NORTE: ' . count($codigosProvinciaNorte)
        );
    }
}