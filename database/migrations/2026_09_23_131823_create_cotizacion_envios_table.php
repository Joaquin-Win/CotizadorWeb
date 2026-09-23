<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_envios — datos de origen/destino del envío (1:1 con cotizaciones).
 * Esta tabla está SEPARADA de cotizaciones en el diseño del SQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->unique()->constrained('cotizaciones')->cascadeOnDelete();
            // Origen
            $table->foreignId('provincia_origen_id')->constrained('provincias')->restrictOnDelete();
            $table->foreignId('localidad_origen_id')->nullable()->constrained('localidades')->restrictOnDelete();
            // Destino
            $table->foreignId('provincia_destino_id')->constrained('provincias')->restrictOnDelete();
            $table->foreignId('localidad_destino_id')->nullable()->constrained('localidades')->restrictOnDelete();
            // Modalidades
            $table->boolean('solicita_retiro')->default(false)->comment('Cliente pide que SET retire en su domicilio');
            $table->boolean('solicita_entrega')->default(false)->comment('Cliente pide entrega a domicilio en destino');
            $table->boolean('retira_en_sucursal')->default(false)->comment('Destinatario retira en sucursal SET');
            // Carga
            $table->decimal('valor_declarado', 14, 2)->nullable()->comment('Para cálculo de seguro');
            $table->unsignedSmallInteger('dias_almacenamiento')->default(0)->comment('Días en depósito (si aplica)');
            $table->timestamps();
            $table->index('cotizacion_id');
            $table->index('provincia_origen_id');
            $table->index('provincia_destino_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_envios');
    }
};
