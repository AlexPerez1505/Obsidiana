<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use App\Models\RutaDesviacion;
use App\Models\RutaIncidencia;
use App\Models\RutaParada;
use App\Models\VehiculoCombustible;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Indicadores de logística: entregas, puntualidad, kilómetros y costos. */
class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $hasta = $request->filled('hasta') ? Carbon::parse($request->get('hasta'))->endOfDay() : now()->endOfDay();
        $desde = $request->filled('desde') ? Carbon::parse($request->get('desde'))->startOfDay() : $hasta->copy()->subDays(29)->startOfDay();

        if ($desde->greaterThan($hasta)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        $rutas = Ruta::with(['vehicle', 'chofer', 'paradas'])
            ->where('estado', '!=', Ruta::CANCELADA)
            ->whereBetween('fecha_programada', [$desde->toDateString(), $hasta->toDateString()])
            ->get();

        $paradas = $rutas->flatMap->paradas;
        $resueltas = $paradas->filter(fn (RutaParada $p) => $p->estado === RutaParada::COMPLETADA || $p->estado === RutaParada::NO_ENTREGADA);
        $exitosas = $paradas->where('estado', RutaParada::COMPLETADA);
        $noEntregadas = $paradas->where('estado', RutaParada::NO_ENTREGADA);

        $conVentana = $exitosas->filter(fn (RutaParada $p) => $p->ventana_hasta && $p->completada_en);
        $aTiempo = $conVentana->filter(fn (RutaParada $p) => $p->completada_en->format('H:i:s') <= $p->ventana_hasta);

        $enCliente = $exitosas->filter(fn (RutaParada $p) => $p->llegada_en && $p->completada_en)
            ->map(fn (RutaParada $p) => $p->llegada_en->diffInMinutes($p->completada_en));

        $ids = $rutas->pluck('id');
        $km = (float) $rutas->sum('km_recorridos');
        $combustible = VehiculoCombustible::whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->get();
        $gasto = (float) $combustible->sum('monto');

        $kpis = [
            'hoy' => RutaParada::where('estado', RutaParada::COMPLETADA)->whereDate('completada_en', today())->count(),
            'rutas' => $rutas->count(),
            'finalizadas' => $rutas->where('estado', Ruta::FINALIZADA)->count(),
            'entregas' => $exitosas->count(),
            'no_entregadas' => $noEntregadas->count(),
            'exito' => $resueltas->count() ? round($exitosas->count() * 100 / $resueltas->count()) : null,
            'puntualidad' => $conVentana->count() ? round($aTiempo->count() * 100 / $conVentana->count()) : null,
            'km' => $km,
            'tiempo_cliente' => $enCliente->count() ? (int) round($enCliente->avg()) : null,
            'desviaciones' => RutaDesviacion::whereIn('ruta_id', $ids)->count(),
            'incidencias' => RutaIncidencia::whereIn('ruta_id', $ids)->count(),
            'combustible' => $gasto,
            'por_entrega' => $exitosas->count() ? round($gasto / $exitosas->count(), 2) : null,
        ];

        // Por vehículo
        $porVehiculo = $rutas->groupBy('vehicle_id')->map(function ($grupo, $vid) use ($combustible) {
            $v = $grupo->first()->vehicle;
            $pars = $grupo->flatMap->paradas;

            return [
                'nombre' => $v ? $v->plate_number.' · '.trim($v->brand.' '.$v->model) : 'Sin vehículo',
                'rutas' => $grupo->count(),
                'km' => (float) $grupo->sum('km_recorridos'),
                'entregas' => $pars->where('estado', RutaParada::COMPLETADA)->count(),
                'litros' => (float) $combustible->where('vehicle_id', $vid)->sum('litros'),
                'gasto' => (float) $combustible->where('vehicle_id', $vid)->sum('monto'),
            ];
        })->sortByDesc('km')->values();

        // Por chofer
        $porChofer = $rutas->groupBy('chofer_id')->map(function ($grupo) {
            $pars = $grupo->flatMap->paradas;
            $ok = $pars->where('estado', RutaParada::COMPLETADA);
            $vent = $ok->filter(fn (RutaParada $p) => $p->ventana_hasta && $p->completada_en);
            $tiempo = $vent->filter(fn (RutaParada $p) => $p->completada_en->format('H:i:s') <= $p->ventana_hasta);

            return [
                'nombre' => $grupo->first()->chofer?->name ?? 'Sin chofer',
                'rutas' => $grupo->count(),
                'entregas' => $ok->count(),
                'fallidas' => $pars->where('estado', RutaParada::NO_ENTREGADA)->count(),
                'puntualidad' => $vent->count() ? round($tiempo->count() * 100 / $vent->count()) : null,
                'km' => (float) $grupo->sum('km_recorridos'),
            ];
        })->sortByDesc('entregas')->values();

        // Por día
        $porDia = [];
        for ($d = $desde->copy(); $d->lessThanOrEqualTo($hasta); $d->addDay()) {
            $porDia[$d->toDateString()] = ['ok' => 0, 'no' => 0];
        }
        foreach ($exitosas as $p) {
            $k = $p->completada_en?->toDateString();
            if ($k && isset($porDia[$k])) {
                $porDia[$k]['ok']++;
            }
        }
        foreach ($noEntregadas as $p) {
            $k = ($p->completada_en ?? $p->updated_at)?->toDateString();
            if ($k && isset($porDia[$k])) {
                $porDia[$k]['no']++;
            }
        }

        return view('structure.logistica.analytics', [
            'desde' => $desde, 'hasta' => $hasta,
            'kpis' => $kpis,
            'porVehiculo' => $porVehiculo,
            'porChofer' => $porChofer,
            'porDia' => $porDia,
            'maxDia' => max(1, collect($porDia)->map(fn ($x) => $x['ok'] + $x['no'])->max()),
        ]);
    }
}
