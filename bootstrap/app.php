<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
            'webhooks/paystack',
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
            if (! config('app.debug')) {
                return response()->view('errors.500', [], 500);
            }
        });
    })->create();
