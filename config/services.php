<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

<<<<<<< Updated upstream
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v20.0'),
        'default_template' => env('WHATSAPP_TEMPLATE_PROMO', 'promo_generica'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'es_MX'),
=======
    /*
    |--------------------------------------------------------------------------
    | WhatsApp (promociones)
    |--------------------------------------------------------------------------
    |
    | "driver" decide qué implementación de App\Contracts\WhatsAppSender se
    | usa (ver App\Providers\WhatsAppServiceProvider). Por ahora solo existe
    | "log" (no manda nada de verdad, solo lo registra). Cuando se conecte
    | una cuenta real, se agrega su driver aquí y sus credenciales abajo.
    */
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'log'),

        // Credenciales para cuando se conecte una cuenta real (Meta Cloud
        // API, Twilio, 360dialog...). Se dejan aquí ya listas para no tener
        // que tocar el flujo de promociones el día que se configuren.
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'plantilla_confirmacion' => env('WHATSAPP_PLANTILLA_CONFIRMACION'),
>>>>>>> Stashed changes
    ],

];
