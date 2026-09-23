<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Tablas de catálogo sin dependencias de FK externas.
 * Incluye: roles, tipos_cliente, tipos_bulto, tipos_servicio,
 *          unidades_medida, tipos_condicion_comercial,
 *          tipos_integracion, tipos_importacion, tipos_documento,
 *          origenes_cotizacion
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // roles
        // -------------------------------------------------------
        Schema::create('roles', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('es_interno')->default(true)->comment('1 = personal SET. 0 = usuario cliente.');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_cliente
        // -------------------------------------------------------
        Schema::create('tipos_cliente', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedTinyInteger('nivel')->default(0)->comment('0=Público, 1=B2B, 2=B2B Premium');
            $table->boolean('requiere_usuario')->default(true)->comment('0 = puede cotizar sin login');
            $table->boolean('permite_acuerdo_comercial')->default(true);
            $table->decimal('descuento_base_porcentaje', 5, 2)->default(0.00);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique('nivel');
        });

        // -------------------------------------------------------
        // tipos_bulto
        // -------------------------------------------------------
        Schema::create('tipos_bulto', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_servicio
        // -------------------------------------------------------
        Schema::create('tipos_servicio', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // unidades_medida
        // -------------------------------------------------------
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 50);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_condicion_comercial
        // -------------------------------------------------------
        Schema::create('tipos_condicion_comercial', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->string('tipo_dato', 10)->comment('NUMERO | TEXTO | BOOLEANO | ZONA | SERVICIO');
            $table->string('unidad', 20)->nullable()->comment('Unidad legible del valor numérico');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_integracion
        // -------------------------------------------------------
        Schema::create('tipos_integracion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_importacion
        // -------------------------------------------------------
        Schema::create('tipos_importacion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // tipos_documento
        // -------------------------------------------------------
        Schema::create('tipos_documento', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('es_fiscal')->default(false)->comment('1 = comprobante fiscal, nunca se borra');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // origenes_cotizacion
        // -------------------------------------------------------
        Schema::create('origenes_cotizacion', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 100);
            $table->boolean('requiere_login')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------------
        // SEED: datos del cotizador_set.sql
        // -------------------------------------------------------
        DB::table('roles')->insert([
            ['codigo' => 'ADMIN',   'nombre' => 'Administrador', 'es_interno' => true,  'activo' => true],
            ['codigo' => 'CLIENTE', 'nombre' => 'Cliente',       'es_interno' => false, 'activo' => true],
        ]);

        DB::table('tipos_cliente')->insert([
            ['codigo' => 'PUBLICO',     'nombre' => 'Público',     'nivel' => 0, 'requiere_usuario' => false, 'permite_acuerdo_comercial' => false, 'descuento_base_porcentaje' => 0.00, 'activo' => true, 'descripcion' => 'Cotiza sin login en el cotizador web. Tarifa de lista.'],
            ['codigo' => 'B2B',         'nombre' => 'B2B',         'nivel' => 1, 'requiere_usuario' => true,  'permite_acuerdo_comercial' => true,  'descuento_base_porcentaje' => 0.00, 'activo' => true, 'descripcion' => 'Empresa con cuenta y tarifa negociada.'],
            ['codigo' => 'B2B_PREMIUM', 'nombre' => 'B2B Premium', 'nivel' => 2, 'requiere_usuario' => true,  'permite_acuerdo_comercial' => true,  'descuento_base_porcentaje' => 5.00, 'activo' => true, 'descripcion' => 'Cuenta clave: tarifa negociada y beneficios de nivel.'],
        ]);

        DB::table('tipos_bulto')->insert([
            ['codigo' => 'CAJA',   'nombre' => 'Caja',                 'activo' => true],
            ['codigo' => 'PALLET', 'nombre' => 'Pallet',               'activo' => true],
            ['codigo' => 'BOLSA',  'nombre' => 'Bolsa',                'activo' => true],
            ['codigo' => 'TAMBOR', 'nombre' => 'Tambor',               'activo' => true],
            ['codigo' => 'MUEBLE', 'nombre' => 'Mueble / volumen grande', 'activo' => true],
            ['codigo' => 'GRANEL', 'nombre' => 'Carga suelta',         'activo' => true],
        ]);

        DB::table('tipos_servicio')->insert([
            ['codigo' => 'TRONCAL',      'nombre' => 'Troncal entre depósitos',    'activo' => true],
            ['codigo' => 'PRIMERA_MILLA','nombre' => 'Primera milla (retiro)',      'activo' => true],
            ['codigo' => 'ULTIMA_MILLA', 'nombre' => 'Última milla (entrega)',      'activo' => true],
            ['codigo' => 'PUERTA_PUERTA','nombre' => 'Puerta a puerta',             'activo' => true],
            ['codigo' => 'EXPRESO',      'nombre' => 'Expreso',                     'activo' => true],
            ['codigo' => 'FLEX',         'nombre' => 'Flex (Mercado Envíos)',       'activo' => true],
        ]);

        DB::table('unidades_medida')->insert([
            ['codigo' => 'KG',     'nombre' => 'Kilogramo',       'descripcion' => 'Cobro por peso.'],
            ['codigo' => 'M3',     'nombre' => 'Metro cúbico',    'descripcion' => 'Cobro por volumen.'],
            ['codigo' => 'PALLET', 'nombre' => 'Pallet',          'descripcion' => 'Cobro por posición de pallet.'],
            ['codigo' => 'BULTO',  'nombre' => 'Bulto',           'descripcion' => 'Cobro por unidad de bulto.'],
            ['codigo' => 'VIAJE',  'nombre' => 'Viaje completo',  'descripcion' => 'Cobro fijo por viaje.'],
            ['codigo' => 'KM',     'nombre' => 'Kilómetro',       'descripcion' => 'Cobro por distancia.'],
        ]);

        DB::table('tipos_condicion_comercial')->insert([
            ['codigo' => 'MINIMO_ENVIOS',    'nombre' => 'Mínimo de envíos',    'tipo_dato' => 'NUMERO',  'unidad' => 'envíos/mes', 'activo' => true],
            ['codigo' => 'VOLUMEN_MINIMO',   'nombre' => 'Volumen mínimo',      'tipo_dato' => 'NUMERO',  'unidad' => 'm3',         'activo' => true],
            ['codigo' => 'PLAZO_PAGO',       'nombre' => 'Plazo de pago',       'tipo_dato' => 'NUMERO',  'unidad' => 'días',       'activo' => true],
            ['codigo' => 'ZONA_COBERTURA',   'nombre' => 'Zona de cobertura',   'tipo_dato' => 'ZONA',    'unidad' => null,         'activo' => true],
            ['codigo' => 'TIPO_CARGA',       'nombre' => 'Tipo de carga',       'tipo_dato' => 'TEXTO',   'unidad' => null,         'activo' => true],
            ['codigo' => 'SERVICIO_INCLUIDO','nombre' => 'Servicio incluido',   'tipo_dato' => 'SERVICIO','unidad' => null,         'activo' => true],
            ['codigo' => 'RETIRO_DOMICILIO', 'nombre' => 'Retiro en domicilio', 'tipo_dato' => 'BOOLEANO','unidad' => null,         'activo' => true],
            ['codigo' => 'ENTREGA_DOMICILIO','nombre' => 'Entrega en domicilio','tipo_dato' => 'BOOLEANO','unidad' => null,         'activo' => true],
            ['codigo' => 'SEGURO_INCLUIDO',  'nombre' => 'Seguro incluido',     'tipo_dato' => 'BOOLEANO','unidad' => null,         'activo' => true],
        ]);

        DB::table('tipos_integracion')->insert([
            ['codigo' => 'MERCADO_LIBRE', 'nombre' => 'Mercado Libre', 'descripcion' => 'Mercado Envíos / Flex.', 'activo' => true],
            ['codigo' => 'TIENDANUBE',   'nombre' => 'Tiendanube',    'descripcion' => 'Tienda online.',         'activo' => true],
            ['codigo' => 'WOOCOMMERCE',  'nombre' => 'WooCommerce',   'descripcion' => 'Tienda WordPress.',      'activo' => true],
            ['codigo' => 'SHOPIFY',      'nombre' => 'Shopify',       'descripcion' => 'Tienda online.',         'activo' => true],
            ['codigo' => 'API_PROPIA',   'nombre' => 'API propia',    'descripcion' => 'Integración directa.',   'activo' => true],
        ]);

        DB::table('tipos_importacion')->insert([
            ['codigo' => 'PEDIDOS',     'nombre' => 'Pedidos',     'activo' => true],
            ['codigo' => 'TARIFAS',     'nombre' => 'Tarifas',     'activo' => true],
            ['codigo' => 'CLIENTES',    'nombre' => 'Clientes',    'activo' => true],
            ['codigo' => 'SEGUIMIENTO', 'nombre' => 'Seguimiento', 'activo' => true],
        ]);

        DB::table('tipos_documento')->insert([
            ['codigo' => 'FACTURA_A',       'nombre' => 'Factura A',             'es_fiscal' => true,  'activo' => true],
            ['codigo' => 'FACTURA_B',       'nombre' => 'Factura B',             'es_fiscal' => true,  'activo' => true],
            ['codigo' => 'NOTA_CREDITO',    'nombre' => 'Nota de crédito',       'es_fiscal' => true,  'activo' => true],
            ['codigo' => 'REMITO',          'nombre' => 'Remito',                'es_fiscal' => true,  'activo' => true],
            ['codigo' => 'ORDEN_RETIRO',    'nombre' => 'Orden de retiro',       'es_fiscal' => false, 'activo' => true],
            ['codigo' => 'COMPROBANTE',     'nombre' => 'Comprobante de entrega','es_fiscal' => false, 'activo' => true],
            ['codigo' => 'ETIQUETA_TRANSOFT','nombre' => 'Etiqueta Transoft',    'es_fiscal' => false, 'activo' => true],
            ['codigo' => 'REMITO_TRANSOFT', 'nombre' => 'Remito Transoft',       'es_fiscal' => false, 'activo' => true],
        ]);

        DB::table('origenes_cotizacion')->insert([
            ['codigo' => 'WEB_PUBLICA',   'nombre' => 'Cotizador público',  'requiere_login' => false, 'activo' => true],
            ['codigo' => 'PANEL_CLIENTE', 'nombre' => 'Panel del cliente',  'requiere_login' => true,  'activo' => true],
            ['codigo' => 'BACKOFFICE',    'nombre' => 'Backoffice',         'requiere_login' => true,  'activo' => true],
            ['codigo' => 'API',           'nombre' => 'API / integración',  'requiere_login' => true,  'activo' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('origenes_cotizacion');
        Schema::dropIfExists('tipos_documento');
        Schema::dropIfExists('tipos_importacion');
        Schema::dropIfExists('tipos_integracion');
        Schema::dropIfExists('tipos_condicion_comercial');
        Schema::dropIfExists('unidades_medida');
        Schema::dropIfExists('tipos_servicio');
        Schema::dropIfExists('tipos_bulto');
        Schema::dropIfExists('tipos_cliente');
        Schema::dropIfExists('roles');
    }
};
