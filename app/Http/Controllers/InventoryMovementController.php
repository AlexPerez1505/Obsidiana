<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Models\InventoryMovement;
use App\Services\Inventario\ChecklistConIa;
use App\Services\Inventario\EntradasDeInventario;
use App\Services\Inventario\FotoPorPedazos;
use App\Services\Inventario\OperacionRechazada;
use App\Services\Inventario\ProductosDeInventario;
use App\Services\Inventario\VideoPorPedazos;
use App\Support\ChecklistRecepcion;
use App\Support\EdicionPropia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $query = InventoryMovement::query()->with(['creator', 'seriales'])->latest('movement_date')->latest('id');

        $filters = $request->validate(ProductosDeInventario::REGLAS_FILTROS_CATALOGO + [
            'search' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', 'in:entrada,salida,transferencia'],
            'movement_types' => ['nullable', 'array', 'max:4'],
            'movement_types.*' => ['in:entrada,vendido,salida,transferencia'],
            'condiciones' => ['nullable', 'array', 'max:2'],
            'condiciones.*' => ['in:nuevo,usado'],
            'almacenes' => ['nullable', 'array', 'max:100'],
            'almacenes.*' => ['string', 'max:255'],
            'quienes' => ['nullable', 'array', 'max:100'],
            'quienes.*' => ['string', 'max:255'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'estado' => ['nullable', 'in:entrada,salida'],
        ]);
        if (empty($filters['movement_types']) && ! empty($filters['tipo'])) {
            $filters['movement_types'] = $filters['tipo'] === 'salida' ? ['vendido', 'salida'] : [$filters['tipo']];
        }
        $entradas->filtrar($query, null, $filters['desde'] ?? null, $filters['hasta'] ?? null);
        $entradas->filtrarListado($query, $filters);

        $movements = $query->paginate(20)->withQueryString();

        return view('structure.gestion_Inventario.entrada_salida.index', [
            'movements' => $movements,
            'filters' => $filters,
            'catalogo' => $this->catalogoEquipo(),
            'opcionesAlmacenes' => InventoryMovement::selectRaw("COALESCE(NULLIF(warehouse, ''), 'Sin almacén') AS nombre")->distinct()->orderBy('nombre')->pluck('nombre'),
            'opcionesQuienes' => InventoryMovement::leftJoin('users', 'users.id', '=', 'inventory_movements.created_by')
                ->selectRaw("COALESCE(NULLIF(users.name, ''), 'Sin registrar') AS nombre")->distinct()->orderBy('nombre')->pluck('nombre'),
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
     * Recibe un pedazo de una foto (de una pieza o la del catálogo). Igual
     * que el video: así no hay límite de tamaño y el submit solo manda rutas.
     */
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

    /** Checklist de recepción hecho para el equipo elegido (tipo, subtipo, marca y modelo). */
    public function checklistIa(Request $request, ChecklistConIa $checklist): JsonResponse
    {
        $data = $request->validate([
            'equipment_type_id' => ['required', 'integer'],
            'subtype_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'equipment_model_id' => ['nullable', 'integer'],
        ]);

        return response()->json($checklist->para(
            (int) $data['equipment_type_id'],
            $data['subtype_id'] ?? null,
            $data['brand_id'] ?? null,
            $data['equipment_model_id'] ?? null,
        ));
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

    public function show(Request $request, InventoryMovement $movimiento, EntradasDeInventario $entradas): View
    {
        $movimiento->load(['creator', 'seriales.procesos']);
        $producto = $movimiento->producto();

        $returnTo = $request->query('return_to');
        $volverUrl = route('inventory.movimientos.index');
        if (is_string($returnTo) && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            $volverUrl = url($returnTo);
        }

        // Editar abre esta misma pantalla con el formulario arriba
        $editando = $request->boolean('editar')
            && EdicionPropia::puedeEditarMovimiento($request->user(), $movimiento);

        return view('structure.gestion_Inventario.entrada_salida.show', [
            'movimiento' => $movimiento,
            'producto' => $producto,
            'ordenSalida' => $entradas->ordenDeSalida($movimiento),
            'editando' => $editando,
            'estadosGenerales' => ChecklistRecepcion::ESTADOS,
            'puedeEditarPiezas' => $editando && $producto && EdicionPropia::puedeEditarProducto($request->user(), $producto),
            'volverUrl' => $volverUrl,
        ]);
    }

    /**
     * Corregir una entrada: fechas, referencia, notas y, si es usado, su
     * estado general y las respuestas del checklist. Lo puede quien tiene
     * permiso de editar inventario, o quien la registró en sus primeras
     * EdicionPropia::HORAS horas.
     */
    public function edit(Request $request, InventoryMovement $movimiento): RedirectResponse
    {
        abort_unless(EdicionPropia::puedeEditarMovimiento($request->user(), $movimiento), 403, EdicionPropia::mensaje());

        // Se edita en el mismo detalle: así se ve todo lo de la entrada mientras se corrige
        return redirect()->to(route('inventory.movimientos.show', [$movimiento, 'editar' => 1]).'#editar');
    }

    public function update(Request $request, InventoryMovement $movimiento, EntradasDeInventario $entradas): RedirectResponse
    {
        abort_unless(EdicionPropia::puedeEditarMovimiento($request->user(), $movimiento), 403, EdicionPropia::mensaje());

        $data = $request->validate(EntradasDeInventario::reglasEdicion($movimiento)
            + ['generar_series' => ['sometimes', 'boolean']], EntradasDeInventario::MENSAJES);

        $generadas = DB::transaction(function () use ($request, $movimiento, $entradas, $data) {
            $cantidad = $request->boolean('generar_series')
                ? $entradas->generarSeriesFaltantes($movimiento, $request->user()) : 0;
            $entradas->actualizar($movimiento, $data);

            return $cantidad;
        });

        return redirect()->route('inventory.movimientos.show', $movimiento)
            ->with('status', "Entrada {$movimiento->folio} actualizada."
                .($request->boolean('generar_series') ? " Series generadas: {$generadas}. Las existentes se conservaron." : ''));
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

    /**
     * Status del equipo escaneado (pistola, cámara o tecleado) para la
     * pantalla de Entrada y salida.
     */
    public function estatus(\Illuminate\Http\Request $request, \App\Services\Inventario\EscaneoDePiezas $escaneo): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate(['codigo' => ['required', 'string', 'max:255']]);
        $codigo = \App\Services\Inventario\EscaneoDePiezas::codigo($data['codigo']);

        if ($codigo === null) {
            return response()->json(['encontrado' => false, 'mensaje' => \App\Services\Inventario\EscaneoDePiezas::NO_ES_ETIQUETA]);
        }

        $pieza = $escaneo->pieza($codigo);

        return response()->json($pieza
            ? $escaneo->estatus($pieza)
            : ['encontrado' => false, 'codigo' => $codigo, 'mensaje' => \App\Services\Inventario\EscaneoDePiezas::noRegistrada($codigo)]);
    }
}
