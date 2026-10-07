<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega el estado PENDIENTE_CONFIRMACION al catálogo estados_cotizacion
 * y la clave parametros_updated_at a configuracion_cotizador.
 *
 * No toca la tabla cotizaciones (particionada) — solo inserta en catálogos.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ----------------------------------------------------------------
        // 1. Nuevo estado de cotización: PENDIENTE_CONFIRMACION
        // ----------------------------------------------------------------
        $existe = DB::table('estados_cotizacion')
            ->where('codigo', 'PENDIENTE_CONFIRMACION')
            ->exists();

        if (! $existe) {
            // El orden 7 está libre (máximo actual es 6)
            $maxOrden = DB::table('estados_cotizacion')->max('orden') ?? 6;

            DB::table('estados_cotizacion')->insert([
                'codigo'     => 'PENDIENTE_CONFIRMACION',
                'nombre'     => 'Cotización a Confirmar',
                'orden'      => $maxOrden + 1,
                'es_final'   => false,
                'activo'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ----------------------------------------------------------------
        // 2. Nueva clave de configuración: parametros_updated_at
        //    Registra cuándo se actualizaron por última vez los
        //    precios/parámetros del cotizador.
        // ----------------------------------------------------------------
        $claveExiste = DB::table('configuracion_cotizador')
            ->where('clave', 'parametros_updated_at')
            ->exists();

        if (! $claveExiste) {
            DB::table('configuracion_cotizador')->insert([
                'clave'       => 'parametros_updated_at',
                'valor'       => null,
                'tipo'        => 'STRING',
                'descripcion' => 'Timestamp de la última actualización de precios/parámetros. Usado para detectar cotizaciones que requieren reconfirmación.',
                'grupo'       => 'sistema',
                'editable'    => false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('configuracion_cotizador')
            ->where('clave', 'parametros_updated_at')
            ->delete();

        DB::table('estados_cotizacion')
            ->where('codigo', 'PENDIENTE_CONFIRMACION')
            ->delete();
    }
};
