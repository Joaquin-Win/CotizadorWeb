<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de estados: estados_cotizacion, estados_pedido,
 *                    estados_cliente, estados_integracion, estados_importacion.
 * Datos sembrados por CatalogoSeeder (no en migración).
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // estados_cotizacion
        // -------------------------------------------------------
        Schema::create('estados_cotizacion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->unsignedSmallInteger('orden');
            $table->boolean('es_final')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique('orden');
        });

        // -------------------------------------------------------
        // estados_pedido
        // -------------------------------------------------------
        Schema::create('estados_pedido', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->unsignedSmallInteger('orden');
            $table->boolean('es_final')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique('orden');
        });

        // -------------------------------------------------------
        // estados_cliente
        // -------------------------------------------------------
        Schema::create('estados_cliente', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('permite_operar')->default(true)->comment('1 = puede cotizar y hacer pedidos');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // estados_integracion
        // -------------------------------------------------------
        Schema::create('estados_integracion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('es_operativo')->default(false)->comment('1 = la integración puede sincronizar');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // estados_importacion
        // -------------------------------------------------------
        Schema::create('estados_importacion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_importacion');
        Schema::dropIfExists('estados_integracion');
        Schema::dropIfExists('estados_cliente');
        Schema::dropIfExists('estados_pedido');
        Schema::dropIfExists('estados_cotizacion');
    }
};
