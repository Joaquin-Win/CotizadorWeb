<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizaciones — tabla central del negocio.
 *
 * Nota sobre PK: el SQL original usa PK compuesta (id, created_at) orientada
 * a particionado futuro. Eloquent no soporta PK compuestas bien, por lo que
 * se mantiene id como PK simple y created_at como columna estándar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('origen_id');
            $table->foreign('origen_id')->references('id')->on('origenes_cotizacion')->restrictOnDelete();
            $table->unsignedSmallInteger('tipo_cliente_id');
            $table->foreign('tipo_cliente_id')->references('id')->on('tipos_cliente')->restrictOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('acuerdo_id')->nullable()->constrained('acuerdos_comerciales')->nullOnDelete();
            $table->unsignedSmallInteger('estado_id');
            $table->foreign('estado_id')->references('id')->on('estados_cotizacion')->restrictOnDelete();
            // cotizacion_codigos se linkea aparte para mantener snapshot
            $table->timestamps();
            $table->softDeletes();
            $table->index('cliente_id');
            $table->index('usuario_id');
            $table->index('estado_id');
            $table->index('origen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
