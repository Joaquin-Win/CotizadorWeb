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
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();

            $table->string('tabla', 100);

            $table->unsignedBigInteger('registro_id');

            $table->string('accion', 10)
                ->comment('Acción realizada: CREATE, UPDATE o DELETE');

            $table->unsignedBigInteger('usuario_id')
                ->nullable();

            $table->json('datos_anteriores')
                ->nullable();

            $table->json('datos_nuevos')
                ->nullable();

            $table->string('ip', 45)
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->timestamps();

            $table->index([
                'tabla',
                'registro_id',
            ]);

            $table->index('usuario_id');
            $table->index('accion');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};