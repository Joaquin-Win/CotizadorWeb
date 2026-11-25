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
        '¡Hola, {nombre}! Te contactamos para hacer seguimiento de la cotización {codigo} por {importe}. '
        .'Si tenés alguna duda o querés avanzar con el pedido, estamos a tu disposición. ¡Muchas gracias!'
    ),

    // Bot de WhatsApp con IA: YCloud (coexistencia) + Gemini. Ver App\Jobs\ResponderWhatsAppBotJob.
    'bot' => [
        'ycloud_api_key' => env('YCLOUD_API_KEY'),
        'ycloud_webhook_secret' => env('YCLOUD_WEBHOOK_SECRET'), // secreto whsec_... del endpoint, valida los webhooks
        'numero' => env('WHATSAPP_BOT_NUMERO'), // número del bot en E.164, ej. +5493512340859
        'gemini_api_key' => env('GEMINI_API_KEY'),
        'gemini_modelo' => env('GEMINI_MODELO', 'gemini-2.5-flash'),
        // Horas que el bot queda callado en una conversación después de derivar o de que una persona escriba desde la app.
        'horas_modo_humano' => (int) env('WHATSAPP_BOT_HORAS_HUMANO', 24),
        'mensaje_error' => 'Lamentamos el inconveniente técnico. Una persona de nuestro equipo te va a responder por este mismo chat, o escribinos a comercial@setlogistica.com',
    ],

    // Reservado para una futura integración oficial (no se usa en modo wa_me).
    'cloud_api' => [
        'token' => env('WHATSAPP_CLOUD_TOKEN'),
        'phone_number_id' => env('WHATSAPP_CLOUD_PHONE_NUMBER_ID'),
        'template' => env('WHATSAPP_CLOUD_TEMPLATE'),
    ],
];
