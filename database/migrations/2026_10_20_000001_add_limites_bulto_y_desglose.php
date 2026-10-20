<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - tarifas: límites por tarifa de BULTO (m³ máx. por bulto y unidades máx.).
 * - cotizacion_resultados: snapshot JSON del desglose de cálculo (vista admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->decimal('max_m3_bulto', 10, 3)->nullable()->after('maximo')
                ->comment('Tarifa BULTO: m³ máximo por bulto. NULL = sin límite.');
            $table->unsignedInteger('max_unidades')->nullable()->after('max_m3_bulto')
                ->comment('Tarifa BULTO: cantidad máxima de bultos. NULL = sin límite.');
        });

        Schema::table('cotizacion_resultados', function (Blueprint $table) {
            $table->json('desglose_calculo')->nullable()->after('version_algoritmo');
        });
    }

    public function down(): void
    {
        Schema::table('cotizacion_resultados', function (Blueprint $table) {
            $table->dropColumn('desglose_calculo');
        });

        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn(['max_m3_bulto', 'max_unidades']);
        });
    }
};
