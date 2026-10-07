<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('localidades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provincia_id')
                ->constrained('provincias')
                ->restrictOnDelete();

            $table->string('nombre', 150);
            $table->string('codigo_postal', 10)->nullable();
            $table->string('codigo_georef', 20)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('nombre');
            $table->index('codigo_postal');
            $table->index('codigo_georef');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localidades');
    }
};