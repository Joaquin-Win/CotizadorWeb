<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_seguimientos — historial de cambios de estado de una cotización.
 * Nota: MySQL 8 prohíbe CHECK constraints sobre columnas con FK referential actions
 * (error 3823). La regla estado_anterior_id <> estado_nuevo_id se enforcea en la app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            // FK omitida: cotizaciones es particionada
            $table->unsignedSmallInteger('estado_anterior_id')->nullable();
            $table->foreign('estado_anterior_id')->references('id')->on('estados_cotizacion')->nullOnDelete();
            $table->unsignedSmallInteger('estado_nuevo_id');
            $table->foreign('estado_nuevo_id')->references('id')->on('estados_cotizacion')->restrictOnDelete();
            $table->string('motivo_no_cierre', 255)->nullable();
            $table->text('respuesta_cliente')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('cotizacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_seguimientos');
    }
};
