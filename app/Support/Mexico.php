<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Estados y municipios de México según el catálogo de INEGI.
 *
 * Los 32 estados viven aquí (no cambian). Los municipios se piden al
 * servicio público de INEGI la primera vez que se usan y se guardan en
 * caché para siempre, así el formulario no depende de él después.
 */
class Mexico
{
    /** Clave INEGI => nombre oficial. */
    public const ESTADOS = [
        '01' => 'Aguascalientes',
        '02' => 'Baja California',
        '03' => 'Baja California Sur',
        '04' => 'Campeche',
        '05' => 'Coahuila de Zaragoza',
        '06' => 'Colima',
        '07' => 'Chiapas',
        '08' => 'Chihuahua',
        '09' => 'Ciudad de México',
        '10' => 'Durango',
        '11' => 'Guanajuato',
        '12' => 'Guerrero',
        '13' => 'Hidalgo',
        '14' => 'Jalisco',
        '15' => 'México',
        '16' => 'Michoacán de Ocampo',
        '17' => 'Morelos',
        '18' => 'Nayarit',
        '19' => 'Nuevo León',
        '20' => 'Oaxaca',
        '21' => 'Puebla',
        '22' => 'Querétaro',
        '23' => 'Quintana Roo',
        '24' => 'San Luis Potosí',
        '25' => 'Sinaloa',
        '26' => 'Sonora',
        '27' => 'Tabasco',
        '28' => 'Tamaulipas',
        '29' => 'Tlaxcala',
        '30' => 'Veracruz de Ignacio de la Llave',
        '31' => 'Yucatán',
        '32' => 'Zacatecas',
    ];

    public static function claveDe(?string $estado): ?string
    {
        $clave = array_search($estado, self::ESTADOS, true);

        return $clave === false ? null : $clave;
    }

    /**
     * Municipios de un estado (por nombre o clave), ordenados.
     * Lista vacía si INEGI no responde: el formulario deja escribirlo a mano.
     *
     * @return array<int, string>
     */
    public static function municipios(?string $estado): array
    {
        $clave = isset(self::ESTADOS[$estado ?? '']) ? $estado : self::claveDe($estado);

        if (! $clave) {
            return [];
        }

        $llave = "mexico.municipios.$clave";

        if ($lista = Cache::get($llave)) {
            return $lista;
        }

        try {
            $lista = collect(Http::timeout(10)->get("https://gaia.inegi.org.mx/wscatgeo/v2/mgem/$clave")->json('datos') ?? [])
                ->pluck('nomgeo')
                ->filter()
                ->sort(fn ($a, $b) => strcoll(self::sinAcentos($a), self::sinAcentos($b)))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('No se pudieron traer municipios de INEGI: '.$e->getMessage());
            $lista = [];
        }

        // Solo se guarda si llegó algo, para reintentar si falló.
        if ($lista) {
            Cache::forever($llave, $lista);
        }

        return $lista;
    }

    private static function sinAcentos(string $texto): string
    {
        return strtr(mb_strtolower($texto), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n~']);
    }
}
