<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La columna del dump viejo era `email`; el schema actual usa
     * `email_facturacion`. Se agrega y se migran los datos.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('clientes', 'email_facturacion')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('email_facturacion', 255)->nullable()->after('cuit');
            });
        }

        if (Schema::hasColumn('clientes', 'email')) {
            DB::statement('UPDATE `clientes` SET `email_facturacion` = `email` WHERE `email_facturacion` IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('email_facturacion');
        });
    }
};
