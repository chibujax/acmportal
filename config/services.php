<?php

return [

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // ── Stripe ─────────────────────────────────────────────────
    'stripe' => [
        'key'             => env('STRIPE_KEY'),
        'secret'          => env('STRIPE_SECRET'),
        'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET'),
        'currency'        => env('STRIPE_CURRENCY', 'GBP'),
    ],

    // ── Vonage (SMS) ───────────────────────────────────────────
    'vonage' => [
        'key'      => env('VONAGE_API_KEY'),
        'secret'   => env('VONAGE_API_SECRET'),
        'sms_from' => env('VONAGE_SMS_FROM', 'ACMPortal'),
    ],

    // ── Paystack ───────────────────────────────────────────────
    'paystack' => [
        'public_key'      => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key'      => env('PAYSTACK_SECRET_KEY'),
        'payment_url'     => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
        'merchant_email'  => env('PAYSTACK_MERCHANT_EMAIL'),
    ],

];
