<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** transoft_webhook_eventos */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transoft_webhook_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('tracking', 50)->nullable();
            $table->string('estado_codigo', 4)->nullable();
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->string('firma_recibida', 255)->nullable();
            $table->boolean('firma_valida')->default(false);
            $table->boolean('procesado')->default(false);
            $table->text('error')->nullable();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('procesado_at')->nullable();
            $table->index('tracking');
            $table->index('procesado');
            $table->index('pedido_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transoft_webhook_eventos');
    }
};
