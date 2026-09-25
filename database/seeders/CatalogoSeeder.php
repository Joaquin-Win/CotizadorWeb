<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //roles
        DB::table('roles')->insert([
        [
            'id' => 1,
            'nombre'=> 'ADMIN',
            'codigo'=> 'ADMIN',
        ],
        [
            'id' => 2,
            'nombre'=> 'CLIENTE',
            'codigo'=> 'CLIENTE',
        ],
    ]);

     //tipos de cliente
        DB::table('tipos_cliente')->insert([
        [
            'id' => 1,
            'nombre'=> 'Publico',
            'codigo'=> 'PUBLICO',
            'nivel'=>0,
            'descuento_base'=> 0,
        ],
        [
            'id' => 2,
            'nombre'=> 'B2B',
            'codigo'=> 'B2b',
            'nivel'=>1,
            'descuento_base'=> 0,
        ],
        [
            'id' => 3,
            'nombre'=> 'B2B Premium',
            'codigo'=> 'B2B_PREMIUM',
            'nivel'=>2,
            'descuento_base'=> 5,
        ],
    ]);

    //Tipos de servicio
    DB::table('tipos_servicio')->insert([
        [
            'codigo' => 'TRONCAL',
            'activo' => true
        ],
        [
            'codigo' => 'PRIMERA_MILLA',
            'activo' => true
        ],
        [
            'codigo' => 'ULTIMA_MILLA',
            'activo' => true
        ],
        [
            'codigo' => 'PUERTA_PUERTA',
            'activo' => true
        ],
        [
            'codigo' => 'EXPRESO',
            'activo' => true
        ],
        [
            'codigo' => 'FLEX',
            'activo' => false
        ],
    ]);
    
    // unidades de medida
    DB::table('unidades_medida')->insert([
        ['codigo' => 'KG'],
        ['codigo' => 'M3'],
        ['codigo' => 'PALLET'],
        ['codigo' => 'BULTO'],
        ['codigo' => 'VIAJE'],
        ['codigo' => 'KM'],
    ]);

    // tipos de bulto

    DB::table('tipos_bulto')->insert([
        [
            'codigo' => 'CAJA',
            'activo' => true,
        ],
        [   'codigo' => 'PALLET',
            'activo' => true,
        ],
        [   'codigo' => 'BOLSA',
            'activo' => true,
        ],
        [
            'codigo'=> 'TAMBOR',
            'activo' => true,
        ],
        [
            'codigo'=> 'MUEBLE',
            'activo' => true,
        ],
        [
            'codigo'=> 'GRANEL',
            'activo' => true,
        ],
    ]);

    // tipos de condicion comercial
    DB::table('tipos_condicion_comercial')->insert([
        ['codigo' => 'MINIMO_ENVIOS'],
        ['codigo' => 'VOLUMEN_MINIMO'],
        ['codigo' => 'PLAZO_PAGO'],
        ['codigo' => 'ZONA_COBERTURA'],
        ['codigo' => 'TIPO_CARGA'],
        ['codigo' => 'SERVICIO_INCLUIDO'],
        ['codigo' => 'RETIRO_DOMICIO'],
        ['codigo' => 'ENTREGA_DOMICILIO'],
        ['codigo' => 'SEGURO_INCLUIDO'],
    ]);
    
    // ORIGENES DE COTIZACION
    DB::table('origenes_cotizacion')->insert([
        [
            'id'=> '1',
            'codigo' => 'WEB_PUBLICA',
        ],
        [
            'id'=> '2',
            'codigo' => 'PANEL_CLIENTE',
        ],
        [
            'id'=> '3',
            'codigo'=> 'BACKOFFICE',
        ],
        [
            'id'=> '4',
            'codigo'=> 'API',
        ],
    ]);

    // ESTADOS DE COTIZACION

    DB::table('estados_cotizacion')->insert([
        ['codigo' => 'BORRADOR'],
        ['codigo' => 'CALCULADA'],
        ['codigo' => 'SOLICITADA'],
        ['codigo' => 'EN_REVISION'],
        ['codigo' => 'RESPONDIDA'],
        ['codigo' => 'ACEPTADA'],
        ['codigo' => 'RECHAZADA'],
        ['codigo'=> 'VENCIDA'],
        ['codigo'=> 'CONVERTIDA_PEDIDO'],
    ]);
    
    // ESTADOS PEDIDO

    DB::table('estados_pedido')->insert([
        ['codigo' => 'PENDIENTE'],
        ['codigo' => 'CONFIRMADO'],
        ['codigo' => 'RETIRADO'],
        ['codigo' => 'EN_TRANSITO'],
        ['codigo' => 'EN_DEPOSITO'],
        ['codigo' => 'ENTREGADO'],
        ['codigo' => 'CANCELADO'],
    ]);

    // ESTADOS DE CLIENTE

    DB::table('estados_cliente')->insert([
        ['codigo' => 'ACTIVO'],
        ['codigo' => 'INACTIVO'],
        ['codigo' => 'SUSPENDIDO'],
    ]);

    //ZONAS
    DB::table('zonas')->insert([
        ['codigo'=> 'NORTE'],
        ['codigo'=> 'CENTRO'],
        ['codigo'=> 'CUYO'],
        ['codigo'=> 'SUR'],
        ['codigo'=> 'AMBA'],
        ['codigo'=> 'NEA'],
    ]);

    //COSTOS ADICIONALES

    DB::table('costos_adicionales')->insert([
        [
            'nombre'=> 'Carga',
            'monto'=> '12500',
            'unidad'=> '$',
            'activo'=> true,
        ],
        [
            'nombre'=> 'Descarga',
            'monto'=> '12500',
            'unidad'=> '$',
            'activo'=> true,
        ],
    ]);
    }
}
