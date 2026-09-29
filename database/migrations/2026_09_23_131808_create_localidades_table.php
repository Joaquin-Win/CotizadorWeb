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

    }

    public function down(): void
    {
        Schema::dropIfExists('localidades');
    }
};
