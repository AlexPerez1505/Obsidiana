<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Http\Controllers\Concerns\ManejaSeriesDeProducto;
use App\Models\Producto;
use App\Models\ProductoSerial;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductoController extends Controller
{
    use ManejaSeriesDeProducto, ConstruyeCatalogoEquipo;

    /**
     * Lista los productos del inventario.
     */
    public function index(Request $request): View
    {
        $query = Producto::query()->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('tipo_equipo', 'like', "%{$search}%")
                    ->orWhere('marca', 'like', "%{$search}%")
                    ->orWhere('modelo', 'like', "%{$search}%")
                    ->orWhere('no_serie', 'like', "%{$search}%");
            });
        }

        $productos = $query->paginate(20)->withQueryString();

        return view('structure.gestion_Inventario.productos.index', [
            'productos' => $productos,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Muestra el formulario de creación de producto.
     */
    public function create(): View
    {
        return view('structure.gestion_Inventario.productos.create', [
            'productoOptions' => $this->productoOptions(),
            'catalogo' => $this->catalogoEquipo(),
        ]);
    }

    /**
     * Guarda un nuevo producto.
     *
     * Cada unidad que se da de alta queda como su propia fila en
     * producto_seriales, con su propio número de serie. Si el modelo ya
     * existe en el inventario, no se crea otra fila de producto: las
     * unidades nuevas se agregan a la que ya hay.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $series = $this->parsearSeries($request->input('series_texto', ''));
        $series = $this->autocompletarSecuencia($series, (int) $data['stock']);

        if ($series->isNotEmpty() && $series->count() !== (int) $data['stock']) {
            return back()->withInput()->withErrors([
                'series_texto' => 'Capturaste '.$series->count()." número(s) de serie, pero el stock dice {$data['stock']}. Deben coincidir, o deja el campo vacío si no vas a registrar series.",
            ]);
        }

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        $cantidad = (int) $data['stock'];
        unset($data['stock'], $data['no_serie']);

        try {
            try {
                return $this->crearOAcumular($data, $cantidad, $series->all());
            } catch (QueryException $e) {
                if (! $this->esErrorDeDuplicado($e)) {
                    throw $e;
                }

                // Dos altas del mismo modelo casi al mismo tiempo: la otra
                // ganó la carrera. Se reintenta una vez contra la fila que
                // ya quedó creada.
                return $this->crearOAcumular($data, $cantidad, $series->all());
            }
        } catch (QueryException $e) {
            if (! $this->esErrorDeDuplicado($e)) {
                throw $e;
            }

            // Si sigue fallando, ya no es una carrera: el serial de verdad
            // está repetido para ese producto.
            return back()->withInput()->withErrors([
                'series_texto' => 'Uno de esos números de serie ya existe para este producto. Revísalos y vuelve a intentar.',
            ]);
        }
    }

    /**
     * Busca (con bloqueo) si el modelo ya tiene fila en productos; si es
     * así, le suma las unidades nuevas, y si no, crea la fila. El lock
     * evita que dos altas simultáneas del mismo modelo creen dos filas.
     */
    private function crearOAcumular(array $data, int $cantidad, array $series): RedirectResponse
    {
        return DB::transaction(function () use ($data, $cantidad, $series) {
            $existente = $data['equipment_model_id']
                ? Producto::where('equipment_model_id', $data['equipment_model_id'])->lockForUpdate()->first()
                : null;

            if ($existente) {
                return $this->sumarAExistente($existente, $data, $cantidad, $series);
            }

            $producto = Producto::create($data + ['stock' => 0]);
            $producto->agregarUnidades($cantidad, $series);

            return redirect()->route('inventory.productos.index')->with('status', 'Producto guardado correctamente.');
        });
    }

    /**
     * Acumula unidades nuevas en el registro que ya existe de ese modelo,
     * en vez de duplicar la fila.
     */
    private function sumarAExistente(Producto $existente, array $data, int $cantidad, array $series): RedirectResponse
    {
        if (! empty($data['imagen_path']) && $existente->imagen_path) {
            Storage::disk('public')->delete($existente->imagen_path);
        }

        $existente->precio = $data['precio'];
        $existente->descripcion = $data['descripcion'] ?? $existente->descripcion;
        $existente->proveedor = $data['proveedor'] ?? $existente->proveedor;

        if (! empty($data['imagen_path'])) {
            $existente->imagen_path = $data['imagen_path'];
        }

        $existente->save();
        $existente->agregarUnidades($cantidad, $series);

        return redirect()->route('inventory.productos.index')
            ->with('status', "Se agregaron {$cantidad} unidad(es) al stock existente de ".trim($existente->marca.' '.$existente->modelo).'.');
    }

    /**
     * Muestra el formulario de edición de producto.
     */
    public function edit(Producto $producto): View
    {
        return view('structure.gestion_Inventario.productos.edit', [
            'producto' => $producto,
            'seriales' => $producto->seriales()->orderByDesc('id')->get(),
            'productoOptions' => $this->productoOptions(),
            'catalogo' => $this->catalogoEquipo(),
        ]);
    }

    /**
     * Actualiza los datos generales de un producto existente. El stock y
     * los números de serie se manejan aparte, unidad por unidad.
     */
    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $data = $this->validated($request);
        unset($data['stock'], $data['no_serie']);

        if ($request->hasFile('imagen')) {
            // Delete old image if exists
            if ($producto->imagen_path) {
                Storage::disk('public')->delete($producto->imagen_path);
            }
            $data['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto->update($data);

        return redirect()->route('inventory.productos.index')->with('status', 'Producto actualizado correctamente.');
    }

    /**
     * Elimina un producto.
     */
    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->delete();

        return redirect()->route('inventory.productos.index')->with('status', 'Producto eliminado correctamente.');
    }

    /**
     * Agrega unidades (con o sin serial) a un producto ya existente, desde
     * la pantalla de edición.
     */
    public function agregarSeriales(Request $request, Producto $producto): RedirectResponse
    {
        $data = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1'],
            'series_texto' => ['nullable', 'string'],
        ]);

        $series = $this->parsearSeries($data['series_texto'] ?? '');
        $series = $this->autocompletarSecuencia($series, (int) $data['cantidad']);

        if ($series->isNotEmpty() && $series->count() !== (int) $data['cantidad']) {
            return back()->withErrors([
                'series_texto' => 'Capturaste '.$series->count()." número(s) de serie, pero la cantidad dice {$data['cantidad']}. Deben coincidir, o deja el campo vacío.",
            ]);
        }

        try {
            $producto->agregarUnidades((int) $data['cantidad'], $series->all());
        } catch (QueryException $e) {
            if (! $this->esErrorDeDuplicado($e)) {
                throw $e;
            }

            return back()->withErrors([
                'series_texto' => 'Uno de esos números de serie ya existe para este producto. Revísalos y vuelve a intentar.',
            ]);
        }

        return back()->with('status', "Se agregaron {$data['cantidad']} unidad(es) al inventario.");
    }

    /**
     * Quita del inventario una unidad que nunca se llegó a vender (por
     * ejemplo, si se dio de alta por error).
     */
    public function eliminarSerial(ProductoSerial $serial): RedirectResponse
    {
        if ($serial->vendido) {
            return back()->withErrors(['serial' => 'Esa unidad ya se vendió, no se puede eliminar del inventario.']);
        }

        $producto = $serial->producto;
        $serial->delete();
        $producto->recalcularStock();

        return back()->with('status', 'Unidad eliminada del inventario.');
    }

    /**
     * Si ese modelo ya está registrado, se manda lo que ya se sabe de él
     * (precio, descripción, proveedor...) para autocompletar el alta. El
     * stock y el número de serie no se tocan: son propios de cada unidad.
     */
    public function buscarPorModelo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'equipment_model_id' => ['required', 'exists:equipment_models,id'],
        ]);

        $producto = Producto::where('equipment_model_id', $data['equipment_model_id'])
            ->latest()
            ->first();

        if (! $producto) {
            return response()->json(['existe' => false]);
        }

        return response()->json([
            'existe' => true,
            'precio' => (float) $producto->precio,
            'descripcion' => $producto->descripcion,
            'proveedor' => $producto->proveedor,
            'imagen' => $producto->imagen_path ? asset('storage/'.$producto->imagen_path) : null,
            'stock_actual' => (int) $producto->stock,
            'no_serie_sugerido' => $this->siguienteNumeroSerie($producto->id),
        ]);
    }

    /**
     * El siguiente serial de un modelo es consecutivo al último que se
     * registró: si la última pieza es 23A12345, la que sigue es 23A12346,
     * luego 23A12347... Funciona con cualquier prefijo (letras, año,
     * guiones) siempre que el serial termine en dígitos; si no, no se
     * sugiere nada y se deja el campo en blanco para llenarlo a mano.
     */
    private function siguienteNumeroSerie(int $productoId): ?string
    {
        $ultimo = ProductoSerial::where('producto_id', $productoId)
            ->whereNotNull('no_serie')
            ->where('no_serie', '!=', '')
            ->pluck('no_serie')
            ->map(function ($serie) {
                if (! preg_match('/^(.*?)(\d+)$/', $serie, $m)) {
                    return null;
                }

                return ['prefijo' => $m[1], 'numero' => (int) $m[2], 'ancho' => strlen($m[2])];
            })
            ->filter()
            ->sortByDesc(fn ($s) => $s['numero'])
            ->first();

        if ($ultimo === null) {
            return null;
        }

        $siguiente = $ultimo['numero'] + 1;

        return $ultimo['prefijo'].str_pad((string) $siguiente, $ultimo['ancho'], '0', STR_PAD_LEFT);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'equipment_type_id' => ['required', 'exists:equipment_types,id'],
            'subtype_id' => ['nullable', 'exists:subtypes,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'equipment_model_id' => ['nullable', 'exists:equipment_models,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'no_serie' => ['nullable', 'string', 'max:255'],
            'imagen' => ['nullable', 'image', 'max:5120'], // max 5MB
        ]);
    }

    private function productoOptions(): array
    {
        $options = collect(['tipo_equipo', 'subtipo', 'marca', 'modelo', 'proveedor'])
            ->mapWithKeys(function (string $column): array {
                return [
                    $column => Producto::query()
                        ->whereNotNull($column)
                        ->where($column, '!=', '')
                        ->distinct()
                        ->orderBy($column)
                        ->pluck($column)
                        ->values()
                        ->all(),
                ];
            })
            ->all();

        $subtypesByType = Producto::query()
            ->whereNotNull('tipo_equipo')
            ->where('tipo_equipo', '!=', '')
            ->whereNotNull('subtipo')
            ->where('subtipo', '!=', '')
            ->orderBy('tipo_equipo')
            ->orderBy('subtipo')
            ->get(['tipo_equipo', 'subtipo'])
            ->groupBy('tipo_equipo')
            ->map(fn ($rows) => $rows->pluck('subtipo')->unique()->values()->all())
            ->all();

        $brandsByTypeAndSubtype = Producto::query()
            ->whereNotNull('tipo_equipo')
            ->where('tipo_equipo', '!=', '')
            ->whereNotNull('subtipo')
            ->where('subtipo', '!=', '')
            ->whereNotNull('marca')
            ->where('marca', '!=', '')
            ->orderBy('tipo_equipo')
            ->orderBy('subtipo')
            ->orderBy('marca')
            ->get(['tipo_equipo', 'subtipo', 'marca'])
            ->groupBy('tipo_equipo')
            ->map(fn ($typeRows) => $typeRows
                ->groupBy('subtipo')
                ->map(fn ($subtypeRows) => $subtypeRows->pluck('marca')->unique()->values()->all())
                ->all())
            ->all();

        return array_merge($options, [
            'subtypes_by_type' => $subtypesByType,
            'brands_by_type_and_subtype' => $brandsByTypeAndSubtype,
        ]);
    }

    private function productCatalog(): array
    {
        $catalogPath = resource_path('data/product_catalog.json');

        if (! is_file($catalogPath)) {
            return [];
        }

        $catalog = json_decode((string) file_get_contents($catalogPath), true);

        return is_array($catalog) ? $catalog : [];
    }
}
