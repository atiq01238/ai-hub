<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Apple returns its OAuth authorization response with form_post.
        $middleware->validateCsrfTokens(except: [
            'auth/social/apple/callback',
        ]);

        // Keep public GET/HEAD requests on the canonical non-www production host.
        $middleware->appendToGroup('web', \App\Http\Middleware\CanonicalDomain::class);

        // Native, privacy-conscious public visitor analytics. The middleware itself
        // skips admin/auth/private routes, bots, prefetches and DNT requests.
        $middleware->appendToGroup('web', \App\Http\Middleware\TrackVisitorAnalytics::class);

         $middleware->alias([
        'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
