<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Marca de depósito por localidad (editable desde Config. Cotizador → Cobertura Geográfica). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('localidades', function (Blueprint $table) {
            $table->boolean('tiene_deposito')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('localidades', function (Blueprint $table) {
            $table->dropColumn('tiene_deposito');
        });
    }
};
