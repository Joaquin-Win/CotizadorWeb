<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** auditoria — log inmutable de cambios en registros críticos */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('usuario_nombre', 255)->nullable()->comment('Snapshot del nombre al momento del evento');
            $table->string('accion', 20)->comment('CREATE | UPDATE | DELETE | LOGIN | LOGOUT');
            $table->string('tabla', 100);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->json('campos_modificados')->nullable()->comment('Array de nombres de campos cambiados');
            $table->string('ip', 45)->nullable();
            $table->string('usuario_bd', 100)->nullable()->comment('Usuario de BD que ejecutó la query');
            $table->string('contexto', 255)->nullable()->comment('Ruta/URL o nombre del proceso');
            $table->timestamp('created_at')->useCurrent();
            $table->index('usuario_id');
            $table->index(['tabla', 'registro_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
