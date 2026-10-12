<?php

namespace App\Support;

use Dotenv\Dotenv;
use Illuminate\Foundation\Application;
use Illuminate\Support\Env;

/**
 * Respaldo contra una falla intermitente: de vez en cuando una petición llega
 * a Apache y Laravel no alcanza a leer el .env en ese instante, así que arranca
 * como "production" y sin APP_KEY (error 500 "No application encryption key").
 * Un instante después, la misma página funciona.
 *
 * Si al cargar el entorno falta APP_KEY y el .env existe, se reintenta la
 * lectura unas cuantas veces. Cada vez que pasa queda una línea en
 * storage/logs/env-diagnostico.log para saber si fue algo momentáneo (se
 * recuperó) o no, y por qué.
 */
class RespaldoDeEnv
{
    private const INTENTOS = 8;
    private const ESPERA_US = 75_000;   // 75 ms entre intentos: 0.6 s en el peor caso

    public static function registrar(Application $app): void
    {
        $app->afterLoadingEnvironment(function (Application $app) {
            // Con la configuración en caché el .env no se lee: no hay nada que reintentar
            if ($app->configurationIsCached() || ! empty(Env::get('APP_KEY'))) {
                return;
            }

            $archivo = $app->environmentFilePath();
            $error = null;

            for ($i = 1; $i <= self::INTENTOS; $i++) {
                usleep(self::ESPERA_US);

                try {
                    Dotenv::create(Env::getRepository(), $app->environmentPath(), $app->environmentFile())->safeLoad();
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }

                if (! empty(Env::get('APP_KEY'))) {
                    self::anotar($app, "RECUPERADO en el intento {$i}", $archivo, $error);

                    return;
                }
            }

            self::anotar($app, 'NO SE PUDO leer APP_KEY tras '.self::INTENTOS.' intentos', $archivo, $error);
        });
    }

    private static function anotar(Application $app, string $resultado, string $archivo, ?string $error): void
    {
        $linea = sprintf(
            "[%s] %s · %s %s · .env existe=%s legible=%s tamaño=%s · php=%s %s · %s%s\n",
            date('Y-m-d H:i:s'),
            $resultado,
            $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            $_SERVER['REQUEST_URI'] ?? '',
            file_exists($archivo) ? 'sí' : 'NO',
            is_readable($archivo) ? 'sí' : 'NO',
            @filesize($archivo) === false ? '?' : @filesize($archivo),
            PHP_VERSION,
            PHP_SAPI,
            $error ? "error: {$error} · " : '',
            ($e = error_get_last()) ? 'último error PHP: '.($e['message'] ?? '') : ''
        );

        @file_put_contents($app->storagePath('logs/env-diagnostico.log'), $linea, FILE_APPEND | LOCK_EX);
    }
}
