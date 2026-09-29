<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** clientes + seed de prueba */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tipo_cliente_id');
            $table->foreign('tipo_cliente_id')->references('id')->on('tipos_cliente')->restrictOnDelete();
            $table->unsignedSmallInteger('estado_id');
            $table->foreign('estado_id')->references('id')->on('estados_cliente')->restrictOnDelete();
            $table->string('razon_social', 255);
            $table->string('nombre_fantasia', 255)->nullable();
            $table->string('cuit', 13)->comment('Formato: XX-XXXXXXXX-X');
            // Columna GENERADA: unicidad de CUIT entre clientes activos.
            // CASE WHEN en vez de IF para que corra igual en MySQL y en SQLite (tests).
            $table->string('cuit_unico', 13)
                  ->nullable()
                  ->storedAs('CASE WHEN deleted_at IS NULL THEN cuit END');
            $table->unique('cuit_unico', 'uq_clientes_cuit_activo');
            $table->string('email_facturacion', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->foreignId('localidad_id')->nullable()->constrained('localidades')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tipo_cliente_id');
            $table->index('estado_id');
            $table->index('razon_social');
        });

         // Resuelve FK circular: ahora que clientes existe, agregamos la FK en usuarios
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreign('cliente_id')
                  ->references('id')
                  ->on('clientes')
                  ->restrictOnDelete();
        });

        
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });
        Schema::dropIfExists('clientes');
    }
};
