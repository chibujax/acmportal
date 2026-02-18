<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs that should be excluded from CSRF verification.
     * Stripe and Paystack webhooks use their own signature verification.
     */
    protected $except = [
        'webhooks/stripe',
        'webhooks/paystack',
    ];
}
