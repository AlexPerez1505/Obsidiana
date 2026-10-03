<?php

namespace App\Services\Logistica;

use App\Models\Ruta;
use App\Models\RutaParada;
use App\Support\Geo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Decide en qué orden visitar las paradas y calcula distancias, tiempos
 * y el trazo en el mapa.
 *
 * Con llave de Google usa Routes API (tráfico real y orden óptimo cuando la
 * ruta regresa a la bodega). Sin llave, o si Google falla, calcula con
 * distancias en línea recta ajustadas a carretera: vecino más cercano
 * mejorado con 2-opt. Las paradas ya resueltas no se mueven, y si se pasa la
 * posición actual solo se reordenan las pendientes (reoptimización).
 */
class OptimizadorDeRutas
{
    /** Minutos que se supone dura cada entrega, para estimar las ETA. */
    private const MIN_POR_PARADA = 15;

    /** Máximo de paradas intermedias que acepta Google en una sola consulta. */
    private const MAX_GOOGLE = 25;

    /**
     * @param  bool  $reordenar  false = solo recalcula tiempos con el orden actual
     * @param  array{lat: float, lng: float}|null  $desde  posición actual del vehículo
     */
    public function optimizar(Ruta $ruta, bool $reordenar = true, ?array $desde = null): Ruta
    {
        $paradas = $ruta->paradas()->get();
        $resueltas = $paradas->filter(fn (RutaParada $p) => $p->resuelta())->values();
        $pendientes = $paradas->reject(fn (RutaParada $p) => $p->resuelta())->values();

        if ($pendientes->isEmpty()) {
            throw new \DomainException('La ruta no tiene paradas pendientes.');
        }

        if ($pendientes->contains(fn (RutaParada $p) => ! $p->tieneCoordenadas())) {
            throw new \DomainException('Todas las paradas necesitan ubicación en el mapa antes de optimizar.');
        }

        $inicio = $desde ?? ($ruta->tieneOrigen()
            ? ['lat' => $ruta->origen_lat, 'lng' => $ruta->origen_lng]
            : ['lat' => $pendientes->first()->lat, 'lng' => $pendientes->first()->lng]);

        $regresa = $ruta->regresa_a_origen && $ruta->tieneOrigen();
        $fin = $regresa ? ['lat' => $ruta->origen_lat, 'lng' => $ruta->origen_lng] : null;
        $usaGoogle = (bool) config('logistica.google_key');
        $metodo = 'local';

        $orden = $pendientes->all();

        if ($reordenar) {
            $reordenado = null;

            if ($usaGoogle && $regresa && count($orden) >= 2 && count($orden) <= self::MAX_GOOGLE) {
                $reordenado = $this->ordenarConGoogle($orden, $inicio, $fin);
                $metodo = $reordenado ? 'google' : 'local';
            }

            $orden = $reordenado ?? $this->ordenarLocal($orden, $inicio, $fin);
        }

        // Tramos y trazo con el orden definitivo
        $calculo = $usaGoogle && count($orden) <= self::MAX_GOOGLE
            ? $this->tramosGoogle($orden, $inicio, $fin)
            : null;

        if ($calculo) {
            $metodo = 'google';
        } else {
            $calculo = $this->tramosLocal($orden, $inicio, $fin);
            $metodo = 'local';
        }

        DB::transaction(function () use ($ruta, $resueltas, $orden, $calculo, $metodo) {
            $n = 0;
            foreach ($resueltas as $p) {
                $p->update(['orden' => ++$n]);
            }

            $reloj = $this->horaDeSalida($ruta);

            foreach ($orden as $i => $p) {
                $tramo = $calculo['tramos'][$i];
                $reloj = $reloj->copy()->addSeconds($tramo['duracion_s']);

                $p->update([
                    'orden' => ++$n,
                    'distancia_desde_anterior_m' => $tramo['distancia_m'],
                    'duracion_desde_anterior_s' => $tramo['duracion_s'],
                    'eta' => $reloj,
                ]);

                $reloj = $reloj->copy()->addMinutes(self::MIN_POR_PARADA);
            }

            $ruta->update([
                'distancia_m' => $calculo['distancia_m'],
                'duracion_s' => $calculo['duracion_s'],
                'trazo_planeado' => json_encode($calculo['trazo']),
                'optimizada_con' => $metodo,
                'optimizada_en' => now(),
            ]);
        });

        return $ruta->refresh();
    }

