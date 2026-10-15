<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seguimiento de cotizaciones por WhatsApp
    |--------------------------------------------------------------------------
    |
    | Modo "wa.me": el sistema prepara el enlace con el mensaje y el usuario
    | lo revisa y lo envía manualmente desde WhatsApp. NO es un envío automático.
    |
    | Para migrar más adelante a WhatsApp Business Cloud API, agregar un nuevo
    | "driver" en App\Services\WhatsApp\SeguimientoCotizacionService y definir
    | las credenciales en el .env (ver claves de 'cloud_api' más abajo).
    |
    */

    'driver' => env('WHATSAPP_DRIVER', 'wa_me'),

    // Prefijo de país por defecto para números sin código internacional.
    'pais_por_defecto' => env('WHATSAPP_PAIS', '54'),

    // Códigos de estado de cotización (estados_cotizacion.codigo) que permiten seguimiento.
    // Los estados finales (ACEPTADA, RECHAZADA, VENCIDA) nunca se contactan.
    'estados_seguimiento' => ['PENDIENTE_CONFIRMACION', 'ENVIADA', 'EN_REVISION'],

    // Horas mínimas entre dos contactos de la misma cotización (anti-duplicados).
    'horas_entre_contactos' => (int) env('WHATSAPP_HORAS_ENTRE_CONTACTOS', 24),

    // Plantilla del mensaje. Placeholders: {nombre} {codigo} {importe} {enlace}
    'mensaje_seguimiento' => env(
        'WHATSAPP_MENSAJE_SEGUIMIENTO',
        '¡Hola, {nombre}! Te contactamos para consultar si pudiste revisar la cotización {codigo} por {importe}. '
        .'Podés verla acá: {enlace}. Si tenés alguna duda o querés avanzar con el pedido, estamos a tu disposición. ¡Muchas gracias!'
    ),

    // Reservado para una futura integración oficial (no se usa en modo wa_me).
    'cloud_api' => [
        'token' => env('WHATSAPP_CLOUD_TOKEN'),
        'phone_number_id' => env('WHATSAPP_CLOUD_PHONE_NUMBER_ID'),
        'template' => env('WHATSAPP_CLOUD_TEMPLATE'),
    ],
];
