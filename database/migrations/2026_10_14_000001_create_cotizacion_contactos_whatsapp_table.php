<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de contactos de seguimiento por WhatsApp (enlace wa.me generado).
 * Tabla nueva: no modifica datos existentes.
 * FK a cotizaciones omitida (la tabla es particionada, igual que cotizacion_seguimientos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_contactos_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('telefono', 20);
            $table->text('mensaje');
            $table->string('canal', 20)->default('wa_me');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['cotizacion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_contactos_whatsapp');
    }
};
