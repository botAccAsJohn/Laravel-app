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

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
    ],
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'webhook' => [
        'url' => env('WEBHOOK_NOTIFICATION_URL'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL', '#general'),
        ],
        // Incoming Webhooks (each locked to a specific channel in Slack)
        'webhook_alert_url' => env('SLACK_WEBHOOK_ALERT_URL'),
        'webhook_order_url' => env('SLACK_WEBHOOK_ORDER_URL'),
        // Channel names (used by Bot Token for dynamic routing)
        'channels' => [
            'orders'  => env('SLACK_CHANNEL_ORDERS', '#orders'),
            'alerts'  => env('SLACK_CHANNEL_ALERTS', '#alerts'),
            'errors'  => env('SLACK_CHANNEL_ERRORS', '#errors'),
            'support' => env('SLACK_CHANNEL_SUPPORT', '#support'),
        ],
        'signing_secret' => env('SLACK_SIGNING_SECRET'),
    ],

    'external_api' => [
        'base_url' => env('EXTERNAL_API_BASE_URL'),
        'token' => env('EXTERNAL_API_TOKEN'),
        'timeout' => env('EXTERNAL_API_TIMEOUT', 30),
        'retry' => env('EXTERNAL_API_RETRY', 3),
    ],

];
