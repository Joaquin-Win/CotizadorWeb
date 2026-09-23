<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de estados: estados_cotizacion, estados_pedido,
 *                    estados_cliente, estados_integracion, estados_importacion
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // estados_cotizacion
        // -------------------------------------------------------
        Schema::create('estados_cotizacion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->unsignedSmallInteger('orden');
            $table->boolean('es_final')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique('orden');
        });

        // -------------------------------------------------------
        // estados_pedido
        // -------------------------------------------------------
        Schema::create('estados_pedido', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->unsignedSmallInteger('orden');
            $table->boolean('es_final')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique('orden');
        });

        // -------------------------------------------------------
        // estados_cliente
        // -------------------------------------------------------
        Schema::create('estados_cliente', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('permite_operar')->default(true)->comment('1 = puede cotizar y hacer pedidos');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // estados_integracion
        // -------------------------------------------------------
        Schema::create('estados_integracion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('es_operativo')->default(false)->comment('1 = la integración puede sincronizar');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // estados_importacion
        // -------------------------------------------------------
        Schema::create('estados_importacion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // SEED
        // -------------------------------------------------------
        DB::table('estados_cotizacion')->insert([
            ['codigo' => 'BORRADOR',    'nombre' => 'Borrador',    'orden' => 1, 'es_final' => false, 'activo' => true],
            ['codigo' => 'ENVIADA',     'nombre' => 'Enviada',     'orden' => 2, 'es_final' => false, 'activo' => true],
            ['codigo' => 'EN_REVISION', 'nombre' => 'En revisión', 'orden' => 3, 'es_final' => false, 'activo' => true],
            ['codigo' => 'ACEPTADA',    'nombre' => 'Aceptada',    'orden' => 4, 'es_final' => true,  'activo' => true],
            ['codigo' => 'RECHAZADA',   'nombre' => 'Rechazada',   'orden' => 5, 'es_final' => true,  'activo' => true],
            ['codigo' => 'VENCIDA',     'nombre' => 'Vencida',     'orden' => 6, 'es_final' => true,  'activo' => true],
        ]);

        DB::table('estados_pedido')->insert([
            ['codigo' => 'PENDIENTE',   'nombre' => 'Pendiente',   'orden' => 1, 'es_final' => false, 'activo' => true],
            ['codigo' => 'CONFIRMADO',  'nombre' => 'Confirmado',  'orden' => 2, 'es_final' => false, 'activo' => true],
            ['codigo' => 'RETIRADO',    'nombre' => 'Retirado',    'orden' => 3, 'es_final' => false, 'activo' => true],
            ['codigo' => 'EN_TRANSITO', 'nombre' => 'En tránsito', 'orden' => 4, 'es_final' => false, 'activo' => true],
            ['codigo' => 'EN_DESTINO',  'nombre' => 'En destino',  'orden' => 5, 'es_final' => false, 'activo' => true],
            ['codigo' => 'ENTREGADO',   'nombre' => 'Entregado',   'orden' => 6, 'es_final' => true,  'activo' => true],
            ['codigo' => 'CANCELADO',   'nombre' => 'Cancelado',   'orden' => 7, 'es_final' => true,  'activo' => true],
        ]);

        DB::table('estados_cliente')->insert([
            ['codigo' => 'ACTIVO',     'nombre' => 'Activo',     'permite_operar' => true,  'activo' => true],
            ['codigo' => 'INACTIVO',   'nombre' => 'Inactivo',   'permite_operar' => false, 'activo' => true],
            ['codigo' => 'SUSPENDIDO', 'nombre' => 'Suspendido', 'permite_operar' => false, 'activo' => true],
        ]);

        DB::table('estados_integracion')->insert([
            ['codigo' => 'NO_CONECTADO', 'nombre' => 'No conectado',             'es_operativo' => false, 'activo' => true],
            ['codigo' => 'PENDIENTE',    'nombre' => 'Pendiente de autorización','es_operativo' => false, 'activo' => true],
            ['codigo' => 'CONECTADO',    'nombre' => 'Conectado',                'es_operativo' => true,  'activo' => true],
            ['codigo' => 'ERROR',        'nombre' => 'Con error',                'es_operativo' => false, 'activo' => true],
            ['codigo' => 'REVOCADO',     'nombre' => 'Revocado',                 'es_operativo' => false, 'activo' => true],
        ]);

        DB::table('estados_importacion')->insert([
            ['codigo' => 'PENDIENTE',    'nombre' => 'Pendiente',                'activo' => true],
            ['codigo' => 'PROCESANDO',   'nombre' => 'Procesando',               'activo' => true],
            ['codigo' => 'COMPLETADA',   'nombre' => 'Completada',               'activo' => true],
            ['codigo' => 'CON_ERRORES',  'nombre' => 'Completada con errores',   'activo' => true],
            ['codigo' => 'FALLIDA',      'nombre' => 'Fallida',                  'activo' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_importacion');
        Schema::dropIfExists('estados_integracion');
        Schema::dropIfExists('estados_cliente');
        Schema::dropIfExists('estados_pedido');
        Schema::dropIfExists('estados_cotizacion');
    }
};
