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

    'ai_questions' => [
        'key' => env('AI_QUESTION_API_KEY'),
        'url' => env('AI_QUESTION_API_URL', 'http://localhost:20128/v1/messages'),
        'model' => env('AI_QUESTION_MODEL', 'gh/gpt-4o-mini'),
        'adapter' => env('AI_QUESTION_API_ADAPTER', 'anthropic_messages'),
        'timeout' => env('AI_QUESTION_TIMEOUT', 45),
    ],

    'libreoffice' => [
        'path' => env('LIBREOFFICE_PATH'),
    ],

    'vnpay' => [
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'payment_url' => env('VNPAY_PAYMENT_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'version' => env('VNPAY_VERSION', '2.1.0'),
    ],

];
