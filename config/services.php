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
        // Single source of truth for "is online card payment live" — both the
        // dashboard button and the payment routes check this, so a missing
        // key/secret always falls back to "coming soon" rather than a broken form.
        'enabled'         => (bool) (env('STRIPE_KEY') && env('STRIPE_SECRET')),
    ],

    // ── SMS provider switching ──────────────────────────────────
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'vonage'),
    ],

    // ── Vonage (SMS) ───────────────────────────────────────────
    'vonage' => [
        'key'      => env('VONAGE_API_KEY'),
        'secret'   => env('VONAGE_API_SECRET'),
        'sms_from' => env('VONAGE_SMS_FROM', 'ACMPortal'),
    ],

    // ── Twilio (SMS fallback) ───────────────────────────────────
    'twilio' => [
        'sid'   => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from'  => env('TWILIO_FROM'),
    ],

    // ── What3Words ─────────────────────────────────────────────
    'what3words' => [
        'key' => env('WHAT3WORDS_API_KEY'),
    ],

    // ── Google Maps (venue picker: Places Autocomplete + Maps JS) ──
    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

];
