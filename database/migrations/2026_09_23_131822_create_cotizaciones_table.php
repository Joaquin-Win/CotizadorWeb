<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    DB::statement("
            CREATE TABLE IF NOT EXISTS `cotizaciones` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

                `estado_id` SMALLINT UNSIGNED NOT NULL,

                `moneda` VARCHAR(3) NOT NULL DEFAULT 'ARS',

                `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `costos_adicionales` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,

                `margen_id` BIGINT UNSIGNED DEFAULT NULL,

                `algoritmo_version` VARCHAR(20) DEFAULT NULL,

                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,

                PRIMARY KEY (`id`, `created_at`),

                KEY `idx_cotizaciones_estado_id` (`estado_id`),
                KEY `idx_cotizaciones_created_at` (`created_at`),
                KEY `idx_cotizaciones_margen_id` (`margen_id`)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci

            PARTITION BY RANGE COLUMNS(`created_at`) (
                PARTITION p_2024 VALUES LESS THAN ('2025-01-01'),
                PARTITION p_2025_q1 VALUES LESS THAN ('2025-04-01'),
                PARTITION p_2025_q2 VALUES LESS THAN ('2025-07-01'),
                PARTITION p_2025_q3 VALUES LESS THAN ('2025-10-01'),
                PARTITION p_2025_q4 VALUES LESS THAN ('2026-01-01'),

                PARTITION p_2026_q1 VALUES LESS THAN ('2026-04-01'),
                PARTITION p_2026_q2 VALUES LESS THAN ('2026-07-01'),
                PARTITION p_2026_q3 VALUES LESS THAN ('2026-10-01'),
                PARTITION p_2026_q4 VALUES LESS THAN ('2027-01-01'),

                PARTITION p_2027_q1 VALUES LESS THAN ('2027-04-01'),
                PARTITION p_2027_q2 VALUES LESS THAN ('2027-07-01'),
                PARTITION p_2027_q3 VALUES LESS THAN ('2027-10-01'),
                PARTITION p_2027_q4 VALUES LESS THAN ('2028-01-01'),

                PARTITION p_future VALUES LESS THAN (MAXVALUE)
            )
        ");
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    DB::statement('DROP TABLE IF EXISTS `cotizaciones`');
  }
};
