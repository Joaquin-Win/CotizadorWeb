<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pedidos — tabla de pedidos.
 * seguimientos — bitácora de estados del pedido.
 * Per dump: NO cotizacion_id. Tiene numero_unico GENERATED STORED, importacion_id.
 */
return new class extends Migration {
    public function up(): void
    {
        // -------------------------------------------------------
        // pedidos
        // -------------------------------------------------------
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->unsignedBigInteger('importacion_id')->nullable();
            $table->foreignId('localidad_destino_id')->constrained('localidades')->restrictOnDelete();
            $table->unsignedSmallInteger('estado_id');
            $table->foreign('estado_id')->references('id')->on('estados_pedido')->restrictOnDelete();
            $table->string('numero_pedido', 50);
            $table->date('fecha');
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            // Transoft fields
            $table->string('transoft_tracking', 50)->nullable()->unique();
            $table->string('transoft_operation_id', 50)->nullable();
            $table->string('transoft_estado_codigo', 4)->nullable();
            $table->string('transoft_etiqueta_url', 255)->nullable();
            $table->string('transoft_seguimiento_url', 255)->nullable();
            $table->timestamp('transoft_sync_at')->nullable();
            $table->json('transoft_payload_json')->nullable();
            $table->index('cliente_id');
            $table->index('estado_id');
            $table->index('fecha');
            $table->index('transoft_estado_codigo');
        });

        // FK a transoft_estados
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreign('transoft_estado_codigo')
                ->references('codigo')
                ->on('transoft_estados')
                ->nullOnDelete();
        });

        // -------------------------------------------------------
        // seguimientos — bitácora de estados del pedido
        // -------------------------------------------------------
        Schema::create('seguimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->unsignedSmallInteger('estado_id');
            $table->foreign('estado_id')->references('id')->on('estados_pedido')->restrictOnDelete();
            $table->foreignId('localidad_destino_id')->nullable()->constrained('localidades')->restrictOnDelete();
            $table->string('numero_seguimiento', 50)->nullable();
            $table->dateTime('fecha_actualizacion');
            $table->string('observacion', 255)->nullable();
            $table->timestamps();
            $table->index('pedido_id');
            $table->index('numero_seguimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimientos');
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['transoft_estado_codigo']);
        });
        Schema::dropIfExists('pedidos');
    }
};