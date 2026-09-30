<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vistas v_* — copiadas del dump cotizador_set.sql.
 * Se crean con DB::statement(). En down() se eliminan en orden inverso de dependencias.
 */
return new class extends Migration {
    public function up(): void
    {
        // v_cotizaciones_activas — cotizaciones no borradas con datos de envío
        DB::statement("
            CREATE OR REPLACE VIEW `v_cotizaciones_activas` AS
            SELECT
                c.`id`,
                c.`origen_id`,
                c.`tipo_cliente_id`,
                c.`cliente_id`,
                c.`usuario_id`,
                c.`acuerdo_id`,
                c.`estado_id`,
                ec.`codigo`  AS `estado_codigo`,
                ec.`nombre`  AS `estado_nombre`,
                c.`created_at`,
                c.`updated_at`
            FROM `cotizaciones` c
            INNER JOIN `estados_cotizacion` ec ON ec.`id` = c.`estado_id`
            WHERE c.`deleted_at` IS NULL
        ");

        // v_pedidos_activos — pedidos no borrados con estado legible
        DB::statement("
            CREATE OR REPLACE VIEW `v_pedidos_activos` AS
            SELECT
                p.`id`,
                p.`cliente_id`,
                p.`importacion_id`,
                p.`localidad_destino_id`,
                p.`estado_id`,
                ep.`codigo`  AS `estado_codigo`,
                ep.`nombre`  AS `estado_nombre`,
                p.`numero_pedido`,
                p.`fecha`,
                p.`transoft_tracking`,
                p.`transoft_estado_codigo`,
                p.`created_at`,
                p.`updated_at`
            FROM `pedidos` p
            INNER JOIN `estados_pedido` ep ON ep.`id` = p.`estado_id`
            WHERE p.`deleted_at` IS NULL
        ");

        // v_tarifas_vigentes — tarifas cuya vigencia cubre hoy y no están eliminadas
        DB::statement("
            CREATE OR REPLACE VIEW `v_tarifas_vigentes` AS
            SELECT
                t.`id`,
                t.`proveedor_id`,
                t.`provincia_origen_id`,
                t.`provincia_destino_id`,
                t.`localidad_destino_id`,
                t.`tipo_servicio_id`,
                t.`unidad_medida_id`,
                t.`costo_unitario`,
                t.`maximo`,
                t.`vigente_desde`,
                t.`vigente_hasta`
            FROM `tarifas` t
            WHERE t.`deleted_at` IS NULL
              AND t.`vigente_desde` <= CURDATE()
              AND (t.`vigente_hasta` IS NULL OR t.`vigente_hasta` >= CURDATE())
        ");

        // v_margenes_vigentes — márgenes cuya vigencia cubre hoy y no están eliminados
        DB::statement("
            CREATE OR REPLACE VIEW `v_margenes_vigentes` AS
            SELECT
                m.`id`,
                m.`tipo_cliente_id`,
                m.`tipo_servicio_id`,
                m.`porcentaje`,
                m.`vigente_desde`,
                m.`vigente_hasta`,
                m.`motivo`
            FROM `margenes_ganancia` m
            WHERE m.`deleted_at` IS NULL
              AND m.`vigente_desde` <= CURDATE()
              AND (m.`vigente_hasta` IS NULL OR m.`vigente_hasta` >= CURDATE())
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS `v_margenes_vigentes`');
        DB::statement('DROP VIEW IF EXISTS `v_tarifas_vigentes`');
        DB::statement('DROP VIEW IF EXISTS `v_pedidos_activos`');
        DB::statement('DROP VIEW IF EXISTS `v_cotizaciones_activas`');
    }
};
