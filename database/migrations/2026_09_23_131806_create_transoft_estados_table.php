<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * transoft_estados: mapeo de códigos Transoft (PC, TT, ED...) a estados internos.
 * transoft_configuracion: tabla de configuración editable por admin para la API.
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // transoft_estados
        // -------------------------------------------------------
        Schema::create('transoft_estados', function (Blueprint $table) {
            $table->string('codigo', 4)->primary()->comment('PC, TT, ED...');
            $table->string('descripcion', 100);
            $table->unsignedSmallInteger('estado_pedido_id')->nullable();
            $table->unsignedSmallInteger('estado_cotizacion_id')->nullable();
            $table->foreign('estado_pedido_id')->references('id')->on('estados_pedido')->nullOnDelete();
            $table->foreign('estado_cotizacion_id')->references('id')->on('estados_cotizacion')->nullOnDelete();
            $table->boolean('es_final')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // transoft_configuracion  (NUEVO - no está en el SQL original)
        // Almacena credenciales y configuración editable de la API Transoft.
        // Los valores sensibles (password) se guardan encriptados.
        // -------------------------------------------------------
        Schema::create('transoft_configuracion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('clave', 60)->unique()->comment('base_url | username | password | operation_id | webhook_secret');
            $table->text('valor')->nullable();
            $table->boolean('encriptado')->default(false)->comment('Si true, valor se encripta con encrypt()');
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        // -------------------------------------------------------
        // SEED: estados Transoft del SQL original
        // -------------------------------------------------------
        DB::table('transoft_estados')->insert([
            ['codigo' => 'ED', 'descripcion' => 'Entregada en Destino',      'estado_pedido_id' => null, 'estado_cotizacion_id' => 4, 'es_final' => true,  'activo' => true],
            ['codigo' => 'PC', 'descripcion' => 'Pre Carga',                 'estado_pedido_id' => null, 'estado_cotizacion_id' => 2, 'es_final' => false, 'activo' => true],
            ['codigo' => 'PN', 'descripcion' => 'Pre Carga No Recibida',     'estado_pedido_id' => null, 'estado_cotizacion_id' => 1, 'es_final' => false, 'activo' => true],
            ['codigo' => 'PR', 'descripcion' => 'Pendiente retiro',          'estado_pedido_id' => null, 'estado_cotizacion_id' => 2, 'es_final' => false, 'activo' => true],
            ['codigo' => 'RC', 'descripcion' => 'Retira Destinatario',       'estado_pedido_id' => null, 'estado_cotizacion_id' => 3, 'es_final' => false, 'activo' => true],
            ['codigo' => 'RD', 'descripcion' => 'Rechazada Total en Destino','estado_pedido_id' => null, 'estado_cotizacion_id' => 5, 'es_final' => true,  'activo' => true],
            ['codigo' => 'RL', 'descripcion' => 'Reparto Local',             'estado_pedido_id' => null, 'estado_cotizacion_id' => 3, 'es_final' => false, 'activo' => true],
            ['codigo' => 'RP', 'descripcion' => 'Rechazada Parcial',         'estado_pedido_id' => null, 'estado_cotizacion_id' => 4, 'es_final' => false, 'activo' => true],
            ['codigo' => 'RR', 'descripcion' => 'Retiro Rechazado',          'estado_pedido_id' => null, 'estado_cotizacion_id' => 2, 'es_final' => false, 'activo' => true],
            ['codigo' => 'SR', 'descripcion' => 'Sin Respuesta en Domicilio','estado_pedido_id' => null, 'estado_cotizacion_id' => 3, 'es_final' => false, 'activo' => true],
            ['codigo' => 'TT', 'descripcion' => 'Tránsito Troncal',          'estado_pedido_id' => null, 'estado_cotizacion_id' => 3, 'es_final' => false, 'activo' => true],
            ['codigo' => 'VO', 'descripcion' => 'Devolución a Origen',       'estado_pedido_id' => null, 'estado_cotizacion_id' => 5, 'es_final' => true,  'activo' => true],
        ]);

        // Configuración Transoft vacía - admin la completa desde el panel
        DB::table('transoft_configuracion')->insert([
            ['clave' => 'base_url',       'valor' => null, 'encriptado' => false, 'descripcion' => 'URL base de la API Transoft v4 (ej: https://api.transoft.com.ar)'],
            ['clave' => 'username',       'valor' => null, 'encriptado' => false, 'descripcion' => 'Usuario/transportista para autenticación'],
            ['clave' => 'password',       'valor' => null, 'encriptado' => true,  'descripcion' => 'Contraseña para obtener Bearer token'],
            ['clave' => 'operation_id',   'valor' => null, 'encriptado' => false, 'descripcion' => 'ID de operación (usado en precargas legacy)'],
            ['clave' => 'webhook_secret', 'valor' => null, 'encriptado' => true,  'descripcion' => 'Secreto para verificar firma del webhook'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('transoft_configuracion');
        Schema::dropIfExists('transoft_estados');
    }
};
