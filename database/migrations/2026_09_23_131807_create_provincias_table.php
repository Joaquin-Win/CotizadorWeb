<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** provincias + seed de las 6 del SQL */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provincias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo_georef', 10)->unique()->comment('Código GeoRef INDEC');
            $table->boolean('tiene_deposito')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index('nombre');
        });

        DB::table('provincias')->insert([
            ['nombre' => 'Misiones',                       'codigo_georef' => '54', 'tiene_deposito' => true],
            ['nombre' => 'Corrientes',                     'codigo_georef' => '18', 'tiene_deposito' => false],
            ['nombre' => 'Chaco',                          'codigo_georef' => '22', 'tiene_deposito' => false],
            ['nombre' => 'Salta',                          'codigo_georef' => '66', 'tiene_deposito' => false],
            ['nombre' => 'Buenos Aires',                   'codigo_georef' => '06', 'tiene_deposito' => true],
            ['nombre' => 'Ciudad Autónoma de Buenos Aires','codigo_georef' => '02', 'tiene_deposito' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('provincias');
    }
};
