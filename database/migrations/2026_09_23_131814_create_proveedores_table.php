<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** proveedores */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('cuit', 13);
            $table->string('email', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            // Unicidad de CUIT entre proveedores activos (NULL cuando deleted_at IS NOT NULL)
            $table->string('cuit_unico', 13)
                  ->nullable()
                  ->storedAs('IF(deleted_at IS NULL, cuit, NULL)');
            $table->unique('cuit_unico', 'uq_proveedores_cuit_activo');
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
