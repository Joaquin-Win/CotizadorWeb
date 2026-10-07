<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cotizacion_costos_adicionales', function (Blueprint $table) {
            $table->id();

            /*
             * cotizaciones está particionada por created_at.
             *
             * No se crea FK hacia cotizaciones.
             * La integridad se controla desde la aplicación.
             */
            $table->unsignedBigInteger('cotizacion_id');

            $table->unsignedBigInteger('costo_adicional_id');

            $table->decimal('importe', 15, 2)
                ->default(0);

            $table->timestamps();

            $table->index('cotizacion_id');

            $table->foreign('costo_adicional_id')
                ->references('id')
                ->on('costos_adicionales')
                ->restrictOnDelete();

            $table->unique(
                ['cotizacion_id', 'costo_adicional_id'],
                'cotizacion_costo_adicional_unique'

            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizacion_costos_adicionales');
    }
};