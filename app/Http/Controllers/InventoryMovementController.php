<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Models\InventoryMovement;
use App\Services\Inventario\EntradasDeInventario;
use App\Services\Inventario\OperacionRechazada;
use App\Services\Inventario\VideoPorPedazos;
use App\Support\ChecklistRecepcion;
use App\Support\EdicionPropia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Entrada/Salida es la bitácora real de inventario:
 *
 * - Una ENTRADA es cómo se da de alta stock nuevo: pide evidencia (fotos
 *   del lote/envío) y, al guardarse, crea o acumula las unidades del
 *   producto en producto_seriales (igual que hacía ProductoController, pero
 *   ahora queda documentado con quién, cuándo y con qué evidencia llegó).
 * - Una SALIDA se genera sola cuando se registra una venta (ver
 *   VentaController); aquí solo se listan y se consultan.
 *
 * Lo que hace cada entrada vive en EntradasDeInventario, compartido con la
 * API: aquí solo queda cómo se ve en pantalla.
 */
class InventoryMovementController extends Controller
{
    use ConstruyeCatalogoEquipo;

    public function index(Request $request, EntradasDeInventario $entradas): View
    {
        $query = InventoryMovement::query()->with('creator')->latest('movement_date')->latest('id');

        $entradas->filtrar($query, $request->get('tipo'), $request->get('desde'), $request->get('hasta'));

        $movements = $query->paginate(20)->withQueryString();

        return view('structure.gestion_Inventario.entrada_salida.index', [
            'movements' => $movements,
            'filters' => $request->only('tipo', 'desde', 'hasta'),
            'resumen' => $entradas->resumen(),
        ]);
    }

    public function create(Request $request): View
    {
        /*
        | Los errores de las piezas llegan con su índice
        | (unidades.0.evidencias, unidades.2.video_path...). Los bloques de
        | cada pieza los dibuja el script en el navegador, así que no pueden
        | traer su propio error: se juntan aquí para mostrarlos como lista.
        */
        $erroresPiezas = collect(optional($request->session()->get('errors'))->getBag('default')?->messages() ?? [])
            ->filter(fn ($mensajes, $llave) => str_starts_with((string) $llave, 'unidades'))
            ->flatten()
            ->unique()
            ->values();

        return view('structure.gestion_Inventario.entrada_salida.create', [
            'catalogo' => $this->catalogoEquipo(),
            'checklist' => ChecklistRecepcion::grupos(),
            'estadosGenerales' => ChecklistRecepcion::ESTADOS,
            'erroresPiezas' => $erroresPiezas,
        ]);
    }

    /**
     * Recibe un pedazo (chunk) del video de verificación y lo va guardando.
     * Cuando llega el último, se ensamblan y se regresa la ruta del video;
     * el formulario principal solo manda esa ruta como texto.
     */
    public function subirVideoChunk(Request $request, VideoPorPedazos $videos): JsonResponse
    {
        $data = $request->validate(VideoPorPedazos::reglas());

        try {
            return response()->json($videos->recibir(
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

    /**
     * Registra una entrada de inventario: da de alta (o acumula) el
     * producto, crea sus unidades/seriales, y deja evidencia fotográfica.
     */
    public function store(Request $request, EntradasDeInventario $entradas): RedirectResponse
    {
        $usado = $request->input('condicion') === 'usado';

        $data = $request->validate(EntradasDeInventario::reglas($usado), EntradasDeInventario::MENSAJES);

        // Lo que no cuadra después de validar regresa al formulario con lo
        // ya capturado, como cualquier otro error.
        try {
            $resultado = $entradas->registrar($request, $data);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        $redirect = redirect()->route('inventory.movimientos.index')->with('status', $resultado['mensaje']);

        return $resultado['advertencias'] ? $redirect->with('warning', implode(' ', $resultado['advertencias'])) : $redirect;
    }

    /**
     * Hoja de etiquetas con el QR de cada pieza de esta entrada.
     *
     * Se imprime, se corta y se pega una en cada equipo. El QR lleva a la
     * ficha pública de esa pieza, no a nada interno.
     */
    public function etiquetas(InventoryMovement $movimiento): View
    {
        $piezas = $movimiento->seriales()->with('producto')->orderBy('id')->get();

        return view('structure.gestion_Inventario.entrada_salida.etiquetas', [
            'movimiento' => $movimiento,
            'piezas' => $piezas,
        ]);
    }

    public function show(InventoryMovement $movimiento, EntradasDeInventario $entradas): View
    {
        $movimiento->load(['creator', 'seriales.procesos']);

        return view('structure.gestion_Inventario.entrada_salida.show', [
            'movimiento' => $movimiento,
            'producto' => $movimiento->producto(),
            'ordenSalida' => $entradas->ordenDeSalida($movimiento),
        ]);
    }

    /**
     * Elimina un movimiento. Solo tiene sentido para entradas que todavía
     * no se hayan vendido (si alguna de sus unidades ya se vendió, borrar
     * el movimiento dejaría esa venta sin origen).
     *
     * Ya no pide PIN: lo borra quien tiene permiso de eliminar inventario,
     * o quien registró la entrada durante sus primeras EdicionPropia::HORAS
     * horas. La pantalla pide confirmar antes de mandar.
     */
    public function destroy(Request $request, InventoryMovement $movimiento, EntradasDeInventario $entradas): RedirectResponse
    {
        abort_unless(EdicionPropia::puedeEliminarMovimiento($request->user(), $movimiento), 403, EdicionPropia::mensaje('eliminar'));

        try {
            $entradas->eliminar($movimiento);
        } catch (OperacionRechazada $e) {
            return back()->withErrors([$e->campo => $e->getMessage()]);
        }

        return redirect()->route('inventory.movimientos.index')->with('status', "Movimiento {$movimiento->folio} eliminado.");
    }
}
