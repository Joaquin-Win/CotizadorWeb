<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * transoft_configuracion — credenciales y config editable de la API Transoft.
 * Los valores sensibles (password, webhook_secret) se guardan encriptados
 * via Laravel Crypt. Esta tabla NO estaba en el dump original pero el
 * controlador Admin\TransoftConfiguracionController la requiere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transoft_configuracion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('clave', 60)->unique()->comment('base_url | username | password | operation_id | webhook_secret');
            $table->text('valor')->nullable();
            $table->boolean('encriptado')->default(false)->comment('Si true, valor se cifra con Crypt::encryptString()');
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        // Filas iniciales vacías — el admin las completa desde el panel
        DB::table('transoft_configuracion')->insertOrIgnore([
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
    }
};
