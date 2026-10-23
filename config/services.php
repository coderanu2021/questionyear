<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => preg_replace('#^models/#', '', trim((string) env('GEMINI_MODEL', 'gemini-3.5-flash-lite'))),
    ],

    'job_notices' => [
        'auto_publish' => (bool) env('JOB_NOTICES_AUTO_PUBLISH', false),
        'minimum_confidence' => (float) env('JOB_NOTICES_MIN_CONFIDENCE', 0.95),
    ],
    'job_alerts' => [
        'telegram_bot_token' => env('JOB_ALERT_TELEGRAM_BOT_TOKEN'),
        'telegram_chat_id' => env('JOB_ALERT_TELEGRAM_CHAT_ID'),
        'email_recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('JOB_ALERT_EMAIL_RECIPIENTS', ''))))),
        'vapid_subject' => env('JOB_PUSH_VAPID_SUBJECT', 'mailto:admin@example.com'),
        'vapid_public_key' => env('JOB_PUSH_VAPID_PUBLIC_KEY'),
        'vapid_private_key' => str_replace('\\n', "\n", (string) env('JOB_PUSH_VAPID_PRIVATE_KEY', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

];
