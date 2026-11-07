<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

/**
 * Tiempo real en todas las pantallas.
 *
 * Un contador global ("revisión") que sube cada vez que alguien cambia algo:
 * lo sube el middleware ObsVivoTocar (cualquier POST/PUT/PATCH/DELETE que
 * salió bien, de la web o de la API) y los eventos de Eloquent de cualquier
 * modelo cuando el cambio no viene de una petición (comandos, tareas
 * programadas).
 *
 * Cada pantalla consulta /_vivo cada pocos segundos; si la revisión cambió,
 * se actualiza sola en su lugar, sin recargar (partials/vivo).
 *
 * Varios cambios en la misma petición suben el contador una sola vez, al
 * final. Vive en la caché de archivos para que la consulta no toque la base.
 */
class ObsVivo
{
    private const CLAVE = 'obs-vivo:rev';

    /**
     * Peticiones que guardan algo pero no cambian lo que otros ven: subir
     * fotos o video por pedazos (cada pedazo es un POST), entrar y salir.
     */
    public const IGNORAR = [
        '*foto-chunk', '*video-chunk', '_vivo',
        'login', 'logout', 'register', 'verificar*', 'email/*', 'recuperar-contrasena*', 'restablecer-contrasena*',
        // Cálculos que no guardan nada (oferta pública y cupón del cotizador).
        'p/*/calcular', 'promociones/cupones/comprobar', 'promociones/campanas/audiencia',
    ];

    private static bool $pendiente = false;

    /** Marca que hubo un cambio; el contador sube una vez al terminar la petición. */
    public static function tocar(): void
    {
        if (self::$pendiente) {
            return;
        }

        self::$pendiente = true;

        app()->terminating(function () {
            self::$pendiente = false;
            self::subir();
        });
    }

    public static function subir(): int
    {
        try {
            $cache = Cache::store('file');

            if (! $cache->has(self::CLAVE)) {
                $cache->forever(self::CLAVE, (int) (microtime(true) * 10) % 100000000);
            }

            return (int) $cache->increment(self::CLAVE);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function rev(): int
    {
        try {
            return (int) Cache::store('file')->get(self::CLAVE, 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Cambios que no pasan por una petición web (comandos, tareas
     * programadas). Los de las peticiones ya los avisa el middleware; en
     * una petición GET no se avisa, para que guardar cosas al solo ver una
     * pantalla no tenga a todos actualizando sin parar.
     */
    public static function escuchar(): void
    {
        $avisar = function () {
            if (app()->runningInConsole()) {
                self::tocar();
            }
        };

        Event::listen('eloquent.saved: *', $avisar);
        Event::listen('eloquent.deleted: *', $avisar);
    }
}
