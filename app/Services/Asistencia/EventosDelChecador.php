<?php

namespace App\Services\Asistencia;

use Illuminate\Support\Facades\Cache;

/**
 * Cola corta de eventos para la pantalla del checador: lo que el lector
 * acaba de leer (checada, huella no reconocida, registro en curso). La
 * pantalla la consulta cada segundo con el id del último evento que vio.
 * Es solo para mostrar: la verdad está en attendance_punches.
 */
class EventosDelChecador
{
    private const MAX = 30;

    private static function clave(int $dispositivo): string
    {
        return "checador:eventos:{$dispositivo}";
    }

    public static function publicar(int $dispositivo, string $tipo, array $datos = []): void
    {
        $cola = Cache::get(self::clave($dispositivo), ['ultimo' => 0, 'eventos' => []]);

        $cola['ultimo']++;
        $cola['eventos'][] = ['id' => $cola['ultimo'], 'tipo' => $tipo, 'at' => now()->timestamp] + $datos;
        $cola['eventos'] = array_slice($cola['eventos'], -self::MAX);

        Cache::put(self::clave($dispositivo), $cola, now()->addHours(6));
    }

    /** @return array{ultimo: int, eventos: array<int, array>} */
    public static function desde(int $dispositivo, int $despuesDe): array
    {
        $cola = Cache::get(self::clave($dispositivo), ['ultimo' => 0, 'eventos' => []]);

        // Si la caché se limpió y el contador regresó a cero, la pantalla se reajusta sola
        if ($despuesDe > $cola['ultimo']) {
            $despuesDe = 0;
        }

        return [
            'ultimo' => $cola['ultimo'],
            // Solo eventos recientes: no se reproduce lo que pasó hace minutos
            'eventos' => array_values(array_filter($cola['eventos'], fn ($e) => $e['id'] > $despuesDe && $e['at'] >= now()->timestamp - 30)),
        ];
    }
}
