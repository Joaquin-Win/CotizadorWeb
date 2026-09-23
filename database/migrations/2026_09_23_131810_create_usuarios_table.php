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
            // Columna GENERADA: unicidad de email entre cuentas vivas (NULL para eliminadas)
            $table->string('email_unico', 255)
                  ->nullable()
                  ->storedAs('IF(deleted_at IS NULL, LOWER(email), NULL)');
            $table->unique('email_unico', 'uq_usuarios_email_activo');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->comment('Hash bcrypt/argon2. Nunca texto plano.');
            $table->string('telefono', 50)->nullable();
            $table->boolean('activo')->default(true)->comment('Suspensión temporal sin borrar la cuenta.');
            $table->timestamp('ultimo_acceso')->nullable();
            $table->rememberToken();
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('rol_id');
            $table->index('cliente_id');
        });

        // Seed: admin inicial
        DB::table('usuarios')->insert([
            'rol_id'   => 1,
            'name'     => 'Administrador SET',
            'email'    => 'admin@setlogistica.com',
            'password' => bcrypt('password'),
            'activo'   => true,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
