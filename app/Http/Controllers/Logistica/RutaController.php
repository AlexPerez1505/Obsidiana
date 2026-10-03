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
use App\Services\Logistica\OptimizadorDeRutas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Rutas de reparto: alta, edición, optimización del orden y control de
 * la ruta desde la oficina. Lo que hace el chofer vive en ChoferController.
 */
class RutaController extends Controller
{
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
            $enRuta = RutaParada::whereNotNull('orden_salida_id')
                ->whereHas('ruta', fn ($q) => $q->whereNotIn('estado', [Ruta::FINALIZADA, Ruta::CANCELADA]))
                ->pluck('orden_salida_id');

            $datos['paradasIniciales'] = OrdenSalida::with(['venta.customer', 'items'])
                ->where('estado', OrdenSalida::LISTA)->whereNotIn('id', $enRuta)
                ->oldest()->limit(30)->get()
                ->map(fn (OrdenSalida $o) => [
                    'nombre' => trim(($o->venta?->customer?->nombre ?? '').' '.($o->venta?->customer?->apellido ?? '')) ?: $o->folio,
                    'direccion' => $o->venta?->customer?->direccion,
                    'contacto_telefono' => $o->venta?->customer?->telefono,
                    'orden_salida_id' => $o->id,
                    'radio_m' => 100,
                    'mercancia' => $o->items->map(fn ($i) => max(1, (int) $i->cantidad).' '.$i->nombre)->implode("\n"),
                ])->values()->all();
        }

        return view('structure.logistica.rutas.form', $datos);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        $ruta = DB::transaction(function () use ($data, $request) {
            $ruta = Ruta::create($this->camposRuta($data) + [
                'folio' => Ruta::siguienteFolio(),
                'estado' => ! empty($data['vehicle_id']) && ! empty($data['chofer_id']) ? Ruta::PROGRAMADA : Ruta::BORRADOR,
                'created_by' => $request->user()->id,
            ]);

            $this->guardarParadas($ruta, $data['paradas']);
            $ruta->registrarEvento('creada', 'Ruta creada');

            return $ruta;
        });

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

        DB::transaction(function () use ($ruta, $data) {
            $ruta->update($this->camposRuta($data) + [
                'estado' => ! empty($data['vehicle_id']) && ! empty($data['chofer_id']) ? Ruta::PROGRAMADA : Ruta::BORRADOR,
                // Cambió el recorrido: el cálculo anterior ya no vale
                'distancia_m' => null, 'duracion_s' => null, 'trazo_planeado' => null,
                'optimizada_con' => null, 'optimizada_en' => null,
            ]);

            $ruta->paradas()->delete();
            $this->guardarParadas($ruta, $data['paradas']);
            $ruta->registrarEvento('editada', 'Ruta editada');
        });

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
    public function optimizar(Request $request, Ruta $ruta, OptimizadorDeRutas $optimizador): RedirectResponse
    {
        if ($ruta->cerrada()) {
            return back()->withErrors(['ruta' => 'La ruta ya está cerrada.']);
        }

        $desde = null;

        // En curso: se reordena lo pendiente desde donde va el vehículo
        if ($ruta->activa()) {
            $u = $ruta->ultimaUbicacion;
            if (! $u) {
                return back()->withErrors(['ruta' => 'Todavía no hay posición del vehículo para reoptimizar.']);
            }
            $desde = ['lat' => $u->lat, 'lng' => $u->lng];
        }

        try {
            $optimizador->optimizar($ruta, true, $desde);
        } catch (\DomainException $e) {
            return back()->withErrors(['ruta' => $e->getMessage()]);
        }

        $ruta->refresh();
        $ruta->registrarEvento($desde ? 'reoptimizada' : 'optimizada', 'Orden calculado con '.($ruta->optimizada_con === 'google' ? 'Google' : 'cálculo local'));

        return back()->with('status', 'Orden de paradas '.($desde ? 'reoptimizado' : 'optimizado').' ('.$ruta->distanciaKm().' km, '.$ruta->duracionTexto().').');
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
        $data = $request->validate([
            'fecha_programada' => ['nullable', 'date'],
            'hora_salida' => ['nullable', 'date_format:H:i'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'chofer_id' => ['nullable', 'exists:users,id'],
            'origen_nombre' => ['nullable', 'string', 'max:150'],
            'origen_direccion' => ['nullable', 'string', 'max:255'],
            'origen_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'origen_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'regresa_a_origen' => ['nullable', 'boolean'],
            'exigir_evidencia' => ['nullable', 'boolean'],
            'notas' => ['nullable', 'string', 'max:2000'],

            'paradas' => ['required', 'array', 'min:1', 'max:60'],
            'paradas.*.nombre' => ['required', 'string', 'max:150'],
            'paradas.*.direccion' => ['nullable', 'string', 'max:255'],
            'paradas.*.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'paradas.*.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'paradas.*.radio_m' => ['nullable', 'integer', 'between:30,2000'],
            'paradas.*.contacto_nombre' => ['nullable', 'string', 'max:120'],
            'paradas.*.contacto_telefono' => ['nullable', 'string', 'max:30'],
            'paradas.*.ventana_desde' => ['nullable', 'date_format:H:i'],
            'paradas.*.ventana_hasta' => ['nullable', 'date_format:H:i'],
            'paradas.*.urgente' => ['nullable', 'boolean'],
            'paradas.*.instrucciones' => ['nullable', 'string', 'max:1000'],
            'paradas.*.mercancia' => ['nullable', 'string', 'max:4000'],
            'paradas.*.orden_salida_id' => ['nullable', 'exists:ordenes_salida,id'],
        ], [
            'paradas.required' => 'Agrega al menos una parada.',
            'paradas.*.nombre.required' => 'Cada parada necesita un nombre.',
        ]);

        // Un vehículo y un chofer no pueden estar en dos rutas en curso a la vez
        foreach (['vehicle_id' => 'vehículo', 'chofer_id' => 'chofer'] as $campo => $texto) {
            if (! empty($data[$campo]) && Ruta::where('estado', Ruta::EN_RUTA)
                ->where($campo, $data[$campo])
                ->when($request->route('ruta'), fn ($q, $r) => $q->where('id', '!=', $r->id))
                ->exists()) {
                abort(back()->withInput()->withErrors([$campo => "Ese {$texto} ya va en una ruta en curso."]));
            }
        }

        return $data;
    }

    private function camposRuta(array $d): array
    {
        return [
            'fecha_programada' => $d['fecha_programada'] ?? null,
            'hora_salida' => $d['hora_salida'] ?? null,
            'vehicle_id' => $d['vehicle_id'] ?? null,
            'chofer_id' => $d['chofer_id'] ?? null,
            'origen_nombre' => $d['origen_nombre'] ?? null,
            'origen_direccion' => $d['origen_direccion'] ?? null,
            'origen_lat' => $d['origen_lat'] ?? null,
            'origen_lng' => $d['origen_lng'] ?? null,
            'regresa_a_origen' => (bool) ($d['regresa_a_origen'] ?? false),
            'exigir_evidencia' => (bool) ($d['exigir_evidencia'] ?? false),
            'notas' => $d['notas'] ?? null,
        ];
    }

    private function guardarParadas(Ruta $ruta, array $paradas): void
    {
        foreach (array_values($paradas) as $i => $p) {
            $orden = ! empty($p['orden_salida_id'])
                ? OrdenSalida::with(['venta', 'items'])->find($p['orden_salida_id'])
                : null;

            $parada = $ruta->paradas()->create([
                'orden' => $i + 1,
                'nombre' => $p['nombre'],
                'direccion' => $p['direccion'] ?? null,
                'lat' => $p['lat'] ?? null,
                'lng' => $p['lng'] ?? null,
                'radio_m' => $p['radio_m'] ?? 100,
                'customer_id' => $orden?->venta?->customer_id,
                'orden_salida_id' => $orden?->id,
                'contacto_nombre' => $p['contacto_nombre'] ?? null,
                'contacto_telefono' => $p['contacto_telefono'] ?? null,
                'ventana_desde' => $p['ventana_desde'] ?? null,
                'ventana_hasta' => $p['ventana_hasta'] ?? null,
                'urgente' => (bool) ($p['urgente'] ?? false),
                'instrucciones' => $p['instrucciones'] ?? null,
            ]);

            $items = $this->interpretarMercancia((string) ($p['mercancia'] ?? ''));

            // Si no escribieron mercancía pero ligaron una orden de salida, se trae de ahí
            if (! $items && $orden) {
                $items = $orden->items->map(fn ($it) => ['descripcion' => $it->nombre, 'cantidad' => max(1, (int) $it->cantidad)])->all();
            }

            foreach ($items as $n => $item) {
                $parada->items()->create($item + ['orden' => $n + 1]);
            }
        }
    }

    /** "2 Monitor Philips" / "Ventilador x3" / "Consumibles" → renglones. */
    private function interpretarMercancia(string $texto): array
    {
        $items = [];

        foreach (preg_split('/\R/u', $texto) as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                continue;
            }

            if (preg_match('/^(\d{1,5})\s*(?:x|×|pz|pza|pzas)?\s+(.+)$/iu', $linea, $m)) {
                $items[] = ['cantidad' => (int) $m[1], 'descripcion' => trim($m[2])];
            } elseif (preg_match('/^(.+?)\s*(?:x|×)\s*(\d{1,5})$/iu', $linea, $m)) {
                $items[] = ['cantidad' => (int) $m[2], 'descripcion' => trim($m[1])];
            } else {
                $items[] = ['cantidad' => 1, 'descripcion' => $linea];
            }
        }

        return $items;
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

        $ordenes = OrdenSalida::with(['venta.customer', 'items'])
            ->whereIn('estado', [OrdenSalida::LISTA, OrdenSalida::PENDIENTE, OrdenSalida::EN_PREPARACION])
            ->latest()->limit(100)->get()
            ->map(fn (OrdenSalida $o) => [
                'id' => $o->id,
                'folio' => $o->folio,
                'cliente' => trim(($o->venta?->customer?->nombre ?? '').' '.($o->venta?->customer?->apellido ?? '')),
                'direccion' => $o->venta?->customer?->direccion,
                'telefono' => $o->venta?->customer?->telefono,
                'mercancia' => $o->items->map(fn ($i) => max(1, (int) $i->cantidad).' '.$i->nombre)->implode("\n"),
            ])->values()->all();

        return [
            'ruta' => $ruta,
            'paradasIniciales' => old('paradas', $paradas),
            'vehiculos' => Vehicle::where('status', Vehicle::STATUS_ACTIVE)->orderBy('plate_number')->get(),
            'choferes' => User::orderBy('name')->get()->filter(fn (User $u) => $u->isApproved())->values(),
            'ordenes' => $ordenes,
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
