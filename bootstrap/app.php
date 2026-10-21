<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway terminates HTTPS at its proxy. Trust forwarded headers so
        // Laravel generates secure URLs and cookies for the original request.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'guest' => RedirectIfAuthenticated::class,
        ]);

        // Guest-only routes must never be reachable by a signed-in user.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // The payment provider calls the webhook server to server with no
        // session and no CSRF token. Excluding the path does not weaken the
        // route: the handler never trusts the payload, it verifies PayMongo's signature and rechecks every
        // checkout through PayMongo's secret-key endpoint before changing state.
        // The path is read from the environment because the config repository
        // is not yet bound while this file is evaluated.
        $middleware->validateCsrfTokens(except: [
            env('PAYMONGO_WEBHOOK_PATH', 'webhooks/paymongo'),
        ]);

        $middleware->web(append: [
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
