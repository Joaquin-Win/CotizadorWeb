<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tiempo estimado de entrega cargado manualmente por el admin en cada tarifa
 * (texto libre: "48 horas", "3 días"). Reemplaza el cálculo por APIs de mapas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->string('tiempo_estimado', 100)->nullable()->after('max_kg_bulto');
        });
    }

    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn('tiempo_estimado');
        });
    }
};

