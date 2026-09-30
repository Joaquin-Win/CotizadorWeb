<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versión del algoritmo de cotización
    |--------------------------------------------------------------------------
    | Este valor se guarda como snapshot en cada cotización.
    | También se almacena en la tabla configuracion_cotizador para visualización
    | en el panel admin. No cambiar este valor sin actualizar la BD.
    */
    'algorithm_version' => env('COTIZADOR_ALGORITHM_VERSION', 'v2.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Defaults de configuración (fallback si la BD no responde)
    |--------------------------------------------------------------------------
    | Estos valores son de seguridad. La fuente de verdad es la tabla
    | configuracion_cotizador, editable desde el panel admin.
    */
    'defaults' => [
        'seguro_porcentaje' => 0.80,  // % del valor declarado
        'iva_porcentaje'    => 0.00,  // Actualmente 0: tarifas son precio final
    ],

];
