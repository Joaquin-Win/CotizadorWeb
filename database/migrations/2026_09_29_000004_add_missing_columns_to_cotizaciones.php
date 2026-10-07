<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alinea la tabla cotizaciones con el schema real.
 *
 * La tabla cotizaciones está particionada, por lo que se utiliza
 * ALTER TABLE mediante SQL directo.
 *
 * Agrega:
 * - codigo
 * - created_by
 * - updated_by
 *
 * No agrega foreign keys hacia otras tablas.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = DB::select("
            SHOW COLUMNS FROM `cotizaciones`
        ");

        $existingColumns = [];

        foreach ($columns as $column) {
            $existingColumns[] = $column->Field;
        }

        /*
         * codigo
         */
        if (!in_array('codigo', $existingColumns, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                ADD COLUMN `codigo`
                    VARCHAR(30)
                    COLLATE utf8mb4_unicode_ci
                    NOT NULL
                    DEFAULT ''
                    COMMENT 'Código público legible. Se sincroniza via cotizacion_codigos.'
                    AFTER `id`
            ");
        }

        /*
         * created_by
         */
        if (!in_array('created_by', $existingColumns, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                ADD COLUMN `created_by`
                    BIGINT UNSIGNED
                    DEFAULT NULL
                    AFTER `estado_id`
            ");
        }

        /*
         * updated_by
         */
        if (!in_array('updated_by', $existingColumns, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                ADD COLUMN `updated_by`
                    BIGINT UNSIGNED
                    DEFAULT NULL
                    AFTER `created_by`
            ");
        }

        /*
         * Obtener índices actuales.
         */
        $indexes = DB::select("
            SHOW INDEX FROM `cotizaciones`
        ");

        $existingIndexes = [];

        foreach ($indexes as $index) {
            $existingIndexes[] = $index->Key_name;
        }

        /*
         * Índice de codigo.
         */
        if (!in_array('idx_cotizaciones_codigo', $existingIndexes, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                ADD KEY `idx_cotizaciones_codigo` (`codigo`)
            ");
        }

        /*
         * Índice de created_by.
         */
        if (!in_array('idx_cotizaciones_created_by', $existingIndexes, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                ADD KEY `idx_cotizaciones_created_by` (`created_by`)
            ");
        }

        /*
         * Índice de updated_by.
         */
        if (!in_array('idx_cotizaciones_updated_by', $existingIndexes, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                ADD KEY `idx_cotizaciones_updated_by` (`updated_by`)
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = DB::select("
            SHOW COLUMNS FROM `cotizaciones`
        ");

        $existingColumns = [];

        foreach ($columns as $column) {
            $existingColumns[] = $column->Field;
        }

        $indexes = DB::select("
            SHOW INDEX FROM `cotizaciones`
        ");

        $existingIndexes = [];

        foreach ($indexes as $index) {
            $existingIndexes[] = $index->Key_name;
        }

        /*
         * Eliminar índices antes que sus columnas.
         */
        if (in_array('idx_cotizaciones_codigo', $existingIndexes, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                DROP KEY `idx_cotizaciones_codigo`
            ");
        }

        if (in_array('idx_cotizaciones_created_by', $existingIndexes, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                DROP KEY `idx_cotizaciones_created_by`
            ");
        }

        if (in_array('idx_cotizaciones_updated_by', $existingIndexes, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                DROP KEY `idx_cotizaciones_updated_by`
            ");
        }

        /*
         * Eliminar columnas.
         */
        if (in_array('codigo', $existingColumns, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                DROP COLUMN `codigo`
            ");
        }

        if (in_array('created_by', $existingColumns, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                DROP COLUMN `created_by`
            ");
        }

        if (in_array('updated_by', $existingColumns, true)) {
            DB::statement("
                ALTER TABLE `cotizaciones`
                DROP COLUMN `updated_by`
            ");
        }
    }
};