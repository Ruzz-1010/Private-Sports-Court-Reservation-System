<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment gateway
    |--------------------------------------------------------------------------
    |
    | The application talks to this adapter, never to a provider SDK directly.
    | Maya Pay with Maya Dynamic QRPh is the provider. It creates the QR code
    | and hosts the checkout page, so wallet credentials and card details never
    | reach this application.
    |
    | Set the sandbox keys to PAYMENT_GATEWAY=maya and switch MAYA_BASE_URL to
    | https://pg-maya.paymaya.com for live mode.
    |
    */

    'gateway' => env('PAYMENT_GATEWAY', 'maya'),

    'maya' => [
        'public_key' => env('MAYA_PUBLIC_KEY'),
        'secret_key' => env('MAYA_SECRET_KEY'),
        'base_url' => env('MAYA_BASE_URL', 'https://pg-sandbox.paymaya.com'),
        'timeout' => (int) env('MAYA_TIMEOUT', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | Publicly reachable over HTTPS, so it is rate limited to stop casual
    | abuse. Maya publishes no per-request signature for Dynamic QRPh, so the
    | rate limiter is not the security control. The handler re-verifies every
    | payment through Maya's secret-key endpoint and compares amount, currency,
    | status, and reference before any state changes.
    |
    */

    'webhook' => [
        'path' => env('MAYA_WEBHOOK_PATH', 'webhooks/maya'),
        'public_url' => env('MAYA_WEBHOOK_URL'),
        'rate_limit' => (int) env('MAYA_WEBHOOK_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Receipts
    |--------------------------------------------------------------------------
    */

    'receipt_prefix' => env('RECEIPT_PREFIX', 'RCP'),

];
