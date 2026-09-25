<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransoftEstadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('transoft_estados')->insert([
            [
                'codigo' => 'PC',
                'nombre' => 'Precarga',
            ],
            [
                'codigo' => 'TT',
                'nombre' => 'En tránsito',
            ],
            [
                'codigo' => 'ED',
                'nombre' => 'Entregado',
            ],
            [
                'codigo' => 'RL',
                'nombre' => 'Relevado',
            ],
        ]);

    }
}
