<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Congress;
use App\Models\CongresoParticipante;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use App\Services\Almacen\GestionDeCongresos;
use App\Services\RegresoDeCongresos;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Congresos, como su propia sección de Gestión de Inventario.
 *
 * Antes vivía enterrado dentro de Configuración > Catálogos, mezclado con
 * tipos de equipo y categorías. Aquí es lo que de verdad importa de un
 * congreso desde el lado de inventario: qué se llevó (con su serie, para
 * poder rastrear la pieza puntual) y quién asistió.
 */
class CongresoController extends Controller
{
    public function __construct(private readonly GestionDeCongresos $congresos)
    {
    }

    /**
     * Calendario + detalle del congreso seleccionado.
     */
    public function index(Request $request): View
    {
        /*
        | Respaldo del aviso de piezas sin regresar, por si el programador
        | de tareas del servidor no está corriendo. Trae su propio candado
        | (media hora), así que abrir esta pantalla seguido no manda nada
        | de más.
        */
        app(RegresoDeCongresos::class)->avisarSiToca();

        $congresses = Congress::query()->orderBy('fecha_inicio')->get();

        $seleccionado = null;
        if ($request->filled('congreso')) {
            $seleccionado = $congresses->firstWhere('id', (int) $request->input('congreso'));
        }

        // Sin selección explícita: el más próximo a hoy (activo o por venir).
        $seleccionado ??= $congresses
            ->filter(fn (Congress $c) => $c->fecha_finalizacion->startOfDay()->gte(now()->startOfDay()))
            ->first() ?? $congresses->last();

        try {
            $mes = Carbon::createFromFormat('Y-m', (string) $request->input('mes', now()->format('Y-m')))->startOfMonth();
        } catch (\Throwable $e) {
            $mes = now()->startOfMonth();
        }

        $inicioGrid = $mes->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $finGrid = $mes->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $colores = ['#3b82f6', '#22c55e', '#a855f7', '#f97316', '#ec4899', '#14b8a6', '#eab308'];

        $eventosPorDia = [];
        foreach ($congresses as $congress) {
            $inicio = $congress->fecha_inicio->copy()->startOfDay();
            $fin = $congress->fecha_finalizacion->copy()->startOfDay();
            $color = $colores[$congress->id % count($colores)];

            for ($cursor = $inicio->copy(); $cursor->lte($fin); $cursor->addDay()) {
                if ($cursor->lt($inicioGrid) || $cursor->gt($finGrid)) {
                    continue;
                }

                $eventosPorDia[$cursor->toDateString()][] = [
                    'congress' => $congress,
                    'color' => $color,
                ];
            }
        }

        $calendario = [];
        for ($cursor = $inicioGrid->copy(); $cursor->lte($finGrid); $cursor->addDay()) {
            $calendario[] = [
                'fecha' => $cursor->toDateString(),
                'numero' => $cursor->day,
                'atenuado' => $cursor->month !== $mes->month,
                'hoy' => $cursor->isSameDay(now()),
                'eventos' => $eventosPorDia[$cursor->toDateString()] ?? [],
            ];
        }

        $proximos = $congresses
            ->filter(fn (Congress $c) => $c->fecha_finalizacion->startOfDay()->gte(now()->startOfDay()))
            ->take(5);

        $usuariosAsignados = $seleccionado?->notifiedUsers()->get() ?? collect();

        return view('structure.gestion_Inventario.congresos.index', [
            'congresses' => $congresses,
            'congress' => $seleccionado,
            'productosResumen' => $seleccionado?->productosResumen() ?? collect(),
            'vendidasEnCongreso' => $seleccionado?->unidadesVendidas()->count() ?? 0,
            'participantes' => $seleccionado?->participantes()->latest()->get() ?? collect(),
            'usuariosAsignados' => $usuariosAsignados,
            'usuariosDisponibles' => User::query()
                ->whereNotIn('id', $usuariosAsignados->pluck('id'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'mes' => $mes,
            'mesAnterior' => $mes->copy()->subMonth()->format('Y-m'),
            'mesSiguiente' => $mes->copy()->addMonth()->format('Y-m'),
            'calendario' => $calendario,
            'proximos' => $proximos,
            'productosDisponibles' => Producto::orderBy('tipo_equipo')->get(),
        ]);
    }

    /**
     * Unidades disponibles de un producto, con su foto, para elegir a mano
     * cuáles se llevan al congreso (no una cantidad a ciegas).
     */
    public function unidadesDisponibles(Request $request): JsonResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
        ]);

        $unidades = $this->congresos->unidadesDisponibles(Producto::findOrFail($data['producto_id']));

