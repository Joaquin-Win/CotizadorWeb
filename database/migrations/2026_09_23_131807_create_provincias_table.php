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
        Schema::create('provincias', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 100);
            $table->string('codigo_georef', 10)->unique();

            $table->boolean('tiene_deposito')
                ->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index('nombre');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provincias');
    }
};