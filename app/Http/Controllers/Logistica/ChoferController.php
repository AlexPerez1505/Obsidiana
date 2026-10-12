<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use App\Models\RutaIncidencia;
use App\Models\RutaParada;
use App\Services\Logistica\OperacionDeRutas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * La parte del chofer: ve sus rutas del día en el teléfono, manda su GPS
 * y cierra cada entrega con sus evidencias. Solo toca rutas asignadas a él.
 */
class ChoferController extends Controller
{
    public function index(Request $request): View
    {
        $rutas = Ruta::with(['vehicle', 'paradas'])
            ->where('chofer_id', $request->user()->id)
            ->whereIn('estado', [Ruta::PROGRAMADA, Ruta::EN_RUTA])
            ->orderByRaw("estado = 'en_ruta' desc")
            ->orderBy('fecha_programada')
            ->get();

        $recientes = Ruta::where('chofer_id', $request->user()->id)
            ->where('estado', Ruta::FINALIZADA)
            ->latest('finalizada_en')->limit(5)->get();

        return view('structure.logistica.chofer.index', compact('rutas', 'recientes'));
    }

    public function show(Request $request, Ruta $ruta): View
    {
        $this->propia($request, $ruta);

        $ruta->load(['vehicle', 'paradas.items', 'paradas.evidencias']);

        return view('structure.logistica.chofer.show', [
            'ruta' => $ruta,
            'tiposIncidencia' => RutaIncidencia::TIPOS,
            'intervalo' => config('logistica.intervalo_gps_s'),
            'siguiente' => $ruta->paradas->first(fn (RutaParada $p) => ! $p->resuelta()),
        ]);
    }

    /** El teléfono manda su posición; la respuesta trae el estado de las paradas. */
    public function ubicacion(Request $request, Ruta $ruta, OperacionDeRutas $op): JsonResponse
    {
        $this->propia($request, $ruta);

        $d = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'velocidad_ms' => ['nullable', 'numeric'],
            'precision' => ['nullable', 'numeric'],
            'rumbo' => ['nullable', 'numeric'],
            'registrada_en' => ['nullable', 'date'],
        ]);

        $avisos = $op->registrarUbicacion($ruta, $d);
        $ruta->refresh()->load('paradas');

        return response()->json([
            'activa' => $ruta->activa(),
            'avisos' => $avisos,
            'paradas' => $ruta->paradas->map(fn (RutaParada $p) => [
                'id' => $p->id, 'estado' => $p->estado, 'estadoLabel' => $p->estadoLabel(),
            ])->values()->all(),
        ]);
    }

    public function llegada(Request $request, Ruta $ruta, RutaParada $parada, OperacionDeRutas $op): RedirectResponse
    {
        $this->propia($request, $ruta, $parada);

        try {
            $op->marcarLlegada($parada, $request->user()->id, $request->float('lat') ?: null, $request->float('lng') ?: null);
        } catch (\DomainException $e) {
            return back()->withErrors(['ruta' => $e->getMessage()]);
        }

        return back()->with('status', 'Llegada registrada.');
    }

    public function completar(Request $request, Ruta $ruta, RutaParada $parada, OperacionDeRutas $op): RedirectResponse
    {
        $this->propia($request, $ruta, $parada);

        $d = $request->validate([
            'receptor_nombre' => ['nullable', 'string', 'max:150'],
            'firma' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'fotos' => ['nullable', 'array', 'max:8'],
            'fotos.*' => ['file', 'image', 'max:8192'],
            'remision' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);

        try {
            $op->completar($parada, $d, $request->file('fotos', []), $request->file('remision'), $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withInput($request->except('firma'))->withErrors(['ruta' => $e->getMessage()]);
        }

        return redirect()->route('logistica.chofer.show', $ruta)->with('status', 'Entrega registrada.');
    }

    public function noEntregada(Request $request, Ruta $ruta, RutaParada $parada, OperacionDeRutas $op): RedirectResponse
    {
        $this->propia($request, $ruta, $parada);

        $d = $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ], ['motivo.required' => 'Escribe por qué no se pudo entregar.']);

        try {
            $op->noEntregada($parada, $d['motivo'], $request->user()->id, $d['lat'] ?? null, $d['lng'] ?? null);
        } catch (\DomainException $e) {
            return back()->withErrors(['ruta' => $e->getMessage()]);
        }

        return redirect()->route('logistica.chofer.show', $ruta)->with('status', 'Parada marcada como no entregada.');
    }

    public function incidencia(Request $request, Ruta $ruta, OperacionDeRutas $op): RedirectResponse
    {
        $this->propia($request, $ruta);

        $d = $request->validate([
            'tipo' => ['required', 'in:'.implode(',', array_keys(RutaIncidencia::TIPOS))],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'ruta_parada_id' => ['nullable', 'integer'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'foto' => ['nullable', 'file', 'image', 'max:8192'],
        ], ['tipo.required' => 'Elige qué pasó.']);

        $op->reportarIncidencia($ruta, $d, $request->file('foto'), $request->user()->id);

        return back()->with('status', 'Incidencia reportada. La oficina ya la puede ver.');
    }

    /** Carga de combustible hecha en el camino: se liga al vehículo y a la ruta. */
    public function combustible(Request $request, Ruta $ruta, OperacionDeRutas $op): RedirectResponse
    {
        $this->propia($request, $ruta);

        abort_unless($ruta->vehicle, 422, 'La ruta no tiene vehículo asignado.');

        $d = $request->validate([
            'litros' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'monto' => ['required', 'numeric', 'min:0', 'max:100000'],
            'odometro_km' => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'tanque_lleno' => ['nullable', 'boolean'],
            'estacion' => ['nullable', 'string', 'max:120'],
            'ticket' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        $op->registrarCombustible($ruta, $d, $request->file('ticket'), $request->user()->id);

        return back()->with('status', 'Carga de combustible registrada.');
    }

    /** El chofer solo opera sus rutas (los admins pasan por el Gate, no por aquí). */
    private function propia(Request $request, Ruta $ruta, ?RutaParada $parada = null): void
    {
        abort_unless($ruta->chofer_id === $request->user()->id, 403, 'Esta ruta no está asignada a ti.');

        if ($parada) {
            abort_unless($parada->ruta_id === $ruta->id, 404);
        }
    }
}
