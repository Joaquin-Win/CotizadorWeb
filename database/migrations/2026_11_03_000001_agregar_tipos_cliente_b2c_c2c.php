<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tipos de cliente B2C y C2C, con el mismo comportamiento que B2B (acceso al portal y acuerdos comerciales).
 * Solo corre sobre una base con catálogo ya cargado: en una base nueva los crea CatalogoSeeder
 * (si corrieran antes, tomarían los ids 1 y 2 y el seeder dejaría mal el catálogo).
 */
return new class extends Migration
{
    // `nivel` es UNIQUE (0, 1 y 2 ya los usan PUBLICO, B2B y B2B_PREMIUM); ningún código lo lee.
    private const TIPOS = ['B2C' => 3, 'C2C' => 4];

    public function up(): void
    {
        if (! DB::table('tipos_cliente')->exists()) {
            return;
        }

        foreach (self::TIPOS as $codigo => $nivel) {
            DB::table('tipos_cliente')->updateOrInsert(
                ['codigo' => $codigo],
                [
                    'nombre' => $codigo, 'nivel' => $nivel,
                    'requiere_usuario' => true, 'permite_acuerdo_comercial' => true,
                    'descuento_base_porcentaje' => 0, 'activo' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Con clientes asociados no se borra (FK): solo se desactiva.
        foreach (array_keys(self::TIPOS) as $codigo) {
            $id = DB::table('tipos_cliente')->where('codigo', $codigo)->value('id');
            if (! $id) {
                continue;
            }

            DB::table('clientes')->where('tipo_cliente_id', $id)->exists()
                ? DB::table('tipos_cliente')->where('id', $id)->update(['activo' => false])
                : DB::table('tipos_cliente')->where('id', $id)->delete();
        }
    }
};
