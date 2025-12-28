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

    'payment' => [
        'provider' => env('PAYMENT_PROVIDER', 'direct'), // 'direct', 'fincra', 'flutterwave', 'paystack'
        'api_key' => env('PAYMENT_API_KEY'),
        'api_secret' => env('PAYMENT_API_SECRET'),
        'merchant_id' => env('PAYMENT_MERCHANT_ID'),
        'base_url' => env('PAYMENT_BASE_URL', 'https://api.fincra.com'),
        'verify_ssl' => env('PAYMENT_VERIFY_SSL'), // null = auto-detect (disabled in local/dev), true = always verify, false = never verify
    ],

    'momo' => [
        // Direct Mobile Money API credentials (optional - for automatic verification)
        'mtn_api_key' => env('MTN_API_KEY'),
        'mtn_api_secret' => env('MTN_API_SECRET'),
        'mtn_merchant_id' => env('MTN_MERCHANT_ID'),
        'vodafone_api_key' => env('VODAFONE_API_KEY'),
        'vodafone_api_secret' => env('VODAFONE_API_SECRET'),
        'airteltigo_api_key' => env('AIRTELTIGO_API_KEY'),
        'airteltigo_api_secret' => env('AIRTELTIGO_API_SECRET'),
    ],

];
