<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * tabla usuarios — tabla de autenticación del proyecto.
 * FK circular (cliente_id → clientes) se agrega al final de la migración de clientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('rol_id');
            $table->foreign('rol_id')->references('id')->on('roles')->restrictOnDelete();
            // FK a clientes se agrega en create_clientes_table (resuelve FK circular)
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->string('name');
            $table->string('email');
            // Columna GENERADA: unicidad de email entre cuentas vivas (NULL para eliminadas).
            // CASE WHEN en vez de IF para que corra igual en MySQL y en SQLite (tests).
            $table->string('email_unico', 255)
                  ->nullable()
                  ->storedAs('CASE WHEN deleted_at IS NULL THEN LOWER(email) END');
            $table->unique('email_unico', 'uq_usuarios_email_activo');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->comment('Hash bcrypt/argon2. Nunca texto plano.');
            $table->string('telefono', 50)->nullable();
            $table->boolean('activo')->default(true)->comment('Suspensión temporal sin borrar la cuenta.');
            $table->timestamp('ultimo_acceso')->nullable();
            $table->rememberToken();
            // Columnas del starter-kit (2FA la usan sus tests).
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('rol_id');
            $table->index('cliente_id');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
