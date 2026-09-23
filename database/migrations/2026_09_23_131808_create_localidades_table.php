<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** localidades + zonas + pivots zona_provincias / zona_localidades */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // localidades
        // -------------------------------------------------------
        Schema::create('localidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincia_id')->constrained('provincias')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->string('codigo_postal', 20)->nullable();
            $table->string('codigo_georef', 20)->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index('provincia_id');
            $table->index('codigo_postal');
            $table->index('nombre');
        });

        DB::table('localidades')->insert([
            ['provincia_id' => 1, 'nombre' => 'Posadas',      'codigo_postal' => 'N3300', 'codigo_georef' => '540070'],
            ['provincia_id' => 1, 'nombre' => 'Campo Grande',  'codigo_postal' => 'N3332', 'codigo_georef' => '540112'],
            ['provincia_id' => 1, 'nombre' => 'Oberá',         'codigo_postal' => 'N3360', 'codigo_georef' => '540098'],
            ['provincia_id' => 2, 'nombre' => 'Corrientes',    'codigo_postal' => 'W3400', 'codigo_georef' => '180084'],
            ['provincia_id' => 5, 'nombre' => 'La Plata',      'codigo_postal' => 'B1900', 'codigo_georef' => '060441'],
            ['provincia_id' => 6, 'nombre' => 'CABA',          'codigo_postal' => 'C1000', 'codigo_georef' => '020010'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('localidades');
    }
};
