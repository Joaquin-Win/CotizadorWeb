<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alinea la tabla cotizaciones (particionada) con el schema real del SQL dump.
 *
 * El SQL real tiene: codigo, created_by, updated_by, pedido_id.
 * La migración original usó DROP/CREATE RAW sin esas columnas.
 *
 * NO se puede usar Schema::table() en tablas particionadas de MySQL,
 * por eso usamos ALTER TABLE RAW.
 *
 * Importante: esta migración NO modifica datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Verificar si codigo ya existe antes de agregar
        $columns = DB::select("SHOW COLUMNS FROM `cotizaciones` LIKE 'codigo'");
        if (empty($columns)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                ADD COLUMN `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Código público legible. Se sincroniza via cotizacion_codigos.' AFTER `id`,
                ADD COLUMN `created_by` bigint unsigned DEFAULT NULL AFTER `estado_id`,
                ADD COLUMN `updated_by` bigint unsigned DEFAULT NULL AFTER `created_by`,
                ADD KEY `idx_cotizaciones_codigo` (`codigo`),
                ADD KEY `idx_cotizaciones_created_by` (`created_by`),
                ADD KEY `idx_cotizaciones_updated_by` (`updated_by`)
            ");
        }
    }

    public function down(): void
    {
        // Solo quitar si existen
        $columns = DB::select("SHOW COLUMNS FROM `cotizaciones` LIKE 'codigo'");
        if (!empty($columns)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                DROP KEY `idx_cotizaciones_codigo`,
                DROP KEY `idx_cotizaciones_created_by`,
                DROP KEY `idx_cotizaciones_updated_by`,
                DROP COLUMN `codigo`,
                DROP COLUMN `created_by`,
                DROP COLUMN `updated_by`
            ");
        }
    }
};