    private function horaDeSalida(Ruta $ruta): \Illuminate\Support\Carbon
    {
        if ($ruta->activa()) {
            return now();
        }

        $fecha = $ruta->fecha_programada ?? now();
        $hora = $ruta->hora_salida ?: '09:00:00';

        return $fecha->copy()->setTimeFromTimeString((string) $hora);
    }

    // ------------------------------------------------------------------
    // Cálculo local
    // ------------------------------------------------------------------

    /** Distancia por carretera estimada entre dos puntos, en metros. */
    private function dist(array $a, array $b): float
    {
        return Geo::distanciaM($a['lat'], $a['lng'], $b['lat'], $b['lng']) * config('logistica.factor_carretera', 1.3);
    }

    private function punto(RutaParada $p): array
    {
        return ['lat' => $p->lat, 'lng' => $p->lng];
    }

    /** Vecino más cercano + 2-opt. $fin = null significa trayecto abierto. */
    private function ordenarLocal(array $paradas, array $inicio, ?array $fin): array
    {
        $restantes = $paradas;
        $orden = [];
        $actual = $inicio;

        while ($restantes) {
            $mejor = null;
            $mejorD = INF;

            foreach ($restantes as $k => $p) {
                $d = $this->dist($actual, $this->punto($p));
                // Las urgentes pesan menos: se prefieren aunque estén más lejos
                if ($p->urgente) {
                    $d *= 0.5;
                }
                if ($d < $mejorD) {
                    $mejorD = $d;
                    $mejor = $k;
                }
            }

            $orden[] = $restantes[$mejor];
            $actual = $this->punto($restantes[$mejor]);
            unset($restantes[$mejor]);
        }

        // Con urgentes no se mejora con 2-opt: se respetaría menos su prioridad
        if (count($orden) < 4 || collect($orden)->contains(fn ($p) => $p->urgente)) {
            return $orden;
        }

        $costo = function (array $o) use ($inicio, $fin) {
            $t = 0;
            $prev = $inicio;
            foreach ($o as $p) {
                $t += $this->dist($prev, $this->punto($p));
                $prev = $this->punto($p);
            }

            return $t + ($fin ? $this->dist($prev, $fin) : 0);
        };

        $mejoro = true;
        $vueltas = 0;

        while ($mejoro && $vueltas++ < 50) {
            $mejoro = false;
            $n = count($orden);

            for ($i = 0; $i < $n - 1; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $candidato = array_merge(
                        array_slice($orden, 0, $i),
                        array_reverse(array_slice($orden, $i, $j - $i + 1)),
                        array_slice($orden, $j + 1)
                    );

                    if ($costo($candidato) + 1 < $costo($orden)) {
                        $orden = $candidato;
                        $mejoro = true;
                    }
                }
            }
        }

