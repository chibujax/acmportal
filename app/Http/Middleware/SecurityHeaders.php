<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Loose CSP: restricts which external origins can load/connect, without nonces —
        // 'unsafe-inline' is still needed since the app has many inline <script>/style
        // blocks. Allowlist covers Bootstrap/Icons (jsdelivr), Stripe.js + its API/fraud-
        // detection calls (*.stripe.com), and the meeting check-in QR code image (qrserver).
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://js.stripe.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "font-src 'self' https://cdn.jsdelivr.net",
            "img-src 'self' data: https://api.qrserver.com https://cdn.jsdelivr.net",
            "connect-src 'self' https://*.stripe.com https://cdn.jsdelivr.net",
            "frame-src https://*.stripe.com",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
        ]));

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
