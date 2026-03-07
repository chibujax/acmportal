<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PageAccessMiddleware
{
    /**
     * $pages is one or more slugs. Access is granted if the user has ANY of them.
     * Usage: middleware('page:meetings') or middleware('page:meetings,attendance')
     */
    public function handle(Request $request, Closure $next, string ...$pages)
    {
        $user = auth()->user();

        $allowed = $user && collect($pages)->contains(fn($p) => $user->hasAccess($p));

        if (! $allowed) {
            abort(403, 'You do not have access to this section.');
        }

        return $next($request);
    }
}
