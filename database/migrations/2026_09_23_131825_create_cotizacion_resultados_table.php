<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_resultados — snapshot versionado del cálculo.
 * Cada recalculación crea una nueva versión (numero_version++).
 * Nunca se pisa el resultado anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_version')->default(1)->comment('Versión del cálculo; incrementa en cada recalculo');
            // Inputs del cálculo (snapshot)
            $table->decimal('peso_total_kg', 10, 3)->nullable();
            $table->decimal('volumen_total_m3', 10, 6)->nullable();
            $table->decimal('pallets_equivalentes', 8, 4)->nullable();
            // Costo base
            $table->decimal('subtotal_flete', 12, 2)->comment('Costo de transporte sin márgenes ni descuentos');
            // Margen aplicado
            $table->decimal('margen_porcentaje', 6, 2)->default(0);
            $table->decimal('margen_monto', 12, 2)->default(0);
            // Descuento (acuerdo comercial)
            $table->decimal('descuento_porcentaje', 6, 2)->default(0);
            $table->decimal('descuento_monto', 12, 2)->default(0);
            // Adicionales
            $table->decimal('subtotal_adicionales', 12, 2)->default(0);
            // Seguro
            $table->decimal('seguro_monto', 12, 2)->default(0);
            // Total
            $table->decimal('total_final', 12, 2);
            // Metadatos del cálculo
            $table->foreignId('margen_id')->nullable()->constrained('margenes_ganancia')->nullOnDelete();
            $table->foreignId('tarifa_id')->nullable()->constrained('tarifas')->nullOnDelete();
            $table->string('unidad_cobro', 20)->nullable()->comment('KG | M3 | PALLET | BULTO');
            $table->decimal('cantidad_cobrada', 10, 4)->nullable();
            $table->timestamp('calculado_at')->useCurrent();
            $table->timestamps();
            $table->index('cotizacion_id');
            $table->unique(['cotizacion_id', 'numero_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_resultados');
    }
};
