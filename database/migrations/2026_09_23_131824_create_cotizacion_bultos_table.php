<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_bultos — líneas de bultos de una cotización.
 * volumen_m3: GENERATED STORED = ((largo_cm/100)*(ancho_cm/100)*(alto_cm/100))*cantidad
 * peso_total_kg: GENERATED STORED = peso_kg * cantidad
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_bultos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            // FK omitida: cotizaciones es particionada
            $table->unsignedSmallInteger('tipo_bulto_id');
            $table->foreign('tipo_bulto_id')->references('id')->on('tipos_bulto')->restrictOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            // Dimensiones — decimal(10,2) per dump
            $table->decimal('largo_cm', 10, 2);
            $table->decimal('ancho_cm', 10, 2);
            $table->decimal('alto_cm', 10, 2);
            $table->decimal('peso_kg', 10, 2);
            // Columnas GENERADAS
            $table->decimal('volumen_m3', 12, 4)
                  ->storedAs('((largo_cm / 100) * (ancho_cm / 100) * (alto_cm / 100)) * cantidad');
            $table->decimal('peso_total_kg', 12, 2)
                  ->storedAs('peso_kg * cantidad');
            $table->timestamps();
            $table->index('cotizacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_bultos');
    }
};
