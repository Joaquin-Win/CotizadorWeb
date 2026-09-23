<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_bultos — líneas de bultos de una cotización.
 * volumen_m3 y peso_total_kg son columnas GENERADAS inline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_bultos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('tipo_bulto_id');
            $table->foreign('tipo_bulto_id')->references('id')->on('tipos_bulto')->restrictOnDelete();
            $table->unsignedSmallInteger('cantidad')->default(1);
            // Dimensiones
            $table->decimal('largo_cm', 8, 2);
            $table->decimal('ancho_cm', 8, 2);
            $table->decimal('alto_cm', 8, 2);
            $table->decimal('peso_kg', 8, 3);
            $table->boolean('palletizado')->default(false);
            // Columnas GENERADAS inline
            $table->decimal('volumen_m3', 10, 6)
                  ->storedAs('(largo_cm * ancho_cm * alto_cm) / 1000000');
            $table->decimal('peso_total_kg', 10, 3)
                  ->storedAs('peso_kg * cantidad');
            // Calculados por la app (no generados)
            $table->decimal('pallets_equivalentes', 8, 4)->nullable()->comment('Calculado: volumen / 1.1 m3 por pallet');
            $table->decimal('costo_individual', 12, 2)->nullable()->comment('Snapshot del costo por este bulto');
            $table->timestamps();
            $table->index('cotizacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_bultos');
    }
};
