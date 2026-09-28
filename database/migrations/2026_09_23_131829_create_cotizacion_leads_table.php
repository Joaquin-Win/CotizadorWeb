<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * cotizacion_leads — datos de contacto del cliente/visitante por cotización.
 * 1:1 con cotizaciones. Campos per dump.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id')->unique();
            // FK omitida: cotizaciones es particionada
            $table->string('nombre_cliente', 150)->nullable();
            $table->string('email_cliente', 255)->nullable();
            $table->string('telefono_cliente', 50)->nullable();
            $table->string('empresa', 255)->nullable();
            $table->timestamps();
            $table->index('email_cliente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_leads');
    }
};
