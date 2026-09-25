<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProvinciasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('provincias')->insert([
            [
                'nombre' => 'Ciudad Autónoma de Buenos Aires',
                'codigo_georef' => '02',
                'tiene_deposito' => true,
            ],
            [
                'nombre' => 'Buenos Aires',
                'codigo_georef' => '06',
                'tiene_deposito' => true,
            ],
            [
                'nombre' => 'Catamarca',
                'codigo_georef' => '10',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Córdoba',
                'codigo_georef' => '14',
                'tiene_deposito' => true,
            ],
            [
                'nombre' => 'Corrientes',
                'codigo_georef' => '18',
                'tiene_deposito' => true,
            ],
            [
                'nombre' => 'Chaco',
                'codigo_georef' => '22',
                'tiene_deposito' => true,
            ],
            [
                'nombre' => 'Chubut',
                'codigo_georef' => '26',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Entre Ríos',
                'codigo_georef' => '30',
                'tiene_deposito' => true,
            ],
            [
                'nombre' => 'Formosa',
                'codigo_georef' => '34',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Jujuy',
                'codigo_georef' => '38',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'La Pampa',
                'codigo_georef' => '42',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'La Rioja',
                'codigo_georef' => '46',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Mendoza',
                'codigo_georef' => '50',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Misiones',
                'codigo_georef' => '54',
                'tiene_deposito' => true,
            ],
            [
                'nombre' => 'Neuquén',
                'codigo_georef' => '58',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Río Negro',
                'codigo_georef' => '62',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Salta',
                'codigo_georef' => '66',
                'tiene_deposito' => true,
            ],
            [
                'nombre' => 'San Juan',
                'codigo_georef' => '70',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'San Luis',
                'codigo_georef' => '74',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Santa Cruz',
                'codigo_georef' => '78',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Santa Fe',
                'codigo_georef' => '82',
                'tiene_deposito' => true,
            ],
            [
                'nombre' => 'Santiago del Estero',
                'codigo_georef' => '86',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Tierra del Fuego, Antártida e Islas del Atlántico Sur',
                'codigo_georef' => '94',
                'tiene_deposito' => false,
            ],
            [
                'nombre' => 'Tucumán',
                'codigo_georef' => '90',
                'tiene_deposito' => true,
            ],
        ]);
    }
}
