<?php

return [
    'connection' => env('WP_DB_CONNECTION_NAME', 'wordpress'),
    'table_prefix' => env('WP_TABLE_PREFIX', 'wp_'),
    'member_role' => env('WP_MEMBER_ROLE', 'smsa_socio'),
    'expose_plain_password' => env('WP_EXPOSE_PLAIN_PASSWORD', false),
    'local_fallback_email_domain' => env('WP_LOCAL_FALLBACK_EMAIL_DOMAIN', 'smsa.local.test'),
    'member_area_shared_secret' => env('WP_MEMBER_AREA_SHARED_SECRET', env('WP_LARAVEL_BRIDGE_TOKEN', '')),
    'member_area_max_drift_seconds' => (int) env('WP_MEMBER_AREA_MAX_DRIFT_SECONDS', 300),
];
