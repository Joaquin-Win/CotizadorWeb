<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tarifas + tarifas_minimas + tiempos_estimados + costos_adicionales + margenes_ganancia
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // tarifas
        // -------------------------------------------------------
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('provincia_origen_id')->constrained('provincias')->restrictOnDelete();
            $table->foreignId('provincia_destino_id')->constrained('provincias')->restrictOnDelete();
            $table->foreignId('localidad_destino_id')->nullable()->constrained('localidades')->restrictOnDelete();
            $table->unsignedSmallInteger('tipo_servicio_id');
            $table->foreign('tipo_servicio_id')->references('id')->on('tipos_servicio')->restrictOnDelete();
            $table->unsignedSmallInteger('unidad_medida_id');
            $table->foreign('unidad_medida_id')->references('id')->on('unidades_medida')->restrictOnDelete();
            $table->decimal('costo_unitario', 12, 2);
            $table->decimal('maximo', 12, 2)->nullable()->comment('Tope de cobro / escaón. NULL = escalón abierto.');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            // Índice principal para búsqueda por ruta
            $table->index(['provincia_origen_id', 'provincia_destino_id', 'tipo_servicio_id'], 'idx_tarifas_ruta');
            $table->index(['vigente_desde', 'vigente_hasta'], 'idx_tarifas_vigencia');
        });

        // -------------------------------------------------------
        // tarifas_minimas
        // -------------------------------------------------------
        Schema::create('tarifas_minimas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincia_origen_id')->constrained('provincias')->restrictOnDelete();
            $table->foreignId('provincia_destino_id')->constrained('provincias')->restrictOnDelete();
            $table->foreignId('localidad_destino_id')->nullable()->constrained('localidades')->restrictOnDelete();
            $table->unsignedSmallInteger('tipo_servicio_id');
            $table->foreign('tipo_servicio_id')->references('id')->on('tipos_servicio')->restrictOnDelete();
            $table->decimal('monto_minimo', 12, 2);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // -------------------------------------------------------
        // tiempos_estimados
        // -------------------------------------------------------
        Schema::create('tiempos_estimados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provincia_origen_id')->constrained('provincias')->restrictOnDelete();
            $table->foreignId('provincia_destino_id')->constrained('provincias')->restrictOnDelete();
            $table->foreignId('localidad_origen_id')->nullable()->constrained('localidades')->restrictOnDelete();
            $table->foreignId('localidad_destino_id')->nullable()->constrained('localidades')->restrictOnDelete();
            $table->unsignedSmallInteger('dias_min');
            $table->unsignedSmallInteger('dias_max');
            $table->string('observacion', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // -------------------------------------------------------
        // costos_adicionales
        // -------------------------------------------------------
        Schema::create('costos_adicionales', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tipo_servicio_id')->nullable();
            $table->foreign('tipo_servicio_id')->references('id')->on('tipos_servicio')->nullOnDelete();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 150);
            $table->string('descripcion', 255)->nullable();
            $table->decimal('monto', 12, 2);
            $table->string('unidad', 5)->default('$')->comment('$ = fijo | % = porcentaje');
            $table->boolean('es_obligatorio')->default(false);
            $table->boolean('activo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // -------------------------------------------------------
        // margenes_ganancia
        // -------------------------------------------------------
        Schema::create('margenes_ganancia', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tipo_cliente_id')->nullable();
            $table->foreign('tipo_cliente_id')->references('id')->on('tipos_cliente')->restrictOnDelete()
                  ->comment('NULL = aplica a todos los segmentos');
            $table->unsignedSmallInteger('tipo_servicio_id')->nullable();
            $table->foreign('tipo_servicio_id')->references('id')->on('tipos_servicio')->restrictOnDelete()
                  ->comment('NULL = aplica a todos los servicios');
            $table->decimal('porcentaje', 6, 2)->comment('Recargo sobre costo. 25.00 = costo × 1.25');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable()->comment('NULL = sin vencimiento');
            $table->string('motivo', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tipo_cliente_id', 'tipo_servicio_id', 'vigente_desde', 'vigente_hasta'], 'idx_margenes_busqueda');
        });

        // Seed margenes del SQL original
        \Illuminate\Support\Facades\DB::table('margenes_ganancia')->insert([
            ['tipo_cliente_id' => null, 'tipo_servicio_id' => null, 'porcentaje' => 30.00, 'vigente_desde' => '2026-01-01', 'vigente_hasta' => null,         'motivo' => 'Margen base de lista (público).'],
            ['tipo_cliente_id' => 2,    'tipo_servicio_id' => null, 'porcentaje' => 20.00, 'vigente_desde' => '2026-01-01', 'vigente_hasta' => '2026-06-30', 'motivo' => 'Margen B2B inicial.'],
            ['tipo_cliente_id' => 2,    'tipo_servicio_id' => null, 'porcentaje' => 22.00, 'vigente_desde' => '2026-07-01', 'vigente_hasta' => null,         'motivo' => 'Ajuste por suba de combustible.'],
            ['tipo_cliente_id' => 3,    'tipo_servicio_id' => null, 'porcentaje' => 18.00, 'vigente_desde' => '2026-01-01', 'vigente_hasta' => null,         'motivo' => 'Cuentas clave: margen reducido.'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('margenes_ganancia');
        Schema::dropIfExists('costos_adicionales');
        Schema::dropIfExists('tiempos_estimados');
        Schema::dropIfExists('tarifas_minimas');
        Schema::dropIfExists('tarifas');
    }
};
