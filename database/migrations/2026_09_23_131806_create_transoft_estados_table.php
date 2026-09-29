<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * transoft_estados: mapeo de códigos Transoft (PN, PC, PR...) a estados internos.
 * Debe ir ANTES de pedidos (que FK a transoft_estados.codigo).
 * Datos sembrados por TransoftEstadosSeeder (no en migración).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transoft_estados', function (Blueprint $table) {
            $table->string('codigo', 4)->primary()->comment('PN, PC, PR, TT, RL, ED, SR, RP, RD, RC, RR, VO');
            $table->string('descripcion', 100)->comment('Descripción legible del estado');
            $table->unsignedSmallInteger('estado_pedido_id')->nullable();
            $table->unsignedSmallInteger('estado_cotizacion_id')->nullable();
            $table->foreign('estado_pedido_id')->references('id')->on('estados_pedido')->nullOnDelete();
            $table->foreign('estado_cotizacion_id')->references('id')->on('estados_cotizacion')->nullOnDelete();
            $table->boolean('es_final')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transoft_estados');
    }
};
