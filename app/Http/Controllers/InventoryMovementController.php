<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Http\Controllers\Concerns\ManejaSeriesDeProducto;
use App\Models\InventoryMovement;
use App\Models\Producto;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
 */
class InventoryMovementController extends Controller
{
    use ManejaSeriesDeProducto, ConstruyeCatalogoEquipo;

    private const ALMACEN = 'Almacen Central';

    public function index(Request $request): View
    {
        $query = InventoryMovement::query()->with('creator')->latest('movement_date')->latest('id');

        if ($tipo = $request->get('tipo')) {
            $query->where('movement_type', $tipo);
        }

        if ($desde = $request->get('desde')) {
            $query->whereDate('movement_date', '>=', $desde);
        }

        if ($hasta = $request->get('hasta')) {
            $query->whereDate('movement_date', '<=', $hasta);
        }

        $movements = $query->paginate(20)->withQueryString();

        return view('structure.gestion_Inventario.entrada_salida.index', [
            'movements' => $movements,
            'filters' => $request->only('tipo', 'desde', 'hasta'),
        ]);
    }

    public function create(): View
    {
        return view('structure.gestion_Inventario.entrada_salida.create', [
            'catalogo' => $this->catalogoEquipo(),
        ]);
    }

    /**
     * Registra una entrada de inventario: da de alta (o acumula) el
     * producto, crea sus unidades/seriales, y deja evidencia fotográfica
     * del lote completo ligada a esas unidades.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipment_type_id' => ['required', 'exists:equipment_types,id'],
            'subtype_id' => ['nullable', 'exists:subtypes,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'equipment_model_id' => ['nullable', 'exists:equipment_models,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'movement_date' => ['required', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'series_texto' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:5120'],
            'evidencias' => ['required', 'array', 'min:1'],
            'evidencias.*' => ['image', 'max:5120'],
        ], [
            'evidencias.required' => 'Sube al menos una foto que documente cómo llegó esta entrada.',
        ]);

        $series = $this->parsearSeries($data['series_texto'] ?? '');
        $series = $this->autocompletarSecuencia($series, (int) $data['cantidad']);

        if ($series->isNotEmpty() && $series->count() !== (int) $data['cantidad']) {
            return back()->withInput()->withErrors([
                'series_texto' => 'Capturaste '.$series->count()." número(s) de serie, pero la cantidad dice {$data['cantidad']}. Deben coincidir, o deja el campo vacío si no vas a registrar series.",
            ]);
        }

        $evidencias = collect($request->file('evidencias'))
            ->map(fn ($archivo) => $archivo->store('inventario/entradas', 'public'))
            ->all();

        $imagen = $request->hasFile('imagen')
            ? $request->file('imagen')->store('productos', 'public')
            : null;

        try {
            try {
                return $this->registrarEntrada($data, $series->all(), $evidencias, $imagen);
            } catch (QueryException $e) {
                if (! $this->esErrorDeDuplicado($e)) {
                    throw $e;
                }

                // Otra entrada del mismo modelo ganó la carrera: se
                // reintenta una vez contra la fila que ya quedó creada.
                return $this->registrarEntrada($data, $series->all(), $evidencias, $imagen);
            }
        } catch (QueryException $e) {
            $this->borrarEvidencias($evidencias);

            if ($imagen) {
                Storage::disk('public')->delete($imagen);
            }

            if (! $this->esErrorDeDuplicado($e)) {
                throw $e;
            }

            return back()->withInput()->withErrors([
                'series_texto' => 'Uno de esos números de serie ya existe para este producto. Revísalos y vuelve a intentar.',
            ]);
        }
    }

    /**
     * Busca (con bloqueo) si el modelo ya tiene fila en productos, crea el
     * movimiento de entrada, y le agrega las unidades nuevas ya ligadas a
     * ese movimiento.
     */
    private function registrarEntrada(array $data, array $series, array $evidencias, ?string $imagen = null): RedirectResponse
    {
        return DB::transaction(function () use ($data, $series, $evidencias, $imagen) {
            $cantidad = (int) $data['cantidad'];

            $productoData = [
                'equipment_type_id' => $data['equipment_type_id'],
                'subtype_id' => $data['subtype_id'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'equipment_model_id' => $data['equipment_model_id'] ?? null,
                'precio' => $data['precio'],
                'descripcion' => $data['descripcion'] ?? null,
            ];

            $existente = ! empty($productoData['equipment_model_id'])
                ? Producto::where('equipment_model_id', $productoData['equipment_model_id'])->lockForUpdate()->first()
                : null;

            $stockAntes = $existente->stock ?? 0;

            if ($existente) {
                $existente->precio = $productoData['precio'];
                $existente->descripcion = $productoData['descripcion'] ?? $existente->descripcion;
                $existente->proveedor = $data['proveedor'] ?? $existente->proveedor;

                // La foto de catálogo es del modelo, no de la entrada: solo
                // se reemplaza si se subió una nueva, y se borra la vieja.
                if ($imagen) {
                    if ($existente->imagen_path) {
                        Storage::disk('public')->delete($existente->imagen_path);
                    }
                    $existente->imagen_path = $imagen;
                }

                $existente->save();
                $producto = $existente;
            } else {
                $producto = Producto::create($productoData + [
                    'stock' => 0,
                    'proveedor' => $data['proveedor'] ?? null,
                    'imagen_path' => $imagen,
                ]);
            }

            $movimiento = InventoryMovement::create([
                'folio' => InventoryMovement::siguienteFolio(InventoryMovement::TYPE_ENTRY),
                'movement_type' => InventoryMovement::TYPE_ENTRY,
                'item_type' => InventoryMovement::ITEM_PRODUCT,
                'item_id' => $producto->id,
                'item_code' => (string) $producto->id,
                'item_name' => trim($producto->marca.' '.$producto->modelo) ?: $producto->tipo_equipo,
                'warehouse' => self::ALMACEN,
                'quantity' => $cantidad,
                'unit' => 'Pza',
                'stock_before' => $stockAntes,
                'stock_after' => $stockAntes + $cantidad,
                'supplier' => $data['proveedor'] ?? null,
                'movement_date' => $data['movement_date'],
                'notes' => $data['notas'] ?? null,
                'evidence_paths' => $evidencias,
                'created_by' => auth()->id(),
            ]);

            $producto->agregarUnidades($cantidad, $series, $movimiento->id);

            return redirect()->route('inventory.movimientos.index')
                ->with('status', "Entrada {$movimiento->folio} registrada correctamente.");
        });
    }

    private function borrarEvidencias(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }
    }

