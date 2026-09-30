<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * auditoria — log inmutable de cambios en registros críticos.
 * Per dump: NO FK a usuarios. usuario_bd NOT NULL. datetime(6). CHECK accion.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 64)->comment('Nombre de la tabla auditada');
            $table->unsignedBigInteger('registro_id')->comment('PK del registro afectado');
            $table->string('accion', 10)->comment('INSERT | UPDATE | DELETE');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nombre', 255)->nullable()->comment('Snapshot del nombre');
            $table->string('usuario_bd', 100)->comment('Usuario de BD que ejecutó la query');
            $table->string('ip', 45)->nullable();
            $table->string('contexto', 255)->nullable()->comment('Ruta/URL o nombre del proceso');
            $table->json('campos_modificados')->nullable()->comment('Array de nombres de campos cambiados');
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // CHECK constraint inline para SQLite
            $table->check("accion IN ('INSERT','UPDATE','DELETE')", 'chk_auditoria_accion');

            // Índices
            $table->index(['tabla', 'registro_id'], 'auditoria_tabla_registro_index');
            $table->index('usuario_id', 'auditoria_usuario_id_index');
            $table->index('created_at', 'auditoria_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};