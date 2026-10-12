<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Services\Logistica\IndicadoresLogistica;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Indicadores de logística: entregas, puntualidad, kilómetros y costos. */
class AnalyticsController extends Controller
{
    public function index(Request $request, IndicadoresLogistica $indicadores): View
    {
        [$desde, $hasta] = $indicadores->periodo($request->get('desde'), $request->get('hasta'));

        $r = $indicadores->calcular($desde, $hasta);

        return view('structure.logistica.analytics', [
            'desde' => $desde, 'hasta' => $hasta,
            'kpis' => $r['kpis'],
            'porVehiculo' => $r['porVehiculo'],
            'porChofer' => $r['porChofer'],
            'porDia' => $r['porDia'],
            'maxDia' => max(1, collect($r['porDia'])->map(fn ($x) => $x['ok'] + $x['no'])->max()),
        ]);
    }
}
