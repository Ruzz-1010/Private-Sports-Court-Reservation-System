<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment gateway
    |--------------------------------------------------------------------------
    |
    | The application talks to this adapter, never to a provider SDK directly.
    | Switching between the real gateway and a local simulator is a config
    | change, which is what makes the payment flow testable and demoable
    | without live credentials.
    |
    | gateway: "paymongo" for the real hosted checkout, "fake" for the local
    | simulator used in development and during presentations.
    |
    */

    'gateway' => env('PAYMENT_GATEWAY', 'fake'),

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY'),
        'public_key' => env('PAYMONGO_PUBLIC_KEY'),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),
        'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
        'timeout' => (int) env('PAYMONGO_TIMEOUT', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | Endpoints are rate limited because they are publicly reachable. The
    | limiter stops casual abuse; signature verification is what actually
    | authenticates a callback.
    |
    */

    'webhook' => [
        'path' => env('PAYMONGO_WEBHOOK_PATH', 'webhooks/paymongo'),
        'signature_header' => 'Paymongo-Signature',
        'rate_limit' => (int) env('PAYMONGO_WEBHOOK_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Receipts
    |--------------------------------------------------------------------------
    */

    'receipt_prefix' => env('RECEIPT_PREFIX', 'RCP'),

];
