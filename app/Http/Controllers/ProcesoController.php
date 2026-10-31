<?php

namespace App\Http\Controllers;

use App\Models\PiezaProceso;
use App\Models\ProductoSerial;
use App\Services\Almacen\ColasDeProcesos;
use App\Services\Inventario\FotoPorPedazos;
use App\Services\RutaDeProcesos;
use App\Support\ChecklistProceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
    public function __construct(
        private readonly RutaDeProcesos $ruta,
        private readonly ColasDeProcesos $colas,
    ) {
    }

    public function index(): View
    {
        $pasos = $this->colas->pasosEnCola()
            ->with(['pieza.producto', 'pieza.procesos', 'responsable'])
            ->get();

        // Lo que ya salió y espera a que alguien lo note.
        $listas = $this->colas->listas();

        return view('structure.gestion_Inventario.procesos.index', [
            'pasos' => $pasos,
            'listas' => $listas,
            'resumen' => $this->colas->resumen($pasos, $listas),
        ]);
    }

    /** La pantalla de trabajo de una pieza en un proceso. */
    public function show(PiezaProceso $paso): View
    {
        $paso->load(['pieza.producto', 'pieza.procesos', 'pieza.entrada', 'responsable', 'cerradoPor', 'avances.autor']);

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
        $data = $request->validate(ColasDeProcesos::reglasCierre(), ColasDeProcesos::MENSAJES_CIERRE);

        $razones = $this->colas->cerrar(
            $paso,
            $data['checklist'] ?? [],
            $request->file('evidencias') ?? [],
            $data['trabajo_realizado'] ?? null,
            $data['fotos_paths'] ?? []
        );

        if ($razones) {
            return back()->withInput()->withErrors(['cierre' => implode(' ', $razones)]);
        }

        $pieza = $paso->pieza->refresh()->load('procesos');
        $falta = $pieza->faltaTexto();

        $mensaje = "{$pieza->codigo}: {$paso->nombre()} terminado.";
        $mensaje .= $falta ? " Sigue en {$falta}." : ' Ya está disponible para venta.';

        return redirect()->route('inventory.procesos.index')->with('status', $mensaje);
    }

    /**
     * Guarda lo que va sin cerrar el proceso: el checklist contestado hasta
     * ahora, y la nota y fotos de hoy como un avance más de la bitácora.
     */
    public function avance(Request $request, PiezaProceso $paso): RedirectResponse
    {
        $data = $request->validate(ColasDeProcesos::reglasAvance());

        $razones = $this->colas->guardarAvance(
            $paso,
            $data['checklist'] ?? [],
            $data['trabajo_realizado'] ?? null,
            $data['nota'] ?? null,
            $request->file('evidencias') ?? [],
            $data['fotos_paths'] ?? []
        );

        if ($razones) {
            return back()->withInput()->withErrors(['cierre' => implode(' ', $razones)]);
        }

        return redirect()->route('inventory.procesos.show', $paso)
            ->with('status', "Avance guardado. Puedes seguir mañana donde te quedaste.");
    }

    /** Recibe un pedazo de una foto de avance o de cierre (sin límite de tamaño ni formato). */
    public function subirFotoChunk(Request $request, FotoPorPedazos $fotos): JsonResponse
    {
        $data = $request->validate(FotoPorPedazos::reglas());

        try {
            return response()->json($fotos->recibir(
                $request->file('chunk'),
                $data['upload_id'],
                (int) $data['index'],
                (int) $data['total'],
                $data['extension'],
            ));
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }
    }

    /** Descarta un paso que al verlo de cerca no hacía falta. */
    public function omitir(Request $request, PiezaProceso $paso): RedirectResponse
    {
        $data = $request->validate(ColasDeProcesos::REGLAS_OMITIR, ColasDeProcesos::MENSAJES_OMITIR);

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
        $data = $request->validate(ColasDeProcesos::reglasAgregar());

        $this->colas->agregar($pieza, $data['proceso'], $data['motivo'] ?? null);

        $nombre = PiezaProceso::PROCESOS[$data['proceso']];

        return back()->with('status', "{$pieza->codigo} se mandó a {$nombre}.");
    }

    /**
     * Corrige de un jalón toda la ruta de procesos de una pieza.
     *
     * Es para el caso típico: al dar la entrada se marcaron mal los procesos
     * (p. ej. solo mantenimiento y limpieza) y después se ve que también
     * faltaba hojalatería. Aquí se deja exactamente la lista correcta: se
     * agregan los que faltaban y se quitan los pendientes que sobran. Los
     * pasos que ya se hicieron NO se borran: son historia, se conservan.
     */
    public function definir(Request $request, ProductoSerial $pieza): RedirectResponse
    {
        $data = $request->validate(ColasDeProcesos::reglasDefinir());

        if ($razon = $this->colas->razonParaNoRedefinir($pieza)) {
            return back()->withErrors(['procesos' => $razon]);
        }

        $this->colas->redefinir($pieza, $data['procesos'] ?? []);

        $pieza->refresh()->load('procesos');
        $falta = $pieza->faltaTexto();

        $mensaje = "{$pieza->codigo}: procesos actualizados.";
        $mensaje .= $falta ? " Ahora falta: {$falta}." : ' Queda disponible para venta.';

        return back()->with('status', $mensaje);
    }
}
