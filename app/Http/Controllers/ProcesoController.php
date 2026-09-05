<?php

namespace App\Http\Controllers;

use App\Models\PiezaProceso;
use App\Models\ProductoSerial;
use App\Services\RutaDeProcesos;
use App\Support\ChecklistProceso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Las colas de trabajo: qué piezas están esperando en cada proceso.
 *
 * Una pieza aparece en la cola del proceso donde está parada. Al cerrar ese
 * paso avanza sola al siguiente de SU ruta, que no es la misma para todas:
 * puede ir a mantenimiento, o directo a stock si ya no le queda nada.
 *
 * Cerrar un paso no es apretar un botón: hay que responder el checklist de
 * salida y subir fotos de que quedó funcionando. Sin eso, la pieza no pasa
 * a stock.
 */
class ProcesoController extends Controller
{
    public function __construct(private readonly RutaDeProcesos $ruta)
    {
    }

    public function index(): View
    {
        $pasos = PiezaProceso::query()
            ->whereIn('estado', ['pendiente', 'en_curso'])
            ->whereHas('pieza', fn ($q) => $q->whereColumn('producto_seriales.estado', 'pieza_procesos.proceso'))
            ->with(['pieza.producto', 'pieza.procesos', 'responsable'])
            ->orderBy('estado')          // en_curso primero
            ->orderBy('created_at')
            ->get();

        // Lo que ya salió y espera a que alguien lo note.
        $listas = ProductoSerial::query()
            ->where('estado', 'disponible')
            ->where('vendido', false)
            ->whereHas('procesos', fn ($q) => $q->where('estado', 'terminado'))
            ->with('producto')
            ->latest('updated_at')
            ->limit(12)
            ->get();

        return view('structure.gestion_Inventario.procesos.index', [
            'pasos' => $pasos,
            'listas' => $listas,
            'resumen' => [
                'en_cola' => $pasos->where('estado', 'pendiente')->count(),
                'en_curso' => $pasos->where('estado', 'en_curso')->count(),
                'atoradas' => $pasos->filter(fn ($p) => $p->diasDetenida() >= 7)->count(),
                'listas' => $listas->count(),
            ],
        ]);
    }

    /** La pantalla de trabajo de una pieza en un proceso. */
    public function show(PiezaProceso $paso): View
    {
        $paso->load(['pieza.producto', 'pieza.procesos', 'pieza.entrada', 'responsable', 'cerradoPor']);

        return view('structure.gestion_Inventario.procesos.show', [
            'paso' => $paso,
            'pieza' => $paso->pieza,
            'checklist' => ChecklistProceso::para($paso),
            'siguientes' => $paso->pieza->procesos
                ->where('estado', 'pendiente')
                ->where('id', '!=', $paso->id),
        ]);
    }

    /** Alguien tomó la pieza y empezó a trabajarla. */
    public function iniciar(PiezaProceso $paso): RedirectResponse
    {
        $this->ruta->iniciar($paso, auth()->id());

        return back()->with('status', "{$paso->pieza->codigo}: {$paso->nombre()} en curso, a tu nombre.");
    }

    /**
     * Cierra el paso con su verificación.
     *
     * Si el checklist no pasa o no hay fotos, no se cierra: se regresa el
     * porqué y la pieza se queda donde está.
     */
    public function terminar(Request $request, PiezaProceso $paso): RedirectResponse
    {
        $data = $request->validate([
            // Nullable a propósito: si no viene nada, el mensaje lo da la
            // verificación de abajo, que dice exactamente qué falta, y no
            // un "el campo checklist es obligatorio" que no ayuda.
            'checklist' => ['nullable', 'array'],
            'trabajo_realizado' => ['nullable', 'string', 'max:1000'],
            'evidencias' => ['nullable', 'array', 'max:5'],
            'evidencias.*' => ['image', 'max:5120'],
        ], [
            'evidencias.*.image' => 'La evidencia tiene que ser una foto.',
            'evidencias.max' => 'Puedes subir máximo 5 fotos.',
        ]);

        $checklist = ChecklistProceso::limpiar($paso, $data['checklist'] ?? []);
        $yaTenia = count($paso->evidencias ?? []);

        // Se revisa ANTES de subir nada: si no va a cerrar, no se dejan
        // archivos huérfanos en el disco.
        $razones = ChecklistProceso::razonesParaNoCerrar(
            $paso,
            $checklist,
            $yaTenia + count($request->file('evidencias') ?? [])
        );

        if ($razones) {
            return back()->withInput()->withErrors(['cierre' => implode(' ', $razones)]);
        }

        $disco = config('filesystems.fotos_disk', 'public');

        $subidas = collect($request->file('evidencias') ?? [])
            ->map(fn ($archivo) => $archivo->store('inventario/procesos', $disco))
            ->all();

        try {
            $this->ruta->terminar($paso, $checklist, $subidas, $data['trabajo_realizado'] ?? null);
        } catch (\RuntimeException $e) {
            // Algo cambió entre la revisión y el guardado: se limpia lo subido.
            Storage::disk($disco)->delete($subidas);

            return back()->withInput()->withErrors(['cierre' => $e->getMessage()]);
        }

        $pieza = $paso->pieza->refresh()->load('procesos');
        $falta = $pieza->faltaTexto();

        $mensaje = "{$pieza->codigo}: {$paso->nombre()} terminado.";
        $mensaje .= $falta ? " Sigue en {$falta}." : ' Ya está disponible para venta.';

        return redirect()->route('inventory.procesos.index')->with('status', $mensaje);
    }

    /** Descarta un paso que al verlo de cerca no hacía falta. */
    public function omitir(Request $request, PiezaProceso $paso): RedirectResponse
    {
        $data = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'motivo.required' => 'Escribe por qué no hacía falta este proceso.',
            'motivo.min' => 'Explica un poco más: esto queda como constancia de la decisión.',
        ]);

        $this->ruta->omitir($paso, $data['motivo']);

        $pieza = $paso->pieza->refresh()->load('procesos');
        $falta = $pieza->faltaTexto();

        return redirect()->route('inventory.procesos.index')->with(
            'status',
            "{$pieza->codigo}: {$paso->nombre()} se descartó."
            .($falta ? " Sigue en {$falta}." : ' Ya está disponible para venta.')
        );
    }

    /** Manda una pieza a un proceso que no traía en su ruta. */
    public function agregar(Request $request, ProductoSerial $pieza): RedirectResponse
    {
        $data = $request->validate([
            'proceso' => ['required', Rule::in(array_keys(PiezaProceso::PROCESOS))],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $this->ruta->agregar($pieza, $data['proceso'], $data['motivo'] ?: 'Agregado a mano');

        $nombre = PiezaProceso::PROCESOS[$data['proceso']];

        return back()->with('status', "{$pieza->codigo} se mandó a {$nombre}.");
    }
}
