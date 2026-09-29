<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;

/** zonas + zona_provincias + zona_localidades con seed */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // zonas
        // -------------------------------------------------------
        Schema::create('zonas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // -------------------------------------------------------
        // zona_provincias (pivot)
        // -------------------------------------------------------
        Schema::create('zona_provincias', function (Blueprint $table) {
            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('provincia_id')->constrained('provincias')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['zona_id', 'provincia_id']);
            $table->index('provincia_id');
        });

        // -------------------------------------------------------
        // zona_localidades (pivot)
        // -------------------------------------------------------
        Schema::create('zona_localidades', function (Blueprint $table) {
            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('localidad_id')->constrained('localidades')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['zona_id', 'localidad_id']);
            $table->index('localidad_id');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('zona_localidades');
        Schema::dropIfExists('zona_provincias');
        Schema::dropIfExists('zonas');
    }
};
