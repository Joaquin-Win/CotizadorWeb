<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Agrega apellido y DNI a los datos personales (lead) de la cotización. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotizacion_leads', function (Blueprint $table) {
            $table->string('apellido_cliente', 100)->nullable()->after('nombre_cliente');
            $table->string('dni_cliente', 20)->nullable()->after('apellido_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('cotizacion_leads', function (Blueprint $table) {
            $table->dropColumn(['apellido_cliente', 'dni_cliente']);
        });
    }
};
