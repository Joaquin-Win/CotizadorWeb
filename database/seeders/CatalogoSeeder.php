<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // -------------------------------------------------------
        // roles
        // -------------------------------------------------------
        DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'codigo' => 'ADMIN',   'nombre' => 'Administrador', 'es_interno' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'codigo' => 'CLIENTE', 'nombre' => 'Cliente',       'es_interno' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // tipos_cliente
        // -------------------------------------------------------
        DB::table('tipos_cliente')->insertOrIgnore([
            [
                'id' => 1, 'codigo' => 'PUBLICO', 'nombre' => 'Público', 'nivel' => 0,
                'requiere_usuario' => false, 'permite_acuerdo_comercial' => false,
                'descuento_base_porcentaje' => 0, 'activo' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 2, 'codigo' => 'B2B', 'nombre' => 'B2B', 'nivel' => 1,
                'requiere_usuario' => true, 'permite_acuerdo_comercial' => true,
                'descuento_base_porcentaje' => 0, 'activo' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 3, 'codigo' => 'B2B_PREMIUM', 'nombre' => 'B2B Premium', 'nivel' => 2,
                'requiere_usuario' => true, 'permite_acuerdo_comercial' => true,
                'descuento_base_porcentaje' => 5, 'activo' => true,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        // -------------------------------------------------------
        // tipos_servicio — solo EXPRESO y FLEX activos per dump
        // -------------------------------------------------------
        DB::table('tipos_servicio')->insertOrIgnore([
            ['codigo' => 'TRONCAL',       'nombre' => 'Troncal',         'activo' => true,  'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'PRIMERA_MILLA', 'nombre' => 'Primera milla',   'activo' => true,  'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'ULTIMA_MILLA',  'nombre' => 'Última milla',    'activo' => true,  'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'PUERTA_PUERTA', 'nombre' => 'Puerta a puerta', 'activo' => true,  'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'EXPRESO',       'nombre' => 'Expreso',         'activo' => true,  'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'FLEX',          'nombre' => 'Flex',            'activo' => true,  'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // unidades_medida
        // -------------------------------------------------------
        DB::table('unidades_medida')->insertOrIgnore([
            ['codigo' => 'KG',     'nombre' => 'Kilogramo',    'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'M3',     'nombre' => 'Metro cúbico', 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'PALLET', 'nombre' => 'Pallet',       'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'BULTO',  'nombre' => 'Bulto',        'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'VIAJE',  'nombre' => 'Viaje',        'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'KM',     'nombre' => 'Kilómetro',    'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // tipos_bulto
        // -------------------------------------------------------
        DB::table('tipos_bulto')->insertOrIgnore([
            ['codigo' => 'CAJA',   'nombre' => 'Caja',   'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'PALLET', 'nombre' => 'Pallet', 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'BOLSA',  'nombre' => 'Bolsa',  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'TAMBOR', 'nombre' => 'Tambor', 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'MUEBLE', 'nombre' => 'Mueble', 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'GRANEL', 'nombre' => 'Granel', 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // tipos_condicion_comercial
        // -------------------------------------------------------
        DB::table('tipos_condicion_comercial')->insertOrIgnore([
            ['codigo' => 'MINIMO_ENVIOS',     'nombre' => 'Mínimo de envíos',     'tipo_dato' => 'NUMERO',   'unidad' => 'envíos', 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'VOLUMEN_MINIMO',    'nombre' => 'Volumen mínimo',       'tipo_dato' => 'NUMERO',   'unidad' => 'm3',     'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'PLAZO_PAGO',        'nombre' => 'Plazo de pago',        'tipo_dato' => 'NUMERO',   'unidad' => 'días',   'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'ZONA_COBERTURA',    'nombre' => 'Zona de cobertura',    'tipo_dato' => 'ZONA',     'unidad' => null,     'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'TIPO_CARGA',        'nombre' => 'Tipo de carga',        'tipo_dato' => 'TEXTO',    'unidad' => null,     'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'SERVICIO_INCLUIDO', 'nombre' => 'Servicio incluido',    'tipo_dato' => 'SERVICIO', 'unidad' => null,     'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'RETIRO_DOMICILIO',  'nombre' => 'Retiro en domicilio',  'tipo_dato' => 'BOOLEANO', 'unidad' => null,     'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'ENTREGA_DOMICILIO', 'nombre' => 'Entrega en domicilio', 'tipo_dato' => 'BOOLEANO', 'unidad' => null,     'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'SEGURO_INCLUIDO',   'nombre' => 'Seguro incluido',      'tipo_dato' => 'BOOLEANO', 'unidad' => null,     'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // origenes_cotizacion
        // -------------------------------------------------------
        DB::table('origenes_cotizacion')->insertOrIgnore([
            ['id' => 1, 'codigo' => 'WEB_PUBLICA',   'nombre' => 'Web pública',   'requiere_login' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'codigo' => 'PANEL_CLIENTE', 'nombre' => 'Panel cliente', 'requiere_login' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'codigo' => 'BACKOFFICE',    'nombre' => 'Backoffice',    'requiere_login' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'codigo' => 'API',           'nombre' => 'API',           'requiere_login' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // estados_cotizacion — 6 estados del dump
        // -------------------------------------------------------
        DB::table('estados_cotizacion')->insertOrIgnore([
            ['codigo' => 'BORRADOR',    'nombre' => 'Borrador',    'orden' => 1, 'es_final' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'ENVIADA',     'nombre' => 'Enviada',     'orden' => 2, 'es_final' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'EN_REVISION', 'nombre' => 'En revisión', 'orden' => 3, 'es_final' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'ACEPTADA',    'nombre' => 'Aceptada',    'orden' => 4, 'es_final' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'RECHAZADA',   'nombre' => 'Rechazada',   'orden' => 5, 'es_final' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'VENCIDA',     'nombre' => 'Vencida',     'orden' => 6, 'es_final' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // estados_pedido — 7 estados del dump
        // -------------------------------------------------------
        DB::table('estados_pedido')->insertOrIgnore([
            ['codigo' => 'PENDIENTE',   'nombre' => 'Pendiente',   'orden' => 1, 'es_final' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'CONFIRMADO',  'nombre' => 'Confirmado',  'orden' => 2, 'es_final' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'RETIRADO',    'nombre' => 'Retirado',    'orden' => 3, 'es_final' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'EN_TRANSITO', 'nombre' => 'En tránsito', 'orden' => 4, 'es_final' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'EN_DESTINO',  'nombre' => 'En destino',  'orden' => 5, 'es_final' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'ENTREGADO',   'nombre' => 'Entregado',   'orden' => 6, 'es_final' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'CANCELADO',   'nombre' => 'Cancelado',   'orden' => 7, 'es_final' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // estados_cliente — 5 estados del dump con permite_operar
        // -------------------------------------------------------
        DB::table('estados_cliente')->insertOrIgnore([
            ['codigo' => 'ACTIVO',     'nombre' => 'Activo',     'permite_operar' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'INACTIVO',   'nombre' => 'Inactivo',   'permite_operar' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'SUSPENDIDO', 'nombre' => 'Suspendido', 'permite_operar' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'MOROSO',     'nombre' => 'Moroso',     'permite_operar' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'PROSPECTO',  'nombre' => 'Prospecto',  'permite_operar' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // estados_integracion
        // -------------------------------------------------------
        DB::table('estados_integracion')->insertOrIgnore([
            ['codigo' => 'NO_CONECTADO', 'nombre' => 'No conectado',              'es_operativo' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'PENDIENTE',    'nombre' => 'Pendiente de autorización', 'es_operativo' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'CONECTADO',    'nombre' => 'Conectado',                 'es_operativo' => true,  'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'ERROR',        'nombre' => 'Con error',                 'es_operativo' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'REVOCADO',     'nombre' => 'Revocado',                  'es_operativo' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // estados_importacion
        // -------------------------------------------------------
        DB::table('estados_importacion')->insertOrIgnore([
            ['codigo' => 'PENDIENTE',   'nombre' => 'Pendiente',              'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'PROCESANDO',  'nombre' => 'Procesando',             'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'COMPLETADA',  'nombre' => 'Completada',             'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'CON_ERRORES', 'nombre' => 'Completada con errores', 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'FALLIDA',     'nombre' => 'Fallida',                'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // -------------------------------------------------------
        // costos_adicionales — datos de ejemplo
        // -------------------------------------------------------
        DB::table('costos_adicionales')->insertOrIgnore([
            ['codigo' => 'CARGA',    'nombre' => 'Carga',    'monto' => 12500, 'unidad' => '$', 'es_obligatorio' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'DESCARGA', 'nombre' => 'Descarga', 'monto' => 12500, 'unidad' => '$', 'es_obligatorio' => false, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}