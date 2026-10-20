<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinciasSeeder extends Seeder
{
    public function run(): void
    {
        $provincias = [
            ['nombre' => 'Ciudad Autónoma de Buenos Aires',                          'codigo_georef' => '02'],
            ['nombre' => 'Buenos Aires',                                              'codigo_georef' => '06'],
            ['nombre' => 'Catamarca',                                                 'codigo_georef' => '10'],
            ['nombre' => 'Córdoba',                                                   'codigo_georef' => '14'],
            ['nombre' => 'Corrientes',                                                'codigo_georef' => '18'],
            ['nombre' => 'Chaco',                                                     'codigo_georef' => '22'],
            ['nombre' => 'Chubut',                                                    'codigo_georef' => '26'],
            ['nombre' => 'Entre Ríos',                                                'codigo_georef' => '30'],
            ['nombre' => 'Formosa',                                                   'codigo_georef' => '34'],
            ['nombre' => 'Jujuy',                                                     'codigo_georef' => '38'],
            ['nombre' => 'La Pampa',                                                  'codigo_georef' => '42'],
            ['nombre' => 'La Rioja',                                                  'codigo_georef' => '46'],
            ['nombre' => 'Mendoza',                                                   'codigo_georef' => '50'],
            ['nombre' => 'Misiones',                                                  'codigo_georef' => '54'],
            ['nombre' => 'Neuquén',                                                   'codigo_georef' => '58'],
            ['nombre' => 'Río Negro',                                                 'codigo_georef' => '62'],
            ['nombre' => 'Salta',                                                     'codigo_georef' => '66'],
            ['nombre' => 'San Juan',                                                  'codigo_georef' => '70'],
            ['nombre' => 'San Luis',                                                  'codigo_georef' => '74'],
            ['nombre' => 'Santa Cruz',                                                'codigo_georef' => '78'],
            ['nombre' => 'Santa Fe',                                                  'codigo_georef' => '82'],
            ['nombre' => 'Santiago del Estero',                                       'codigo_georef' => '86'],
            ['nombre' => 'Tucumán',                                                   'codigo_georef' => '90'],
            ['nombre' => 'Tierra del Fuego, Antártida e Islas del Atlántico Sur',    'codigo_georef' => '94'],
        ];

        $now = now();

        foreach ($provincias as $provincia) {
            DB::table('provincias')->updateOrInsert(
                ['codigo_georef' => $provincia['codigo_georef']],
                [
                    'nombre'         => $provincia['nombre'],
                    'activo'         => true,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]
            );
        }
    }
}
