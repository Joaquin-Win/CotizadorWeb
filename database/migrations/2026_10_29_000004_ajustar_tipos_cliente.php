<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tipos de cliente:
 *  - "Público" pasa a llamarse "Proveedores" (el código PUBLICO se conserva: lo usa el motor).
 *  - "B2B Premium" se retira: los clientes que lo tenían pasan a B2B y el tipo se desactiva
 *    (no se borra para no romper márgenes/acuerdos históricos que lo referencian).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tipos_cliente')->where('codigo', 'PUBLICO')->update(['nombre' => 'Proveedores']);

        $b2bId     = DB::table('tipos_cliente')->where('codigo', 'B2B')->value('id');
        $premiumId = DB::table('tipos_cliente')->where('codigo', 'B2B_PREMIUM')->value('id');

        if ($premiumId) {
            if ($b2bId) {
                DB::table('clientes')->where('tipo_cliente_id', $premiumId)->update(['tipo_cliente_id' => $b2bId]);
            }
            DB::table('tipos_cliente')->where('id', $premiumId)->update(['activo' => false]);
        }
    }

    public function down(): void
    {
        DB::table('tipos_cliente')->where('codigo', 'PUBLICO')->update(['nombre' => 'Público']);
        DB::table('tipos_cliente')->where('codigo', 'B2B_PREMIUM')->update(['activo' => true]);
    }
};

