<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega columna palletizado a cotizacion_bultos.
 *
 * Razón: BultoData ya tiene la propiedad paletizado y el motor la necesita
 * para decidir la unidad de cobro (PALLET vs M3/KG). La BD real no la tenía
 * porque el SQL dump original omitió esta columna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizacion_bultos', function (Blueprint $table) {
            $table->boolean('palletizado')
                  ->default(false)
                  ->after('cantidad')
                  ->comment('Indica si el bulto está en pallet. Afecta la unidad de cobro.');
        });
    }

    public function down(): void
    {
        Schema::table('cotizacion_bultos', function (Blueprint $table) {
            $table->dropColumn('palletizado');
        });
    }
};
