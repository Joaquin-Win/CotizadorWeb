<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversaciones_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->string('telefono', 30)->unique();
            $table->string('estado', 10)->default('bot'); // bot | humano
            $table->timestamps();
        });

        Schema::create('mensajes_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_whatsapp_id')->constrained('conversaciones_whatsapp')->cascadeOnDelete();
            $table->string('direccion', 10); // entrante | saliente
            $table->text('texto');
            $table->string('externo_id')->nullable()->unique(); // id del proveedor, evita procesar dos veces
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_whatsapp');
        Schema::dropIfExists('conversaciones_whatsapp');
    }
};
