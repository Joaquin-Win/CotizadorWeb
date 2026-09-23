<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** importaciones_excel + importaciones_errores */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('importaciones_excel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operador_id')->constrained('usuarios')->restrictOnDelete()
                  ->comment('Usuario interno que subió el archivo');
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->unsignedSmallInteger('tipo_importacion_id');
            $table->foreign('tipo_importacion_id')->references('id')->on('tipos_importacion')->restrictOnDelete();
            $table->unsignedSmallInteger('estado_id');
            $table->foreign('estado_id')->references('id')->on('estados_importacion')->restrictOnDelete();
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 255);
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_exitosas')->default(0);
            $table->unsignedInteger('filas_con_error')->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamp('procesado_at')->nullable();
            $table->timestamps();
            $table->index('operador_id');
            $table->index('cliente_id');
            $table->index('tipo_importacion_id');
            $table->index('estado_id');
        });

        Schema::create('importaciones_errores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('importacion_id')->constrained('importaciones_excel')->cascadeOnDelete();
            $table->unsignedInteger('numero_fila');
            $table->string('columna', 100)->nullable();
            $table->text('mensaje_error');
            $table->json('datos_fila')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('importacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('importaciones_errores');
        Schema::dropIfExists('importaciones_excel');
    }
};