        return response()->json(['unidades' => $unidades]);
    }

    public function create(): View
    {
        return view('structure.gestion_Inventario.congresos.create', [
            'categories' => Category::query()->orderBy('nombre')->get(),
            'productosDisponibles' => Producto::orderBy('tipo_equipo')->get(),
            'usuarios' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->congresos->normalizar($request, $request->validate(GestionDeCongresos::REGLAS, GestionDeCongresos::MENSAJES));

        $request->validate(GestionDeCongresos::REGLAS_ALTA_INICIAL);

        // Desde la propia pantalla de "Nuevo congreso" ya se pueden elegir
        // las unidades (viendo su foto) y los usuarios que van.
        $congress = $this->congresos->crear(
            $data,
            (array) $request->file('archivos', []),
            (array) $request->input('serial_ids', []),
            (array) $request->input('user_ids', []),
        );

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', 'Congreso guardado correctamente.');
    }

    public function edit(Congress $congress): View
    {
        return view('structure.gestion_Inventario.congresos.edit', [
            'congress' => $congress,
            'categories' => Category::query()->orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Congress $congress): RedirectResponse
    {
        $data = $this->congresos->normalizar($request, $request->validate(GestionDeCongresos::REGLAS, GestionDeCongresos::MENSAJES));

        $this->congresos->actualizar($congress, $data, (array) $request->file('archivos', []), (array) $request->input('quitar_archivos', []));

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', 'Congreso actualizado correctamente.');
    }

    public function destroy(Congress $congress): RedirectResponse
    {
        $congress->delete();

        return redirect()->route('inventory.congresos.index')
            ->with('status', 'Congreso eliminado correctamente.');
    }

    /**
     * Manda a un congreso las unidades que se eligieron a mano (viendo su
     * foto), no una cantidad a ciegas.
     *
     * Es solo una etiqueta sobre la pieza (congress_id): no toca su
     * estado ni su disponibilidad.
     */
    public function agregarProducto(Request $request, Congress $congress): RedirectResponse
    {
        $data = $request->validate([
            'serial_ids' => ['required', 'array', 'min:1'],
            'serial_ids.*' => ['integer', 'exists:producto_seriales,id'],
        ], [
            'serial_ids.required' => 'Elige al menos una unidad de las que se muestran.',
        ]);

        // Filtro de seguridad: solo unidades que de verdad siguen disponibles
        // y sin congreso, aunque el checkbox venga marcado desde antes.
        $cuantas = $this->congresos->agregarUnidades($congress, $data['serial_ids']);

        if ($cuantas === 0) {
            return back()->withErrors([
                'serial_ids' => 'Esas unidades ya no están disponibles.',
            ]);
        }

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', $cuantas.' unidad(es) agregadas al congreso.');
    }

    public function agregarUsuario(Request $request, Congress $congress): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $congress->notifiedUsers()->syncWithoutDetaching([$data['user_id']]);

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', 'Usuario agregado al congreso.');
    }

    public function quitarUsuario(Congress $congress, User $user): RedirectResponse
    {
        $congress->notifiedUsers()->detach($user);

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', 'Usuario quitado del congreso.');
    }

    /** Regresa una unidad puntual: ya no está "en" el congreso. */
    public function quitarUnidad(Congress $congress, ProductoSerial $serial): RedirectResponse
    {
        abort_unless((int) $serial->congress_id === (int) $congress->id, 404);

        $this->congresos->quitarUnidad($serial);

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', 'Unidad regresada del congreso.');
    }

    /**
     * Regresa de un jalón todo lo que no se vendió.
     *
     * Es lo que cierra el ciclo: al terminar el congreso, lo que sobró
     * vuelve al almacén. Sin esto las piezas se quedaban marcadas para
     * siempre y el dato dejaba de servir.
     */
    public function regresarTodas(Congress $congress): RedirectResponse
    {
        // Las vendidas no se tocan: su marca es el rastro de dónde se vendieron.
        $cuantas = $this->congresos->regresarTodas($congress);

        if ($cuantas === 0) {
            return back()->withErrors(['unidades' => 'Este congreso no tiene piezas por regresar.']);
        }

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', "Se regresaron {$cuantas} pieza(s) al almacén.");
    }

    public function agregarParticipante(Request $request, Congress $congress): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'rol' => ['nullable', 'string', 'max:100'],
            'empresa' => ['nullable', 'string', 'max:255'],
        ]);

        $congress->participantes()->create($data);

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', 'Participante agregado.');
    }

    public function quitarParticipante(Congress $congress, CongresoParticipante $participante): RedirectResponse
    {
        abort_unless((int) $participante->congress_id === (int) $congress->id, 404);

        $participante->delete();

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', 'Participante eliminado.');
    }
}
