<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_envios — datos de origen/destino del envío (1:1 con cotizaciones).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_envios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id')->unique();
            // FK se omite: cotizaciones es particionada y MySQL no soporta FKs en ellas
            // Origen
            $table->foreignId('provincia_origen_id')->constrained('provincias')->restrictOnDelete();
            $table->foreignId('localidad_origen_id')->nullable()->constrained('localidades')->restrictOnDelete();
            // Destino
            $table->foreignId('provincia_destino_id')->constrained('provincias')->restrictOnDelete();
            $table->foreignId('localidad_destino_id')->nullable()->constrained('localidades')->restrictOnDelete();
            // Modalidades
            $table->boolean('solicita_retiro')->default(false);
            $table->boolean('solicita_entrega')->default(false);
            $table->boolean('retira_en_sucursal')->default(false);
            // Carga
            $table->decimal('valor_declarado', 12, 2)->nullable();
            $table->unsignedSmallInteger('dias_almacenamiento')->default(0);
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
