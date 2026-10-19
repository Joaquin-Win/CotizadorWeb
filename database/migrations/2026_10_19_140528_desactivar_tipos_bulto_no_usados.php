<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Desactiva los tipos de bulto que no se usan en el cotizador SET:
 * BOLSA, TAMBOR y MUEBLE.
 *
 * Se mantienen activos: CAJA, PALLET y GRANEL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tipos_bulto')
            ->whereIn('codigo', ['BOLSA', 'TAMBOR', 'MUEBLE'])
            ->update(['activo' => false]);
    }

    public function down(): void
    {
        DB::table('tipos_bulto')
            ->whereIn('codigo', ['BOLSA', 'TAMBOR', 'MUEBLE'])
            ->update(['activo' => true]);
    }
};
