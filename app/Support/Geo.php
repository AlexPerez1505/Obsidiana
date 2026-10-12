<?php

namespace App\Support;

/** Cálculos de distancia y trazos sobre coordenadas. */
class Geo
{
    /** Distancia en línea recta (haversine) entre dos puntos, en metros. */
    public static function distanciaM(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $p1 = deg2rad($lat1);
        $p2 = deg2rad($lat2);
        $dp = deg2rad($lat2 - $lat1);
        $dl = deg2rad($lng2 - $lng1);

        $a = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /**
     * Distancia mínima en metros de un punto a una polilínea [[lat,lng],...],
     * medida contra cada tramo (no solo contra los vértices).
     */
    public static function distanciaAPolilineaM(float $lat, float $lng, array $trazo): ?float
    {
        $n = count($trazo);

        if ($n === 0) {
            return null;
        }

        if ($n === 1) {
            return self::distanciaM($lat, $lng, $trazo[0][0], $trazo[0][1]);
        }

        // Proyección plana local en metros: suficiente para tramos de pocos km
        $kLat = 111320;
        $kLng = 111320 * cos(deg2rad($lat));
        $min = INF;

        for ($i = 0; $i < $n - 1; $i++) {
            $ax = ($trazo[$i][1] - $lng) * $kLng;
            $ay = ($trazo[$i][0] - $lat) * $kLat;
            $bx = ($trazo[$i + 1][1] - $lng) * $kLng;
            $by = ($trazo[$i + 1][0] - $lat) * $kLat;

            $dx = $bx - $ax;
            $dy = $by - $ay;
            $largo2 = $dx * $dx + $dy * $dy;
            $t = $largo2 > 0 ? max(0, min(1, -($ax * $dx + $ay * $dy) / $largo2)) : 0;

            $d = hypot($ax + $t * $dx, $ay + $t * $dy);
            $min = min($min, $d);
        }

        return $min;
    }

    /** Decodifica el "encoded polyline" de Google a [[lat, lng], ...]. */
    public static function decodificarPolyline(string $encoded): array
    {
        $puntos = [];
        $i = 0;
        $len = strlen($encoded);
        $lat = 0;
        $lng = 0;

        while ($i < $len) {
            foreach (['lat', 'lng'] as $eje) {
                $shift = 0;
                $resultado = 0;
                do {
                    $b = ord($encoded[$i++]) - 63;
                    $resultado |= ($b & 0x1f) << $shift;
                    $shift += 5;
                } while ($b >= 0x20 && $i < $len);

                $delta = ($resultado & 1) ? ~($resultado >> 1) : ($resultado >> 1);
                $$eje += $delta;
            }

            $puntos[] = [round($lat / 1e5, 6), round($lng / 1e5, 6)];
        }

        return $puntos;
    }
}
