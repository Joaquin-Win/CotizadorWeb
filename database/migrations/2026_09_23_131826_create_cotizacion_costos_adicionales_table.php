<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_costos_adicionales — snapshot de cada costo adicional aplicado.
 * Schema from dump: id, cotizacion_id, costo_adicional_id NOT NULL FK, monto_aplicado, created_at.
 * UNIQUE(cotizacion_id, costo_adicional_id). CHECK monto >= 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_costos_adicionales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            // FK omitida: cotizaciones es particionada
            $table->foreignId('costo_adicional_id')->constrained('costos_adicionales')->restrictOnDelete();
            $table->decimal('monto_aplicado', 12, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['cotizacion_id', 'costo_adicional_id'], 'uq_cotcosto_cotizacion_costo');
            $table->index('cotizacion_id');
        });

        DB::statement("ALTER TABLE `cotizacion_costos_adicionales`
            ADD CONSTRAINT `chk_cotcosto_monto` CHECK (`monto_aplicado` >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_costos_adicionales');
    }
};
