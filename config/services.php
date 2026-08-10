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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'openai' => [
        'key' => env('GROQ_API_KEY', env('OPENAI_API_KEY')),
        'model' => env('GROQ_MODEL', env('OPENAI_MODEL', 'llama-3.1-8b-instant')),
        'models' => array_values(array_filter(array_map('trim', explode(',', env(
            'GROQ_MODELS',
            env('OPENAI_MODELS', env('GROQ_MODEL', env('OPENAI_MODEL', 'llama-3.1-8b-instant')))
        ))))),
        'base_url' => env('GROQ_BASE_URL', env('OPENAI_BASE_URL', 'https://api.groq.com/openai/v1')),
    ],

    'app_bhp' => [
        'token' => env('APP_BHP_API_TOKEN'),
    ],

    'sumselprov' => [
        'sync_cron' => env('SUMSELPROV_SYNC_CRON', '0 * * * *'),
        'max_pages' => (int) env('SUMSELPROV_SYNC_MAX_PAGES', 5),
        'api_endpoints' => array_values(array_filter(array_map('trim', explode(',', env(
            'SUMSELPROV_API_ENDPOINTS',
            'api_berita_sumsel3,api_berita_all2'
        ))))),
    ],

    'rilis' => [
        'image_convert_webp' => (bool) env('RILIS_IMAGE_CONVERT_WEBP', true),
        'image_storage_disk' => env('RILIS_IMAGE_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
        'image_webp_quality' => (int) env('RILIS_IMAGE_WEBP_QUALITY', 82),
        'image_max_width' => (int) env('RILIS_IMAGE_MAX_WIDTH', 1600),
    ],

    'kliping' => [
        'storage_disk' => env('KLIPING_STORAGE_DISK', 'local'),
    ],

    'dokumentasi' => [
        'storage_disk' => env('DOKUMENTASI_STORAGE_DISK', 'local'),
    ],

    'instagram_upload' => [
        'unlimited_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('INSTAGRAM_UPLOAD_UNLIMITED_IPS', '127.0.0.1,::1'))
        ))),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
