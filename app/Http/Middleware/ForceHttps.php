<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttps
{
    /**
     * Redirect any plain-HTTP request to HTTPS in production. Left alone in
     * local/dev so `php artisan serve` over http:// still works normally.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->secure() && app()->environment('production')) {
            return redirect()->to('https://' . $request->getHttpHost() . $request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
