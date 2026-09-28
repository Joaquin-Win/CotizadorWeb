<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de catálogo sin dependencias de FK externas.
 * Incluye: roles, tipos_cliente, tipos_bulto, tipos_servicio,
 *          unidades_medida, tipos_condicion_comercial,
 *          tipos_integracion, tipos_importacion, tipos_documento,
 *          origenes_cotizacion
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // roles
        // -------------------------------------------------------
        Schema::create('roles', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('es_interno')->default(true)->comment('1 = personal SET. 0 = usuario cliente.');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_cliente
        // -------------------------------------------------------
        Schema::create('tipos_cliente', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedTinyInteger('nivel')->default(0)->comment('0=Público, 1=B2B, 2=B2B Premium');
            $table->boolean('requiere_usuario')->default(true)->comment('0 = puede cotizar sin login');
            $table->boolean('permite_acuerdo_comercial')->default(true);
            $table->decimal('descuento_base_porcentaje', 5, 2)->default(0.00);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique('nivel');
        });

        // -------------------------------------------------------
        // tipos_bulto
        // -------------------------------------------------------
        Schema::create('tipos_bulto', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_servicio
        // -------------------------------------------------------
        Schema::create('tipos_servicio', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // unidades_medida
        // -------------------------------------------------------
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 50);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_condicion_comercial
        // -------------------------------------------------------
        Schema::create('tipos_condicion_comercial', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->string('tipo_dato', 10)->comment('NUMERO | TEXTO | BOOLEANO | ZONA | SERVICIO');
            $table->string('unidad', 20)->nullable()->comment('Unidad legible del valor numérico');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_integracion
        // -------------------------------------------------------
        Schema::create('tipos_integracion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_importacion
        // -------------------------------------------------------
        Schema::create('tipos_importacion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_documento
        // -------------------------------------------------------
        Schema::create('tipos_documento', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('es_fiscal')->default(false)->comment('1 = comprobante fiscal, nunca se borra');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // origenes_cotizacion
        // -------------------------------------------------------
        Schema::create('origenes_cotizacion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('requiere_login')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('origenes_cotizacion');
        Schema::dropIfExists('tipos_documento');
        Schema::dropIfExists('tipos_importacion');
        Schema::dropIfExists('tipos_integracion');
        Schema::dropIfExists('tipos_condicion_comercial');
        Schema::dropIfExists('unidades_medida');
        Schema::dropIfExists('tipos_servicio');
        Schema::dropIfExists('tipos_bulto');
        Schema::dropIfExists('tipos_cliente');
        Schema::dropIfExists('roles');
    }
};
