<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega columnas de snapshot a cotizacion_costos_adicionales.
 *
 * Razón: la spec requiere que una cotización guardada conserve el nombre
 * y la unidad del costo adicional al momento de calcularse, independientemente
 * de cambios futuros en la tabla costos_adicionales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizacion_costos_adicionales', function (Blueprint $table) {
            // Snapshot del nombre al momento de cotizar
            $table->string('nombre_snapshot', 150)
                  ->nullable()
                  ->after('costo_adicional_id')
                  ->comment('Snapshot del nombre del costo adicional al cotizar');

            // Snapshot de la unidad ('$' o '%') al momento de cotizar
            $table->string('unidad_snapshot', 1)
                  ->nullable()
                  ->after('nombre_snapshot')
                  ->comment('Snapshot de la unidad ($ o %) al cotizar');
        });
    }

    public function down(): void
    {
        Schema::table('cotizacion_costos_adicionales', function (Blueprint $table) {
            $table->dropColumn(['nombre_snapshot', 'unidad_snapshot']);
        });
    }
};
