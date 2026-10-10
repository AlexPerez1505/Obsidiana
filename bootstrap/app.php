<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Sentry\Laravel\Integration;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Antes de abrir sesión: por IP siempre https (ver el middleware).
        $middleware->web(prepend: \App\Http\Middleware\ForzarHttpsPorIp::class);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'approved' => \App\Http\Middleware\EnsureAccountApproved::class,
            'dispositivo' => \App\Http\Middleware\AutenticarDispositivo::class,
            'api.cuenta' => \App\Http\Middleware\CuentaActivaApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // En /api todo error sale como JSON en español (ver la clase).
        // Va primero: el de abajo regresa a la pantalla anterior, que en
        // una API no existe.
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(fn (\Throwable $e, $request) => \App\Support\ErroresApi::render($e, $request));

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

// Si el .env no se alcanza a leer en una petición, se reintenta (ver la clase).
\App\Support\RespaldoDeEnv::registrar($app);

return $app;
