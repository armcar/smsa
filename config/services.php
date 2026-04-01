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

    'wp_bridge' => [
        // Dedicated secrets (preferred)
        'ingest_shared_secret' => env('SMSA_INGEST_SHARED_SECRET', env('WP_LARAVEL_BRIDGE_TOKEN', '')),
        'internal_callback_secret' => env('SMSA_INTERNAL_CALLBACK_SECRET', env('SMSA_INGEST_SHARED_SECRET', env('WP_LARAVEL_BRIDGE_TOKEN', ''))),

        // Backward compatibility (legacy single token)
        'token' => env('WP_LARAVEL_BRIDGE_TOKEN', ''),

        // Optional previous secrets (comma-separated) for seamless rotation.
        'ingest_previous_shared_secrets' => array_values(array_filter(array_map(
            static fn (string $secret): string => trim($secret),
            explode(',', (string) env('SMSA_INGEST_PREVIOUS_SHARED_SECRETS', ''))
        ))),
        'internal_callback_previous_shared_secrets' => array_values(array_filter(array_map(
            static fn (string $secret): string => trim($secret),
            explode(',', (string) env('SMSA_INTERNAL_CALLBACK_PREVIOUS_SHARED_SECRETS', ''))
        ))),

        'verify_ssl' => env('WP_BRIDGE_VERIFY_SSL', true),
        'enforce_ssl_verify_in_production' => env('WP_BRIDGE_ENFORCE_SSL_VERIFY_IN_PROD', true),
        'allowed_callback_hosts' => array_values(array_filter(array_map(
            static fn (string $host): string => trim($host),
            explode(',', (string) env('WP_BRIDGE_ALLOWED_CALLBACK_HOSTS', ''))
        ))),
        'allowed_ingest_hosts' => array_values(array_filter(array_map(
            static fn (string $host): string => trim($host),
            explode(',', (string) env('WP_BRIDGE_ALLOWED_INGEST_HOSTS', ''))
        ))),
    ],

];