    public function show(InventoryMovement $movimiento): View
    {
        $movimiento->load(['creator', 'seriales']);

        return view('structure.gestion_Inventario.entrada_salida.show', [
            'movimiento' => $movimiento,
            'producto' => $movimiento->producto(),
        ]);
    }

    /**
     * Elimina un movimiento. Solo tiene sentido para entradas que todavía
     * no se hayan vendido (si alguna de sus unidades ya se vendió, borrar
     * el movimiento dejaría esa venta sin origen).
     */
    public function destroy(Request $request, InventoryMovement $movimiento): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = auth()->user();

        // Si el usuario ya configuró un PIN de aprobación, se usa ese; si
        // no, se acepta su contraseña normal para no dejarlo sin forma de
        // confirmar la eliminación.
        $valido = $user->approval_pin_hash
            ? $user->checkApprovalPin($request->password)
            : \Illuminate\Support\Facades\Hash::check($request->password, $user->password);

        if (! $valido) {
            return back()->withErrors(['password' => 'PIN o contraseña incorrecta.']);
        }

        if ($movimiento->seriales()->where('vendido', true)->exists()) {
            return back()->withErrors(['password' => 'No se puede eliminar: alguna unidad de esta entrada ya se vendió.']);
        }

        DB::transaction(function () use ($movimiento) {
            $unidades = $movimiento->seriales()->get();
            $productoIds = $unidades->pluck('producto_id')->unique();

            $movimiento->seriales()->delete();

            Producto::whereIn('id', $productoIds)->get()->each->recalcularStock();

            $this->borrarEvidencias($movimiento->evidence_paths ?? []);
            $movimiento->delete();
        });

        return redirect()->route('inventory.movimientos.index')->with('status', "Movimiento {$movimiento->folio} eliminado.");
    }
}
