<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use App\Models\RutaParada;
use App\Services\Logistica\MapaEnVivo;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/** Mapa en vivo de la flota: todas las rutas en curso y dónde va cada una. */
class MapaController extends Controller
{
    public function index(): View
    {
        return view('structure.logistica.mapa', [
            'intervalo' => 5,
            'origen' => config('logistica.origen'),
        ]);
    }

    public function feed(MapaEnVivo $mapa): JsonResponse
    {
        $rutas = $mapa->rutasEnCurso();
        $libres = $mapa->vehiculosLibres($rutas);

        return response()->json([
            'rutas' => $rutas->map(function (Ruta $r) {
                $u = $r->ultimaUbicacion;
                $siguiente = $r->paradas->first(fn (RutaParada $p) => in_array($p->estado, [RutaParada::EN_CAMINO, RutaParada::ARRIBO, RutaParada::PENDIENTE], true));

                return [
                    'id' => $r->id,
                    'folio' => $r->folio,
                    'url' => route('logistica.rutas.show', $r),
                    'vehiculo' => $r->vehicle ? trim($r->vehicle->plate_number.' · '.$r->vehicle->brand.' '.$r->vehicle->model) : 'Sin vehículo',
                    'placas' => $r->vehicle?->plate_number,
                    'chofer' => $r->chofer?->name ?? 'Sin chofer',
                    'avance' => $r->avance(),
                    'siguiente' => $siguiente?->nombre,
                    'siguienteEstado' => $siguiente?->estadoLabel(),
                    'eta' => $siguiente?->eta?->format('H:i'),
                    'posicion' => $u ? [
                        'lat' => $u->lat, 'lng' => $u->lng, 'velocidad' => $u->velocidad_kmh,
                        'hace' => $u->registrada_en->diffForHumans(), 'segundos' => $u->registrada_en->diffInSeconds(now()),
                    ] : null,
                    'paradas' => $r->paradas->map(fn (RutaParada $p) => [
                        'n' => $p->orden, 'nombre' => $p->nombre, 'lat' => $p->lat, 'lng' => $p->lng, 'estado' => $p->estado,
                    ])->values()->all(),
                ];
            })->values()->all(),
            'libres' => $libres->map(fn ($v) => [
                'placas' => $v->plate_number,
                'nombre' => trim($v->brand.' '.$v->model),
            ])->values()->all(),
        ]);
    }
}
