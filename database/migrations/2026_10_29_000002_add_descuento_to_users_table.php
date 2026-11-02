<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Descuento comercial asignado por el admin a cada usuario/cliente.
 * (La tabla de autenticación de este proyecto es `usuarios`, no `users`.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->enum('tipo_descuento', ['porcentaje', 'fijo', 'ninguno'])->default('ninguno');
            $table->decimal('valor_descuento', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['tipo_descuento', 'valor_descuento']);
        });
    }
};
