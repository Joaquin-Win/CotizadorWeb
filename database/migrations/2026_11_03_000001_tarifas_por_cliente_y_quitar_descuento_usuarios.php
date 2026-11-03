<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tarifas personalizadas por cliente.
 *
 *  - tarifas.user_id (nullable): NULL = tarifa general; con valor = tarifa exclusiva de ese usuario.
 *  - Se eliminan definitivamente los campos de descuento de `usuarios`
 *    (la tabla de autenticación de este proyecto es `usuarios`, no `users`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tarifas', 'user_id')) {
            Schema::table('tarifas', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('proveedor_id')
                    ->constrained('usuarios')->cascadeOnDelete();
                $table->index(['user_id', 'tipo_servicio_id'], 'idx_tarifas_user_servicio');
            });
        }

        $columnas = array_values(array_filter(
            ['tipo_descuento', 'valor_descuento'],
            fn ($c) => Schema::hasColumn('usuarios', $c)
        ));
        if ($columnas !== []) {
            Schema::table('usuarios', fn (Blueprint $table) => $table->dropColumn($columnas));
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('usuarios', 'tipo_descuento')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->enum('tipo_descuento', ['porcentaje', 'fijo', 'ninguno'])->default('ninguno');
                $table->decimal('valor_descuento', 12, 2)->default(0);
            });
        }

        if (Schema::hasColumn('tarifas', 'user_id')) {
            Schema::table('tarifas', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropIndex('idx_tarifas_user_servicio');
                $table->dropColumn('user_id');
            });
        }
    }
};

