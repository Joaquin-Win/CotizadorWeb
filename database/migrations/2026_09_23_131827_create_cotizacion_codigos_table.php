<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_codigos — código público legible (COT-YYYY-NNNNNN).
 * PK: codigo varchar(30). cotizacion_id: indexed only, NOT unique, NO FK (partitioned table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_codigos', function (Blueprint $table) {
            $table->string('codigo', 30)->primary();
            $table->unsignedBigInteger('cotizacion_id');
            // No unique, no FK: cotizaciones es particionada
            $table->timestamp('created_at')->useCurrent();
            $table->index('cotizacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_codigos');
    }
};
