<?php

/*
|--------------------------------------------------------------------------
| Marketing y promociones
|--------------------------------------------------------------------------
| Todo lo sensible (tokens, secretos) vive en el .env y nunca se escribe en
| la base ni en los logs. Ver la pantalla Promociones → Configuración para
| saber qué falta.
*/
return [

    'whatsapp' => [
        // log  = no manda nada: registra el envío como "simulado" (desarrollo).
        // meta = API oficial de WhatsApp Business (Cloud API de Meta).
        'driver' => env('WHATSAPP_DRIVER', 'log'),

        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v26.0'),

        // Webhook: el token que se escribe en Meta al suscribirse y el
        // secreto de la app con el que Meta firma cada aviso.
        'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),

        // Plantilla aprobada (categoría Marketing). Ver la guía en Configuración.
        'plantilla' => env('WHATSAPP_PLANTILLA_PROMO', 'promo_cupon'),
        'idioma' => env('WHATSAPP_PLANTILLA_IDIOMA', 'es_MX'),
        'plantilla_con_imagen' => (bool) env('WHATSAPP_PLANTILLA_CON_IMAGEN', true),
        'plantilla_boton_baja' => (bool) env('WHATSAPP_PLANTILLA_BOTON_BAJA', true),

        // El texto del cuerpo TAL COMO se aprobó en Meta, solo para la vista
        // previa del panel. Variables: {{1}} nombre, {{2}} oferta, {{3}} código, {{4}} vence.
        'plantilla_texto' => env('WHATSAPP_PLANTILLA_TEXTO', 'Hola {{1}}, tenemos algo para ti: {{2}} Tu código es {{3}} y es válido hasta el {{4}}. Toca el botón para ver la oferta.'),
        'plantilla_boton_texto' => env('WHATSAPP_PLANTILLA_BOTON_TEXTO', 'Ver oferta'),

        'timeout' => 15,
    ],

    // Números a los que se permite mandar pruebas (E.164 sin "+", separados por coma).
    'numeros_prueba' => array_values(array_filter(array_map('trim', explode(',', (string) env('PROMO_NUMEROS_PRUEBA', ''))))),

    // Mientras sea true, una campaña general SOLO llega de verdad a los
    // números de prueba; al resto se le marca "simulado". Apagar hasta que
    // la plantilla esté aprobada y la campaña revisada.
    'solo_numeros_prueba' => (bool) env('PROMO_SOLO_NUMEROS_PRUEBA', true),

    // Control de frecuencia: un cliente no recibe otra promoción antes de estas horas.
    'frecuencia_horas' => (int) env('PROMO_FRECUENCIA_HORAS', 72),

    // Ritmo de envío de la cola.
    'mensajes_por_minuto' => (int) env('PROMO_MENSAJES_POR_MINUTO', 60),

    // Cuánto se aparta un cupón cuando entra en una cotización (sin venta todavía).
    'reserva_horas' => (int) env('PROMO_RESERVA_HORAS', 168),

    // El pedido que el cliente arma desde su enlace.
    'oferta' => [
        'aplica_iva' => (bool) env('PROMO_OFERTA_APLICA_IVA', false),
        'max_cantidad' => 20,
    ],
];