        return $orden;
    }

    private function tramosLocal(array $orden, array $inicio, ?array $fin): array
    {
        $velocidad = max(10, config('logistica.velocidad_local_kmh', 40)) * 1000 / 3600; // m/s
        $tramos = [];
        $trazo = [[$inicio['lat'], $inicio['lng']]];
        $prev = $inicio;
        $total = 0;

        foreach ($orden as $p) {
            $pt = $this->punto($p);
            $d = (int) round($this->dist($prev, $pt));
            $tramos[] = ['distancia_m' => $d, 'duracion_s' => (int) round($d / $velocidad)];
            $trazo[] = [$pt['lat'], $pt['lng']];
            $total += $d;
            $prev = $pt;
        }

        if ($fin) {
            $d = (int) round($this->dist($prev, $fin));
            $total += $d;
            $trazo[] = [$fin['lat'], $fin['lng']];
        }

        $segundos = (int) round($total / $velocidad);

        return ['tramos' => $tramos, 'distancia_m' => $total, 'duracion_s' => $segundos, 'trazo' => $trazo];
    }

    // ------------------------------------------------------------------
    // Google Routes API
    // ------------------------------------------------------------------

    private function ordenarConGoogle(array $paradas, array $inicio, array $fin): ?array
    {
        $r = $this->consultarGoogle($paradas, $inicio, $fin, true, 'routes.optimizedIntermediateWaypointIndex');

        $indices = $r['routes'][0]['optimizedIntermediateWaypointIndex'] ?? null;

        if (! is_array($indices) || count($indices) !== count($paradas)) {
            return null;
        }

        return array_map(fn ($i) => $paradas[$i], $indices);
    }

    private function tramosGoogle(array $orden, array $inicio, ?array $fin): ?array
    {
        // Sin regreso, el destino es la última parada y las demás van en medio
        $destino = $fin ?? $this->punto(end($orden));
        $intermedias = $fin ? $orden : array_slice($orden, 0, -1);

        $r = $this->consultarGoogle(
            $intermedias, $inicio, $destino, false,
            'routes.legs.distanceMeters,routes.legs.duration,routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline'
        );

        $ruta = $r['routes'][0] ?? null;

        if (! $ruta || empty($ruta['legs'])) {
            return null;
        }

        $tramos = [];
        foreach ($ruta['legs'] as $i => $leg) {
            if ($i >= count($orden)) {
                break; // el último tramo (regreso a la bodega) no es una parada
            }
            $tramos[] = [
                'distancia_m' => (int) ($leg['distanceMeters'] ?? 0),
                'duracion_s' => (int) rtrim($leg['duration'] ?? '0', 's'),
            ];
        }

        if (count($tramos) !== count($orden)) {
            return null;
        }

        return [
            'tramos' => $tramos,
            'distancia_m' => (int) ($ruta['distanceMeters'] ?? array_sum(array_column($tramos, 'distancia_m'))),
            'duracion_s' => (int) rtrim($ruta['duration'] ?? '0', 's'),
            'trazo' => isset($ruta['polyline']['encodedPolyline'])
                ? Geo::decodificarPolyline($ruta['polyline']['encodedPolyline'])
                : [],
        ];
    }

    private function consultarGoogle(array $intermedias, array $origen, array $destino, bool $optimizar, string $campos): ?array
    {
        $loc = fn (array $p) => ['location' => ['latLng' => ['latitude' => $p['lat'], 'longitude' => $p['lng']]]];

        $cuerpo = [
            'origin' => $loc($origen),
            'destination' => $loc($destino),
            'travelMode' => 'DRIVE',
            'routingPreference' => 'TRAFFIC_AWARE',
            'departureTime' => now()->addMinutes(2)->utc()->format('Y-m-d\TH:i:s\Z'),
            'languageCode' => 'es-MX',
            'units' => 'METRIC',
        ];

        if ($intermedias) {
            $cuerpo['intermediates'] = array_map(
                fn ($p) => $loc($p instanceof RutaParada ? $this->punto($p) : $p),
                $intermedias
            );
        }

        if ($optimizar) {
            $cuerpo['optimizeWaypointOrder'] = true;
        }

        try {
            $resp = Http::timeout(15)->withHeaders([
                'X-Goog-Api-Key' => config('logistica.google_key'),
                'X-Goog-FieldMask' => $campos,
            ])->post('https://routes.googleapis.com/directions/v2:computeRoutes', $cuerpo);

            if (! $resp->successful()) {
                Log::warning('Routes API: '.$resp->status().' '.$resp->body());

                return null;
            }

            return $resp->json();
        } catch (\Throwable $e) {
            Log::warning('Routes API falló: '.$e->getMessage());

            return null;
        }
    }
}
