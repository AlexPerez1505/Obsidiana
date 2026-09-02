<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'approved' => \App\Http\Middleware\EnsureAccountApproved::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Cuando el archivo pasa el limite de PHP, el servidor descarta el
        // envio completo antes de que llegue a la validacion. Sin esto el
        // usuario veria un 413 en crudo, o peor, un "falta el archivo" que
        // no explica nada.
        $exceptions->render(function (PostTooLargeException $e, $request) {
            $limite = ini_get('upload_max_filesize');

            return back()
                ->withInput($request->except('archivo'))
                ->withErrors([
                    'archivo' => "El archivo pasa del límite del servidor ({$limite}). Súbelo comprimido o pide que se aumente ese límite.",
                ]);
        });

        // Manda cualquier error no controlado a Sentry (si SENTRY_LARAVEL_DSN
        // está configurado en .env; si no, esto no hace nada).
        Integration::handles($exceptions);
    })->create();
