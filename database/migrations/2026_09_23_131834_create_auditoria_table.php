<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * auditoria — log inmutable de cambios en registros críticos.
 * Per dump: NO FK a usuarios. usuario_bd NOT NULL. datetime(6). CHECK accion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 64)->comment('Nombre de la tabla auditada');
            $table->unsignedBigInteger('registro_id')->comment('PK del registro afectado');
            $table->string('accion', 10)->comment('INSERT | UPDATE | DELETE');
            // Sin FK: puede registrar acciones de usuarios ya eliminados o procesos automatizados
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nombre', 255)->nullable()->comment('Snapshot del nombre');
            $table->string('usuario_bd', 100)->comment('Usuario de BD que ejecutó la query');
            $table->string('ip', 45)->nullable();
            $table->string('contexto', 255)->nullable()->comment('Ruta/URL o nombre del proceso');
            $table->json('campos_modificados')->nullable()->comment('Array de nombres de campos cambiados');
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
        });

        // datetime(6) precision for created_at + CHECK accion via raw
        DB::statement("
            ALTER TABLE `auditoria`
            ADD COLUMN `created_at` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
            ADD CONSTRAINT `chk_auditoria_accion` CHECK (`accion` IN ('INSERT','UPDATE','DELETE')),
            ADD INDEX `auditoria_tabla_registro_index` (`tabla`, `registro_id`),
            ADD INDEX `auditoria_usuario_id_index` (`usuario_id`),
            ADD INDEX `auditoria_created_at_index` (`created_at`)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
