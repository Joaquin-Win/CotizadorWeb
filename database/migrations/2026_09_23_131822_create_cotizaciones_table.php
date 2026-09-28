<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * cotizaciones — tabla central del negocio.
 * RAW: usa PARTITION BY RANGE COLUMNS(created_at) copiado del dump.
 * Sin FKs en la tabla particionada (MySQL no las soporta en tablas particionadas).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP TABLE IF EXISTS `cotizaciones`');

        DB::statement("
            CREATE TABLE `cotizaciones` (
              `id` bigint unsigned NOT NULL AUTO_INCREMENT,
              `origen_id` smallint unsigned NOT NULL,
              `tipo_cliente_id` smallint unsigned NOT NULL,
              `cliente_id` bigint unsigned DEFAULT NULL,
              `usuario_id` bigint unsigned DEFAULT NULL,
              `acuerdo_id` bigint unsigned DEFAULT NULL,
              `estado_id` smallint unsigned NOT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime NULL DEFAULT NULL,
              `deleted_at` datetime NULL DEFAULT NULL,
              PRIMARY KEY (`id`, `created_at`),
              KEY `cotizaciones_cliente_id_index` (`cliente_id`),
              KEY `cotizaciones_usuario_id_index` (`usuario_id`),
              KEY `cotizaciones_estado_id_index` (`estado_id`),
              KEY `cotizaciones_origen_id_index` (`origen_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
              PARTITION BY RANGE COLUMNS(`created_at`)
            (
              PARTITION p2024 VALUES LESS THAN ('2025-01-01 00:00:00'),
              PARTITION p2025_q1 VALUES LESS THAN ('2025-04-01 00:00:00'),
              PARTITION p2025_q2 VALUES LESS THAN ('2025-07-01 00:00:00'),
              PARTITION p2025_q3 VALUES LESS THAN ('2025-10-01 00:00:00'),
              PARTITION p2025_q4 VALUES LESS THAN ('2026-01-01 00:00:00'),
              PARTITION p2026_q1 VALUES LESS THAN ('2026-04-01 00:00:00'),
              PARTITION p2026_q2 VALUES LESS THAN ('2026-07-01 00:00:00'),
              PARTITION p2026_q3 VALUES LESS THAN ('2026-10-01 00:00:00'),
              PARTITION p2026_q4 VALUES LESS THAN ('2027-01-01 00:00:00'),
              PARTITION p2027_q1 VALUES LESS THAN ('2027-04-01 00:00:00'),
              PARTITION p2027_q2 VALUES LESS THAN ('2027-07-01 00:00:00'),
              PARTITION p2027_q3 VALUES LESS THAN ('2027-10-01 00:00:00'),
              PARTITION p2027_q4 VALUES LESS THAN ('2028-01-01 00:00:00'),
              PARTITION p_future VALUES LESS THAN (MAXVALUE)
            )
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS `cotizaciones`');
    }
};
