<?php

return [
    'gateway' => env('PAYMENT_GATEWAY', 'paymongo'),
    // Presentation-only confirmation helper. Keep false for real payment runs.
    'simulation_enabled' => filter_var(env('PAYMENT_SIMULATION_ENABLED', false), FILTER_VALIDATE_BOOL),

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com'),
        'timeout' => (int) env('PAYMONGO_TIMEOUT', 15),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
    ],

    'webhook' => [
        'path' => env('PAYMONGO_WEBHOOK_PATH', 'webhooks/paymongo'),
        'public_url' => env('PAYMONGO_WEBHOOK_URL'),
        'rate_limit' => (int) env('PAYMONGO_WEBHOOK_RATE_LIMIT', 60),
    ],

    'receipts' => [
        'prefix' => env('RECEIPT_PREFIX', 'RCP'),
    ],
];
