<?php

namespace App\Http\Controllers\Api\V1\Logistica;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Logistica\RutaEnVivoResource;
use App\Http\Resources\Api\V1\Logistica\VehiculoResource;
use App\Services\Logistica\MapaEnVivo;
use Illuminate\Http\JsonResponse;

/**
 * @tags Logística
 */
class MapaController extends Controller
{
    /**
     * Mapa en vivo de la flota.
     *
     * Todas las rutas en curso con su vehículo, chofer, última posición,
     * siguiente parada y paradas; además los vehículos activos que no van en
     * ninguna ruta (`libres`) y el punto de salida por defecto. La web lo
     * consulta cada `intervalo_s` segundos.
     */
    public function index(MapaEnVivo $mapa): JsonResponse
    {
        $rutas = $mapa->rutasEnCurso();

        return response()->json(['data' => [
            'rutas' => RutaEnVivoResource::collection($rutas),
            'libres' => VehiculoResource::collection($mapa->vehiculosLibres($rutas)),
            'origen' => config('logistica.origen'),
            'intervalo_s' => 5,
        ]]);
    }
}
