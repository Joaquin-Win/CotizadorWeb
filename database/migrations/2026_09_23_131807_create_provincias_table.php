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

    }

    public function down(): void
    {
        Schema::dropIfExists('provincias');
    }
};
