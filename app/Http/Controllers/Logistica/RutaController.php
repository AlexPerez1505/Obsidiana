<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\OrdenSalida;
use App\Models\Ruta;
use App\Models\RutaParada;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Logistica\Geocodificador;
use App\Services\Logistica\OperacionDeRutas;
use App\Services\Logistica\PlaneacionDeRutas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rutas de reparto: alta, edición, optimización del orden y control de
 * la ruta desde la oficina. Lo que hace el chofer vive en ChoferController.
 * Las reglas de alta y edición viven en PlaneacionDeRutas (las comparte la API).
 */
class RutaController extends Controller
{
    public function __construct(private PlaneacionDeRutas $planeacion) {}

    public function index(Request $request): View
    {
        $estado = array_key_exists($request->get('estado'), Ruta::ESTADOS) ? $request->get('estado') : 'abiertas';

        $rutas = Ruta::with(['vehicle', 'chofer', 'paradas'])
            ->when($estado === 'abiertas', fn ($q) => $q->whereNotIn('estado', [Ruta::FINALIZADA, Ruta::CANCELADA]))
            ->when($estado !== 'abiertas', fn ($q) => $q->where('estado', $estado))
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = '%'.$request->get('q').'%';
                $q->where(fn ($w) => $w->where('folio', 'like', $t)
                    ->orWhereHas('paradas', fn ($p) => $p->where('nombre', 'like', $t)));
            })
            ->orderByDesc('fecha_programada')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        $conteos = Ruta::selectRaw('estado, COUNT(*) as n')->groupBy('estado')->pluck('n', 'estado');

        return view('structure.logistica.rutas.index', [
            'rutas' => $rutas,
            'estado' => $estado,
            'busqueda' => $request->get('q'),
            'conteos' => [
                'abiertas' => (int) ($conteos[Ruta::BORRADOR] ?? 0) + (int) ($conteos[Ruta::PROGRAMADA] ?? 0) + (int) ($conteos[Ruta::EN_RUTA] ?? 0),
                Ruta::EN_RUTA => (int) ($conteos[Ruta::EN_RUTA] ?? 0),
                Ruta::FINALIZADA => (int) ($conteos[Ruta::FINALIZADA] ?? 0),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $ruta = new Ruta([
            'fecha_programada' => now()->addDay(),
            'hora_salida' => '09:00',
            'regresa_a_origen' => true,
            'exigir_evidencia' => true,
            'origen_nombre' => config('logistica.origen.nombre'),
            'origen_direccion' => config('logistica.origen.direccion'),
            'origen_lat' => config('logistica.origen.lat'),
            'origen_lng' => config('logistica.origen.lng'),
        ]);

        $datos = $this->datosFormulario($ruta);

        // "Armar desde órdenes listas": una parada por cada orden de salida lista
        // para firma que todavía no va en una ruta abierta.
        if ($request->boolean('listas') && ! old('paradas')) {
            $datos['paradasIniciales'] = $this->planeacion->ordenesListas()
                ->map(fn (OrdenSalida $o) => $this->planeacion->paradaDesdeOrden($o))
                ->values()->all();
        }

        return view('structure.logistica.rutas.form', $datos);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        $ruta = $this->planeacion->crear($data, $request->user()->id);

        return redirect()->route('logistica.rutas.show', $ruta)->with('status', "Ruta {$ruta->folio} creada. Optimízala para definir el mejor orden.");
    }

    public function show(Ruta $ruta): View
    {
        $ruta->load(['vehicle', 'chofer', 'paradas.items', 'paradas.evidencias', 'paradas.ordenSalida', 'ultimaUbicacion', 'eventos.usuario', 'eventos.parada', 'desviaciones', 'incidencias.usuario']);

        return view('structure.logistica.rutas.show', [
            'ruta' => $ruta,
            'datosMapa' => $this->datosMapa($ruta),
        ]);
    }

    public function edit(Ruta $ruta): View|RedirectResponse
    {
        if (! $ruta->editable()) {
            return redirect()->route('logistica.rutas.show', $ruta)->withErrors(['ruta' => 'Una ruta en curso o cerrada ya no se edita.']);
        }

        $ruta->load('paradas.items');

        return view('structure.logistica.rutas.form', $this->datosFormulario($ruta));
    }

    public function update(Request $request, Ruta $ruta): RedirectResponse
    {
        if (! $ruta->editable()) {
            return redirect()->route('logistica.rutas.show', $ruta)->withErrors(['ruta' => 'Una ruta en curso o cerrada ya no se edita.']);
        }

        $data = $this->validar($request);

        $this->planeacion->actualizar($ruta, $data, $request->user()->id);

        return redirect()->route('logistica.rutas.show', $ruta)->with('status', 'Ruta actualizada.');
    }

    public function destroy(Ruta $ruta): RedirectResponse
    {
        if ($ruta->activa()) {
            return back()->withErrors(['ruta' => 'No se puede eliminar una ruta en curso. Finalízala o cancélala primero.']);
        }

        $ruta->delete();

        return redirect()->route('logistica.rutas.index')->with('status', 'Ruta eliminada.');
    }

    /** Calcula el mejor orden de las paradas (o lo reoptimiza desde la posición actual). */
    public function optimizar(Ruta $ruta): RedirectResponse
    {
        try {
            $reoptimizada = $this->planeacion->optimizar($ruta);
        } catch (\DomainException $e) {
            return back()->withErrors(['ruta' => $e->getMessage()]);
        }

        return back()->with('status', 'Orden de paradas '.($reoptimizada ? 'reoptimizado' : 'optimizado').' ('.$ruta->distanciaKm().' km, '.$ruta->duracionTexto().').');
    }

    public function iniciar(Request $request, Ruta $ruta, OperacionDeRutas $op): RedirectResponse
    {
        try {
            $op->iniciar($ruta, $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['ruta' => $e->getMessage()]);
        }

        return back()->with('status', 'La ruta salió. El chofer ya puede reportar su posición.');
    }

    public function finalizar(Request $request, Ruta $ruta, OperacionDeRutas $op): RedirectResponse
    {
        try {
            $op->finalizar($ruta, $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['ruta' => $e->getMessage()]);
        }

        return back()->with('status', 'Ruta finalizada.');
    }

    public function cancelar(Request $request, Ruta $ruta, OperacionDeRutas $op): RedirectResponse
    {
        try {
            $op->cancelar($ruta, $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['ruta' => $e->getMessage()]);
        }

        return back()->with('status', 'Ruta cancelada.');
    }

    /** Buscador de direcciones del formulario. */
    public function geocodificar(Request $request, Geocodificador $geo): JsonResponse
    {
        return response()->json($geo->buscar((string) $request->get('q')));
    }

    /** Estado vivo de una ruta para la pantalla de detalle. */
    public function feed(Ruta $ruta): JsonResponse
    {
        $ruta->load(['paradas', 'ultimaUbicacion']);

        return response()->json($this->estadoVivo($ruta));
    }

    // ------------------------------------------------------------------

    private function validar(Request $request): array
    {
        $data = $request->validate($this->planeacion->reglas(), $this->planeacion->mensajes());

        if ($choque = $this->planeacion->choque($data, $request->route('ruta'))) {
            abort(back()->withInput()->withErrors([$choque[0] => $choque[1]]));
        }

        return $data;
    }

    private function datosFormulario(Ruta $ruta): array
    {
        $paradas = $ruta->exists
            ? $ruta->paradas->map(fn (RutaParada $p) => [
                'nombre' => $p->nombre, 'direccion' => $p->direccion, 'lat' => $p->lat, 'lng' => $p->lng,
                'radio_m' => $p->radio_m, 'contacto_nombre' => $p->contacto_nombre, 'contacto_telefono' => $p->contacto_telefono,
                'ventana_desde' => $p->ventana_desde ? substr($p->ventana_desde, 0, 5) : '',
                'ventana_hasta' => $p->ventana_hasta ? substr($p->ventana_hasta, 0, 5) : '',
                'urgente' => $p->urgente, 'instrucciones' => $p->instrucciones,
                'orden_salida_id' => $p->orden_salida_id,
                'mercancia' => $p->items->map(fn ($i) => "{$i->cantidad} {$i->descripcion}")->implode("\n"),
            ])->values()->all()
            : [];

        return [
            'ruta' => $ruta,
            'paradasIniciales' => old('paradas', $paradas),
            'vehiculos' => Vehicle::where('status', Vehicle::STATUS_ACTIVE)->orderBy('plate_number')->get(),
            'choferes' => User::orderBy('name')->get()->filter(fn (User $u) => $u->isApproved())->values(),
            'ordenes' => $this->planeacion->ordenesDisponibles(),
            'googleActivo' => (bool) config('logistica.google_key'),
        ];
    }

    /** Todo lo que el mapa de una ruta necesita pintar. */
    private function datosMapa(Ruta $ruta): array
    {
        return [
            'origen' => $ruta->tieneOrigen() ? ['lat' => $ruta->origen_lat, 'lng' => $ruta->origen_lng, 'nombre' => $ruta->origen_nombre ?: 'Salida'] : null,
            'trazoPlaneado' => $ruta->trazo(),
            'paradas' => $ruta->paradas->map(fn (RutaParada $p) => [
                'id' => $p->id, 'n' => $p->orden, 'nombre' => $p->nombre, 'lat' => $p->lat, 'lng' => $p->lng,
                'radio' => $p->radio_m, 'estado' => $p->estado, 'estadoLabel' => $p->estadoLabel(),
            ])->values()->all(),
            'desviaciones' => $ruta->desviaciones->map(fn ($x) => ['lat' => $x->lat, 'lng' => $x->lng, 'm' => $x->max_distancia_m, 'hora' => $x->inicio_en->format('H:i')])->values()->all(),
            'trazoReal' => $ruta->ubicaciones()->select('lat', 'lng')->get()->map(fn ($u) => [$u->lat, $u->lng])->all(),
            'vivo' => $this->estadoVivo($ruta),
        ];
    }

    private function estadoVivo(Ruta $ruta): array
    {
        $u = $ruta->ultimaUbicacion;

        return [
            'estado' => $ruta->estado,
            'estadoLabel' => $ruta->estadoLabel(),
            'avance' => $ruta->avance(),
            'posicion' => $u ? [
                'lat' => $u->lat, 'lng' => $u->lng, 'velocidad' => $u->velocidad_kmh,
                'hace' => $u->registrada_en->diffForHumans(), 'segundos' => $u->registrada_en->diffInSeconds(now()),
            ] : null,
            'paradas' => $ruta->paradas->map(fn (RutaParada $p) => [
                'id' => $p->id, 'estado' => $p->estado, 'estadoLabel' => $p->estadoLabel(),
                'llegada' => $p->llegada_en?->format('H:i'), 'salida' => $p->salida_en?->format('H:i'),
            ])->values()->all(),
        ];
    }
}
