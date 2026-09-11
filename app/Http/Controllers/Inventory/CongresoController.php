<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Congress;
use App\Models\CongresoParticipante;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
    private const CARPETA = 'congresos';

    /**
     * Calendario + detalle del congreso seleccionado.
     */
    public function index(Request $request): View
    {
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

        $producto = Producto::findOrFail($data['producto_id']);

        $unidades = $producto->serialesDisponibles()
            ->whereNull('congress_id')
            ->with('entrada')
            ->oldest('id')
            ->get()
            ->map(fn (ProductoSerial $u) => [
                'id' => $u->id,
                'codigo' => $u->codigo,
                'no_serie' => $u->no_serie,
                'foto' => $u->fotoUrl() ?: ($u->entrada?->evidenceUrls()[0] ?? null),
            ]);

        return response()->json(['unidades' => $unidades]);
    }

    public function create(): View
    {
        return view('structure.gestion_Inventario.congresos.create', [
            'categories' => Category::query()->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validado($request);

        $data['path_archivo'] = $this->subirArchivos($request);

        $congress = Congress::create($data);

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
        $data = $this->validado($request);

        $data['path_archivo'] = $this->subirArchivos($request, (array) ($congress->path_archivo ?? []));

        $congress->update($data);

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
        $unidades = ProductoSerial::whereIn('id', $data['serial_ids'])
            ->where('vendido', false)
            ->whereNotIn('estado', ProductoSerial::NO_VENDIBLES)
            ->whereNull('congress_id')
            ->get();

        if ($unidades->isEmpty()) {
            return back()->withErrors([
                'serial_ids' => 'Esas unidades ya no están disponibles.',
            ]);
        }

        ProductoSerial::whereIn('id', $unidades->pluck('id'))->update([
            'congress_id' => $congress->id,
            'enviado_a_congreso_en' => now(),
        ]);

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', $unidades->count().' unidad(es) agregadas al congreso.');
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

        $serial->update(['congress_id' => null, 'enviado_a_congreso_en' => null]);

        return redirect()->route('inventory.congresos.index', ['congreso' => $congress->id])
            ->with('status', 'Unidad regresada del congreso.');
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

    /**
     * Reglas comunes de alta y edición.
     */
    private function validado(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_finalizacion' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'hora_montaje' => ['required', 'date_format:H:i'],
            'hora_desmontaje' => ['required', 'date_format:H:i'],
            'descarga_acceso' => ['nullable', 'boolean'],
            'descarga_texto' => ['nullable', 'string', 'max:255'],
            'acceso_subir' => ['nullable', 'boolean'],
            'subir_texto' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'archivos' => ['nullable', 'array', 'max:10'],
            'archivos.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx'],
            'quitar_archivos' => ['nullable', 'array'],
        ], [
            'archivos.max' => 'Puedes subir hasta 10 archivos a la vez.',
            'archivos.*.mimes' => 'Solo se aceptan imágenes, PDF o documentos de Office.',
        ]);

        $data['descarga_acceso'] = $request->boolean('descarga_acceso');
        $data['acceso_subir'] = $request->boolean('acceso_subir');

        unset($data['archivos'], $data['quitar_archivos']);

        return $data;
    }

    /**
     * Guarda los archivos nuevos y devuelve la lista completa.
     *
     * @return array<int, string>
     */
    private function subirArchivos(Request $request, array $previos = []): array
    {
        foreach ((array) $request->input('quitar_archivos', []) as $ruta) {
            if (Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
            }

            $previos = array_values(array_diff($previos, [$ruta]));
        }

        foreach ((array) $request->file('archivos', []) as $archivo) {
            $previos[] = $archivo->store(self::CARPETA, 'public');
        }

        return array_values(array_unique($previos));
    }
}
