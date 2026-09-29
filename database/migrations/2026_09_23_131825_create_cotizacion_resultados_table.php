<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_resultados — snapshot versionado del cálculo per dump.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_resultados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            // FK omitida: cotizaciones es particionada
            $table->unsignedSmallInteger('numero_version')->default(1);
            $table->foreignId('margen_id')->nullable()->constrained('margenes_ganancia')->nullOnDelete();
            $table->decimal('margen_porcentaje', 6, 2);
            $table->decimal('costo_troncal', 12, 2)->default(0);
            $table->decimal('costo_primera_milla', 12, 2)->default(0);
            $table->decimal('costo_ultima_milla', 12, 2)->default(0);
            $table->decimal('costo_puerta_puerta', 12, 2)->default(0);
            $table->decimal('subtotal_flete', 12, 2)->default(0);
            $table->decimal('costo_seguro', 12, 2)->default(0);
            $table->decimal('costo_carga_descarga', 12, 2)->default(0);
            $table->boolean('incluye_carga_descarga')->default(false);
            $table->decimal('margen_ganancia', 12, 2)->default(0);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('descuento_monto', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total_final', 12, 2)->default(0);
            $table->smallInteger('tiempo_estimado_min')->unsigned()->nullable();
            $table->smallInteger('tiempo_estimado_max')->unsigned()->nullable();
            $table->string('version_algoritmo', 20)->default('v1');
            $table->timestamp('calculado_at')->useCurrent();
            $table->timestamps();
            $table->unique(['cotizacion_id', 'numero_version'], 'uq_resultado_version');
            $table->index('cotizacion_id');
        });

        // CHECK constraints from dump
        DB::statement("ALTER TABLE `cotizacion_resultados`
            ADD CONSTRAINT `chk_resultado_margen` CHECK (`margen_porcentaje` >= 0),
            ADD CONSTRAINT `chk_resultado_descuento` CHECK (`descuento_porcentaje` >= 0 AND `descuento_porcentaje` <= 100),
            ADD CONSTRAINT `chk_resultado_total` CHECK (`total_final` >= 0),
            ADD CONSTRAINT `chk_resultado_iva` CHECK (`iva` >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_resultados');
    }
};
