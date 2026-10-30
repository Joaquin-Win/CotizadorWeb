<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estandariza tarifas.tiempo_estimado como ENTERO en HORAS (24 = 1 día) para poder sumar
 * los tiempos de las tarifas aplicadas. Convierte los textos existentes:
 * "48 horas" → 48, "3 días" → 72, "5" → 5 (se asume horas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->unsignedInteger('tiempo_estimado_horas')->nullable()->after('max_kg_bulto');
        });

        DB::table('tarifas')->whereNotNull('tiempo_estimado')->orderBy('id')->each(function ($t) {
            $texto = mb_strtolower(trim((string) $t->tiempo_estimado));
            if (! preg_match('/\d+(?:[.,]\d+)?/', $texto, $m)) {
                return;
            }
            $n = (float) str_replace(',', '.', $m[0]);
            $horas = preg_match('/d[ií]a/u', $texto) ? $n * 24 : $n;

            DB::table('tarifas')->where('id', $t->id)->update(['tiempo_estimado_horas' => (int) round($horas)]);
        });

        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn('tiempo_estimado');
        });
        Schema::table('tarifas', function (Blueprint $table) {
            $table->renameColumn('tiempo_estimado_horas', 'tiempo_estimado');
        });
    }

    public function down(): void
    {
        Schema::table('tarifas', function (Blueprint $table) {
            $table->string('tiempo_estimado_texto', 100)->nullable();
        });

        DB::table('tarifas')->whereNotNull('tiempo_estimado')->orderBy('id')->each(function ($t) {
            DB::table('tarifas')->where('id', $t->id)->update(['tiempo_estimado_texto' => $t->tiempo_estimado . ' horas']);
        });

        Schema::table('tarifas', function (Blueprint $table) {
            $table->dropColumn('tiempo_estimado');
        });
        Schema::table('tarifas', function (Blueprint $table) {
            $table->renameColumn('tiempo_estimado_texto', 'tiempo_estimado');
        });
    }
};

