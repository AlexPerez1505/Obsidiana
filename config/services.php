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

    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v20.0'),
        'default_template' => env('WHATSAPP_TEMPLATE_PROMO', 'promo_generica'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'es_MX'),
    ],

    // Checklist de recepción armado por IA (OpenAI) según el equipo, igual
    // que en empresajureto. Sin OPENAI_API_KEY se usa el checklist general.
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-5.4'),
        'reasoning_effort' => env('OPENAI_REASONING_EFFORT', 'low'),
        // Corrector ortográfico global: rápido (none/low/medium…).
        'ortografia_effort' => env('OPENAI_ORTOGRAFIA_EFFORT', 'none'),
    ],

];