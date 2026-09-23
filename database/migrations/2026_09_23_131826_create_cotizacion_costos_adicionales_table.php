<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_costos_adicionales — snapshot de cada costo adicional aplicado.
 * cotizacion_codigos — código público legible (COT-2026-000001).
 * cotizacion_seguimientos — historial de cambios de estado.
 * cotizacion_leads — datos de contacto del visitante anónimo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // cotizacion_costos_adicionales
        // -------------------------------------------------------
        Schema::create('cotizacion_costos_adicionales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('resultado_id')->constrained('cotizacion_resultados')->cascadeOnDelete();
            $table->foreignId('costo_adicional_id')->nullable()->constrained('costos_adicionales')->nullOnDelete();
            $table->string('nombre', 150)->comment('Snapshot del nombre al momento de calcular');
            $table->decimal('monto_aplicado', 12, 2)->comment('Monto ya calculado (si era % se convirtió a $)');
            $table->string('tipo', 5)->default('$')->comment('$ | %');
            $table->timestamp('created_at')->useCurrent();
            $table->index('cotizacion_id');
        });

        // -------------------------------------------------------
        // cotizacion_codigos
        // -------------------------------------------------------
        Schema::create('cotizacion_codigos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->unique()->constrained('cotizaciones')->cascadeOnDelete();
            $table->string('codigo', 30)->unique()->comment('COT-YYYY-NNNNNN');
            $table->timestamp('created_at')->useCurrent();
            $table->index('codigo');
        });

        // -------------------------------------------------------
        // cotizacion_seguimientos
        // -------------------------------------------------------
        Schema::create('cotizacion_seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('estado_id');
            $table->foreign('estado_id')->references('id')->on('estados_cotizacion')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->text('observacion')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('cotizacion_id');
        });

        // -------------------------------------------------------
        // cotizacion_leads
        // -------------------------------------------------------
        Schema::create('cotizacion_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->unique()->constrained('cotizaciones')->cascadeOnDelete();
            $table->string('nombre', 150)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('empresa', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_leads');
        Schema::dropIfExists('cotizacion_seguimientos');
        Schema::dropIfExists('cotizacion_codigos');
        Schema::dropIfExists('cotizacion_costos_adicionales');
    }
};
