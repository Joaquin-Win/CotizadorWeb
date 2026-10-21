<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Elimina "Caja" como tipo de carga del cotizador.
 * Se desactiva (no se borra) para no romper cotizaciones históricas.
 * Tipos válidos: BULTO, PALLET, GRANEL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tipos_bulto')->where('codigo', 'CAJA')->update(['activo' => false]);
    }

    public function down(): void
    {
        DB::table('tipos_bulto')->where('codigo', 'CAJA')->update(['activo' => true]);
    }
};
