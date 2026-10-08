<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** cliente_contactos + cliente_integraciones */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // cliente_contactos
        // -------------------------------------------------------
        Schema::create('cliente_contactos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->string('cargo', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index('cliente_id');
            // Nota: la unicidad de "un solo contacto principal activo por cliente"
            // se garantiza en ClienteContacto::markAsPrincipal() y su observer,
            // no con constraint de BD (MySQL no soporta partial indexes).
        });

        // Sin triggers: MySQL prohíbe actualizar la misma tabla dentro de
        // su trigger (error 1442) y rompía todos los inserts. La garantía
        // de "un solo principal" vive en el modelo (evento Eloquent).

        // -------------------------------------------------------
        // cliente_integraciones
        // -------------------------------------------------------
        Schema::create('cliente_integraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->unsignedSmallInteger('tipo_integracion_id');
            $table->foreign('tipo_integracion_id')->references('id')->on('tipos_integracion')->restrictOnDelete();
            $table->unsignedSmallInteger('estado_integracion_id');
            $table->foreign('estado_integracion_id')->references('id')->on('estados_integracion')->restrictOnDelete();
            $table->string('nombre_tienda', 255)->nullable();
            $table->string('referencia_externa', 255)->nullable();
            $table->text('credenciales_ref')->nullable();
            $table->timestamp('conectado_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_contacto_principal_upd');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_contacto_principal_ins');
        Schema::dropIfExists('cliente_integraciones');
        Schema::dropIfExists('cliente_contactos');
    }
};
