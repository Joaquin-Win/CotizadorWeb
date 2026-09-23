<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** acuerdos_comerciales + acuerdo_condiciones */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // acuerdos_comerciales
        // -------------------------------------------------------
        Schema::create('acuerdos_comerciales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->string('codigo', 40);
            $table->decimal('descuento_porcentaje', 6, 2)->nullable()->comment('Descuento sobre precio de lista');
            $table->decimal('recargo_porcentaje', 6, 2)->nullable()->comment('Recargo adicional');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            // Unicidad de código entre acuerdos activos (NULL cuando deleted_at IS NOT NULL)
            $table->string('codigo_unico', 40)
                  ->nullable()
                  ->storedAs('IF(deleted_at IS NULL, codigo, NULL)');
            $table->unique('codigo_unico', 'uq_acuerdos_codigo_activo');
            $table->index('cliente_id');
        });

        // -------------------------------------------------------
        // acuerdo_condiciones
        // -------------------------------------------------------
        Schema::create('acuerdo_condiciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acuerdo_id')->constrained('acuerdos_comerciales')->cascadeOnDelete();
            $table->unsignedSmallInteger('tipo_condicion_id');
            $table->foreign('tipo_condicion_id')->references('id')->on('tipos_condicion_comercial')->restrictOnDelete();
            // Valor polimórfico según tipo_dato del tipo_condicion
            $table->decimal('valor_numerico', 12, 2)->nullable();
            $table->text('valor_texto')->nullable();
            $table->boolean('valor_booleano')->nullable();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->nullOnDelete();
            $table->unsignedSmallInteger('tipo_servicio_id')->nullable();
            $table->foreign('tipo_servicio_id')->references('id')->on('tipos_servicio')->nullOnDelete();
            $table->timestamps();
            $table->index('acuerdo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acuerdo_condiciones');
        Schema::dropIfExists('acuerdos_comerciales');
    }
};
