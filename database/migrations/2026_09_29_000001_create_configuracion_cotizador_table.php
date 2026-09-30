<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * configuracion_cotizador — parámetros editables del motor por el admin.
 * Patrón clave-valor tipado. Cada clave tiene un tipo y descripción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion_cotizador', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique()->comment('Identificador único del parámetro');
            $table->string('valor', 255)->nullable()->comment('Valor almacenado como string');
            $table->string('tipo', 10)->default('STRING')->comment('STRING | NUMBER | BOOLEAN');
            $table->string('descripcion', 255)->nullable();
            $table->string('grupo', 60)->nullable()->comment('Agrupación visual en el panel admin');
            $table->boolean('editable')->default(true)->comment('Si false, solo se modifica por migraciones');
            $table->timestamps();
        });

        // Filas iniciales — el admin las edita desde el panel
        DB::table('configuracion_cotizador')->insertOrIgnore([
            [
                'clave'       => 'seguro_porcentaje',
                'valor'       => '0.80',
                'tipo'        => 'NUMBER',
                'descripcion' => 'Porcentaje del valor declarado para calcular el seguro (ej: 0.80 = 0.80%).',
                'grupo'       => 'seguro',
                'editable'    => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'clave'       => 'iva_porcentaje',
                'valor'       => '0',
                'tipo'        => 'NUMBER',
                'descripcion' => 'Porcentaje de IVA. Actualmente 0 porque las tarifas son precio final.',
                'grupo'       => 'impuestos',
                'editable'    => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'clave'       => 'algoritmo_version',
                'valor'       => 'v2.0.0',
                'tipo'        => 'STRING',
                'descripcion' => 'Versión del algoritmo de cotización. Se guarda en cada cotización como snapshot.',
                'grupo'       => 'sistema',
                'editable'    => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion_cotizador');
    }
};
