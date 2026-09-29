<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Siembra los 12 estados Transoft del dump.
 * estado_cotizacion_id referencia los IDs de estados_cotizacion:
 *   1=BORRADOR, 2=ENVIADA, 3=EN_REVISION, 4=ACEPTADA, 5=RECHAZADA, 6=VENCIDA
 * Idempotente: usa updateOrInsert por PK (codigo).
 */
class TransoftEstadosSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $estados = [
            ['codigo' => 'PN', 'descripcion' => 'Pre Carga No Recibida',      'estado_pedido_id' => null, 'estado_cotizacion_id' => 1, 'es_final' => false],
            ['codigo' => 'PC', 'descripcion' => 'Pre Carga',                  'estado_pedido_id' => null, 'estado_cotizacion_id' => 2, 'es_final' => false],
            ['codigo' => 'PR', 'descripcion' => 'Pendiente retiro',            'estado_pedido_id' => null, 'estado_cotizacion_id' => 2, 'es_final' => false],
            ['codigo' => 'TT', 'descripcion' => 'Tránsito Troncal',            'estado_pedido_id' => null, 'estado_cotizacion_id' => 3, 'es_final' => false],
            ['codigo' => 'RL', 'descripcion' => 'Reparto Local',               'estado_pedido_id' => null, 'estado_cotizacion_id' => 3, 'es_final' => false],
            ['codigo' => 'ED', 'descripcion' => 'Entregada en Destino',        'estado_pedido_id' => null, 'estado_cotizacion_id' => 4, 'es_final' => true],
            ['codigo' => 'SR', 'descripcion' => 'Sin Respuesta en Domicilio',  'estado_pedido_id' => null, 'estado_cotizacion_id' => 3, 'es_final' => false],
            ['codigo' => 'RP', 'descripcion' => 'Rechazada Parcial',           'estado_pedido_id' => null, 'estado_cotizacion_id' => 4, 'es_final' => false],
            ['codigo' => 'RD', 'descripcion' => 'Rechazada Total en Destino',  'estado_pedido_id' => null, 'estado_cotizacion_id' => 5, 'es_final' => true],
            ['codigo' => 'RC', 'descripcion' => 'Retira Destinatario',         'estado_pedido_id' => null, 'estado_cotizacion_id' => 3, 'es_final' => false],
            ['codigo' => 'RR', 'descripcion' => 'Retiro Rechazado',            'estado_pedido_id' => null, 'estado_cotizacion_id' => 2, 'es_final' => false],
            ['codigo' => 'VO', 'descripcion' => 'Devolución a Origen',         'estado_pedido_id' => null, 'estado_cotizacion_id' => 5, 'es_final' => true],
        ];

        foreach ($estados as $estado) {
            DB::table('transoft_estados')->updateOrInsert(
                ['codigo' => $estado['codigo']],
                array_merge($estado, [
                    'activo'     => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
