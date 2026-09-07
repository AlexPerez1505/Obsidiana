<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Http\Controllers\Concerns\ManejaSeriesDeProducto;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Support\GeneradorDeSeries;
use App\Support\PrecioVisible;
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
        $query = Producto::query()->with('serialesDisponibles')->latest();

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

        // Sin precio nuevo se conserva el que ya tenía el modelo.
        if (($data['precio'] ?? null) !== null) {
            $existente->precio = $data['precio'];
        }

        $existente->descripcion = $data['descripcion'] ?? $existente->descripcion;

        if (! empty($data['imagen_path'])) {
            $existente->imagen_path = $data['imagen_path'];
        }

        // Una vez marcado como serializado, se queda así: no se le quita
        // la marca solo porque una entrada posterior no trajo el checkbox
        // marcado.
        if (! empty($data['es_serializado'])) {
            $existente->es_serializado = true;
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
     * (precio, descripción...) para autocompletar el alta. El
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

        /*
        | El precio solo viaja si a quien pregunta le toca verlo. Esconder
        | el campo en el formulario no bastaría: la respuesta de este
        | endpoint se lee desde las herramientas del navegador.
        */
        $vePrecio = PrecioVisible::para($request->user());

        return response()->json([
            'existe' => true,
            've_precio' => $vePrecio,
            'tiene_precio' => $producto->precio !== null,
            'precio' => $vePrecio && $producto->precio !== null ? (float) $producto->precio : null,
            'precio_texto' => PrecioVisible::texto($producto, $request->user()),
            'descripcion' => $producto->descripcion,
            'imagen' => $producto->imagen_path ? asset('storage/'.$producto->imagen_path) : null,
            'stock_actual' => (int) $producto->stock,
            'es_serializado' => (bool) $producto->es_serializado,
            'no_serie_sugerido' => $this->sugerirSiguienteSerial($producto->id),
        ]);
    }

    /**
     * Ficha del producto: el kardex.
     *
     * Responde de un vistazo las tres preguntas de almacén: qué entró,
     * cuándo entró y cuánto hay hoy. El stock no se guarda como un número
     * suelto, se cuenta a partir de las piezas, así que aquí se enseña de
     * dónde sale.
     */
    public function show(Producto $producto): View
    {
        $producto->load(['seriales.entrada', 'seriales.procesos', 'seriales.ventaItem.venta']);

        $piezas = $producto->seriales->sortByDesc('id')->values();

        // La bitácora del producto: entradas y salidas, más reciente arriba.
        $movimientos = InventoryMovement::where('item_type', InventoryMovement::ITEM_PRODUCT)
            ->where('item_id', $producto->id)
            ->with('creator')
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->get();

        return view('structure.gestion_Inventario.productos.show', [
            'producto' => $producto,
            'piezas' => $piezas,
            'movimientos' => $movimientos,
            'conteos' => [
                'total' => $piezas->count(),
                'disponibles' => $piezas->filter->vendible()->count(),
                'en_proceso' => $piezas->where('vendido', false)
                    ->filter(fn ($p) => in_array($p->estado, ProductoSerial::NO_VENDIBLES, true))
                    ->count(),
                'vendidas' => $piezas->where('vendido', true)->count(),
            ],
        ]);
    }

    /**
     * Arma series propias para equipo que no trae serial de fábrica.
     *
     * La serie se construye con el catálogo elegido (tipo, subtipo, marca,
     * modelo) más un consecutivo que no choque con las que ya existen.
     */
    public function generarSeries(Request $request): JsonResponse
    {
        $data = $request->validate([
            'equipment_type_id' => ['nullable', 'exists:equipment_types,id'],
            'subtype_id' => ['nullable', 'exists:subtypes,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'equipment_model_id' => ['nullable', 'exists:equipment_models,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:5000'],
        ]);

        $prefijo = GeneradorDeSeries::prefijo(
            $data['equipment_type_id'] ?? null,
            $data['subtype_id'] ?? null,
            $data['brand_id'] ?? null,
            $data['equipment_model_id'] ?? null,
        );

        $series = GeneradorDeSeries::generar($prefijo, (int) $data['cantidad']);

        return response()->json([
            'prefijo' => $prefijo,
            'series' => $series,
        ]);
    }

    /**
     * Corrige el número de serie y/o la foto de una unidad ya guardada.
     * Pide PIN de aprobación (o contraseña, si el usuario no tiene PIN
     * configurado), igual que las demás acciones destructivas/sensibles
     * del sistema, porque altera un dato que ya quedó como evidencia.
     */
    public function actualizarSerial(Request $request, ProductoSerial $serial): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
            'no_serie' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = auth()->user();

        $valido = $user->approval_pin_hash
            ? $user->checkApprovalPin($data['password'])
            : \Illuminate\Support\Facades\Hash::check($data['password'], $user->password);

        if (! $valido) {
            return back()->withErrors(['password' => 'PIN o contraseña incorrecta.']);
        }

        $nuevaSerie = trim((string) ($data['no_serie'] ?? '')) ?: null;
        $disco = config('filesystems.fotos_disk', 'public');

        try {
            DB::transaction(function () use ($request, $serial, $nuevaSerie, $disco) {
                $cambios = ['editado_por' => auth()->id()];

                if ($nuevaSerie !== $serial->no_serie) {
                    $cambios['no_serie'] = $nuevaSerie;
                }

                if ($request->hasFile('foto')) {
                    $fotoAnterior = $serial->foto_path;
                    $cambios['foto_path'] = $request->file('foto')->store('productos/seriales', $disco);

                    if ($fotoAnterior) {
                        Storage::disk($disco)->delete($fotoAnterior);
                    }
                }

                $serial->update($cambios);
                $serial->producto->recalcularStock();
            });
        } catch (QueryException $e) {
            if (! $this->esErrorDeDuplicado($e)) {
                throw $e;
            }

            return back()->withErrors(['no_serie' => 'Ese número de serie ya existe para este producto.']);
        }

        return back()->with('status', 'Unidad actualizada correctamente.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'equipment_type_id' => ['required', 'exists:equipment_types,id'],
            'subtype_id' => ['nullable', 'exists:subtypes,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'equipment_model_id' => ['nullable', 'exists:equipment_models,id'],
            'precio' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'no_serie' => ['nullable', 'string', 'max:255'],
            'imagen' => ['nullable', 'image', 'max:5120'], // max 5MB
            'es_serializado' => ['sometimes', 'boolean'],
        ]);

        // El formulario no dibuja el campo para quien no puede definir precios;
        // si aun así llegó uno, se descarta en vez de confiar en él.
        if (! PrecioVisible::editable($request->user())) {
            $data['precio'] = null;
        }

        return $data;
    }

    private function productoOptions(): array
    {
        $options = collect(['tipo_equipo', 'subtipo', 'marca', 'modelo'])
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

        return array_merge($options, [
            'subtypes_by_type' => $subtypesByType,
        ]);
    }
}
