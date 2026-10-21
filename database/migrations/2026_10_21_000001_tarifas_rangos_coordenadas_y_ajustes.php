<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - tarifas: rango de unidades (min/max) y límites por bulto (m³ y kg).
 * - localidades: coordenadas (lat/lon) para estimar el tiempo de viaje.
 * - ajustes_sistema: configuración global (mail, SMTP, WhatsApp, API Key de mapas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->unsignedInteger('min_unidades')->nullable()->after('max_unidades')
                ->comment('Rango de unidades: desde. NULL = 1.');
            $table->decimal('min_m3_bulto', 10, 3)->nullable()->after('max_m3_bulto');
            $table->decimal('max_kg_bulto', 10, 2)->nullable()->after('min_m3_bulto');
        });

        Schema::table('localidades', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lon', 10, 7)->nullable();
        });

        Schema::create('ajustes_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 80)->unique();
            $table->text('valor')->nullable();
            $table->boolean('cifrado')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_sistema');

        Schema::table('localidades', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lon']);
        });

        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn(['min_unidades', 'min_m3_bulto', 'max_kg_bulto']);
        });
    }
};
