<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jerarquía geográfica: Provincia → Departamento → Localidad.
 * (GeoRef trae el departamento de cada localidad; en CABA son las "Comunas".)
 * Re-ejecutar LocalidadesSeeder para completar los datos (no pisa activo/tiene_deposito).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('localidades', function (Blueprint $table) {
            $table->string('departamento_georef', 12)->nullable()->after('codigo_georef')->index();
            $table->string('departamento', 150)->nullable()->after('departamento_georef');
        });
    }

    public function down(): void
    {
        Schema::table('localidades', function (Blueprint $table) {
            $table->dropIndex(['departamento_georef']);
            $table->dropColumn(['departamento_georef', 'departamento']);
        });
    }
};
