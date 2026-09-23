<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** clientes + seed de prueba */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tipo_cliente_id');
            $table->foreign('tipo_cliente_id')->references('id')->on('tipos_cliente')->restrictOnDelete();
            $table->unsignedSmallInteger('estado_id');
            $table->foreign('estado_id')->references('id')->on('estados_cliente')->restrictOnDelete();
            $table->string('razon_social', 255);
            $table->string('nombre_fantasia', 255)->nullable();
            $table->string('cuit', 13)->comment('Formato: XX-XXXXXXXX-X');
            // Columna GENERADA: unicidad de CUIT entre clientes activos
            $table->string('cuit_unico', 13)
                  ->nullable()
                  ->storedAs('IF(deleted_at IS NULL, cuit, NULL)');
            $table->unique('cuit_unico', 'uq_clientes_cuit_activo');
            $table->string('email_facturacion', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->foreignId('localidad_id')->nullable()->constrained('localidades')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('tipo_cliente_id');
            $table->index('estado_id');
            $table->index('razon_social');
        });

        // Seed: cliente de prueba
        DB::table('clientes')->insert([
            'tipo_cliente_id' => 2, // B2B
            'estado_id'       => 1, // ACTIVO
            'razon_social'    => 'Mueblería López S.R.L.',
            'nombre_fantasia'  => 'Mueblería López',
            'cuit'            => '20-12345678-9',
        ]);

        // Resuelve FK circular: ahora que clientes existe, agregamos la FK en usuarios
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreign('cliente_id')
                  ->references('id')
                  ->on('clientes')
                  ->restrictOnDelete();
        });

        // Seed: usuario cliente vinculado al cliente de prueba
        DB::table('usuarios')->insert([
            'rol_id'     => 2, // CLIENTE
            'cliente_id' => 1,
            'name'       => 'Martín López',
            'email'      => 'mlopez@muebleria.com',
            'password'   => bcrypt('password'),
            'activo'     => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
        });
        Schema::dropIfExists('clientes');
    }
};
