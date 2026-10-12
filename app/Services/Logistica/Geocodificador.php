<?php

namespace App\Services\Logistica;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Convierte una dirección escrita en coordenadas. Con llave de Google usa
 * su Geocoding API; sin ella cae a Nominatim (OpenStreetMap).
 */
class Geocodificador
{
    /** @return array<int, array{label: string, lat: float, lng: float}> */
    public function buscar(string $texto): array
    {
        $texto = trim($texto);

        if (mb_strlen($texto) < 4) {
            return [];
        }

        try {
            return config('logistica.google_key')
                ? $this->google($texto)
                : $this->nominatim($texto);
        } catch (\Throwable $e) {
            Log::warning('Geocodificación fallida: '.$e->getMessage());

            return [];
        }
    }

    private function google(string $texto): array
    {
        $r = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $texto,
            'region' => 'mx',
            'language' => 'es',
            'key' => config('logistica.google_key'),
        ])->json();

        return collect($r['results'] ?? [])->take(5)->map(fn ($x) => [
            'label' => $x['formatted_address'],
            'lat' => (float) $x['geometry']['location']['lat'],
            'lng' => (float) $x['geometry']['location']['lng'],
        ])->values()->all();
    }

    private function nominatim(string $texto): array
    {
        $r = Http::timeout(8)
            ->withHeaders(['User-Agent' => config('app.name', 'Obsidiana').' logistica'])
            ->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'jsonv2',
                'q' => $texto,
                'countrycodes' => 'mx',
                'limit' => 5,
                'accept-language' => 'es',
            ])->json();

        return collect($r ?? [])->map(fn ($x) => [
            'label' => $x['display_name'],
            'lat' => (float) $x['lat'],
            'lng' => (float) $x['lon'],
        ])->values()->all();
    }
}
