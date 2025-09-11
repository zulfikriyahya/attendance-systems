<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'whatsapp' => [
        'endpoints' => [
            'api1' => env('WHATSAPP_API_URL_1'),
            'api2' => env('WHATSAPP_API_URL_2'),
            'api3' => env('WHATSAPP_API_URL_3'),
            'api4' => env('WHATSAPP_API_URL_4'),
            'api5' => env('WHATSAPP_API_URL_5'),
            'api6' => env('WHATSAPP_API_URL_6'),
            'api7' => env('WHATSAPP_API_URL_7'),
            'api8' => env('WHATSAPP_API_URL_8'),
            'api9' => env('WHATSAPP_API_URL_9'),
            'api10' => env('WHATSAPP_API_URL_10'),
        ],
        'timeout' => 15,
        'connect_timeout' => 5,
        'retry_timeout' => 5, // minutes before retry down endpoint
    ],
    
    'api' => [
        'secret' => env('API_SECRET'),
    ],
];
