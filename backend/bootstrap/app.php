<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth'  => \App\Http\Middleware\AdminAuth::class,
            'admin.only'  => \App\Http\Middleware\AdminOnly::class,
            'admin.rbac'  => \App\Http\Middleware\AdminPermission::class,
            'installed'   => \App\Http\Middleware\CheckInstalled::class,
            'log.access'  => \App\Http\Middleware\LogAccess::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'heimdall/import-export/upload',
            'heimdall/import-export/confirm',
            'import-export/upload',
            'import-export/confirm',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,  // Headers HTTP de segurança (X-Frame, HSTS, CSP...)
            \App\Http\Middleware\BlockBannedIps::class,   // Bloqueia IPs banidos
            \App\Http\Middleware\LogAccess::class,        // Logs automáticos de navegação
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
