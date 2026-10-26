<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * documentos — comprobantes (remito, factura...) de una empresa, usados por el portal.
 * El modelo Documento existía sin su tabla; se siembran también los tipos_documento
 * porque el portal los necesita para clasificar el archivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->unsignedSmallInteger('tipo_documento_id');
            $table->foreign('tipo_documento_id')->references('id')->on('tipos_documento')->restrictOnDelete();
            $table->unsignedSmallInteger('punto_venta')->default(1);
            $table->string('numero_documento', 50);
            $table->date('fecha');
            $table->string('url_archivo', 255);
            $table->timestamps();
            $table->index('cliente_id');
            // DocumentoController::store responde "ya existe" ante este duplicado
            $table->unique(['tipo_documento_id', 'punto_venta', 'numero_documento'], 'documentos_tipo_pv_numero_unique');
        });

        foreach ([
            ['REMITO', 'Remito', false],
            ['FACTURA', 'Factura', true],
            ['NOTA_CREDITO', 'Nota de crédito', true],
            ['OTRO', 'Otro', false],
        ] as [$codigo, $nombre, $fiscal]) {
            DB::table('tipos_documento')->updateOrInsert(
                ['codigo' => $codigo],
                ['nombre' => $nombre, 'es_fiscal' => $fiscal, 'activo' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
