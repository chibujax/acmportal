<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration as SentryIntegration;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\AppServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin'      => \App\Http\Middleware\AdminMiddleware::class,
            'superadmin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'page'       => \App\Http\Middleware\PageAccessMiddleware::class,
        ]);
        $middleware->prepend(\App\Http\Middleware\ForceHttps::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        // No trustProxies() — on a standard cPanel setup, Apache/LiteSpeed terminates
        // SSL directly with no separate reverse-proxy hop, so $request->secure() already
        // reflects the real connection. Trusting proxy headers with nothing real in front
        // would just let a visitor spoof their IP via X-Forwarded-For. Revisit this if a
        // load balancer or CDN (e.g. Cloudflare) ever gets put in front of the app.
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Render HTTP exceptions (404, 403, etc.) with our custom views
        $exceptions->render(function (HttpException $e, Request $request) {
            $code = $e->getStatusCode();
            $view = "errors.{$code}";
            if (view()->exists($view)) {
                return response()->view($view, [], $code);
            }
        });

        // Render any other unexpected exception as a 500 page (only when APP_DEBUG=false)
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! config('app.debug') && ! $e instanceof ValidationException && ! $e instanceof AuthenticationException) {
                return response()->view('errors.500', [], 500);
            }
        });

        // Suppress routine, expected exceptions before Sentry (registered below) ever sees
        // them — returning false here stops the report chain entirely, so this MUST be
        // registered before SentryIntegration::handles(). Failed login attempts throw
        // ValidationException (see LoginController::login()) and are not worth alerting on;
        // 404s/expired sessions are routine bot/browser noise, not application errors.
        $exceptions->reportable(function (Throwable $e) {
            if ($e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof NotFoundHttpException
                || $e instanceof TokenMismatchException) {
                return false;
            }
        });

        SentryIntegration::handles($exceptions);
    })->create();
