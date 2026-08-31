<?php

namespace App\Http\Controllers;

use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Producto;
use App\Models\Subtype;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductoController extends Controller
{
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

        $productos = $query->get();

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
     * Si el modelo ya existe en el inventario, no se crea otra fila: se
     * suma la cantidad al stock de la que ya hay y el número de serie se
     * agrega a la lista de series de esa fila.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        $existente = $data['equipment_model_id']
            ? Producto::where('equipment_model_id', $data['equipment_model_id'])->first()
            : null;

        if ($existente) {
            return $this->sumarAExistente($existente, $data);
        }

        Producto::create($data);

        return redirect()->route('inventory.productos.index')->with('status', 'Producto guardado correctamente.');
    }

    /**
     * Acumula una nueva pieza en el registro que ya existe de ese modelo,
     * en vez de duplicar la fila.
     */
    private function sumarAExistente(Producto $existente, array $data): RedirectResponse
    {
        if (! empty($data['imagen_path']) && $existente->imagen_path) {
            Storage::disk('public')->delete($existente->imagen_path);
        }

        $series = collect(explode(',', (string) $existente->no_serie))
            ->map(fn ($s) => trim($s))
            ->filter();

        if (! empty($data['no_serie'])) {
            $series->push(trim($data['no_serie']));
        }

        $existente->stock += (int) $data['stock'];
        $existente->no_serie = $series->unique()->filter()->implode(', ');
        $existente->precio = $data['precio'];
        $existente->descripcion = $data['descripcion'] ?? $existente->descripcion;
        $existente->proveedor = $data['proveedor'] ?? $existente->proveedor;

        if (! empty($data['imagen_path'])) {
            $existente->imagen_path = $data['imagen_path'];
        }

        $existente->save();

        return redirect()->route('inventory.productos.index')
            ->with('status', "Se sumaron {$data['stock']} unidades al stock existente de ".trim($existente->marca.' '.$existente->modelo).'.');
    }

    /**
     * Muestra el formulario de edición de producto.
     */
    public function edit(Producto $producto): View
    {
        return view('structure.gestion_Inventario.productos.edit', [
            'producto' => $producto,
            'productoOptions' => $this->productoOptions(),
            'catalogo' => $this->catalogoEquipo(),
        ]);
    }

    /**
     * Actualiza un producto existente.
     */
    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $data = $this->validated($request);

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
            'stock_actual' => (int) Producto::where('equipment_model_id', $data['equipment_model_id'])->sum('stock'),
            'no_serie_sugerido' => $this->siguienteNumeroSerie($data['equipment_model_id']),
        ]);
    }

    /**
     * El siguiente serial de un modelo es consecutivo al último que se
     * registró: si la primera pieza es 9803289028, la que sigue es
     * 9803289029, luego 9803289030... Solo funciona sobre seriales
     * puramente numéricos; si el último tiene letras o formato raro, no se
     * sugiere nada y se deja el campo en blanco para llenarlo a mano.
     */
    private function siguienteNumeroSerie(int $equipmentModelId): ?string
    {
        $ultimo = Producto::where('equipment_model_id', $equipmentModelId)
            ->whereNotNull('no_serie')
            ->where('no_serie', '!=', '')
            ->get(['no_serie'])
            ->filter(fn ($p) => ctype_digit($p->no_serie))
            ->map(fn ($p) => $p->no_serie)
            ->sortByDesc(fn ($serie) => (int) $serie)
            ->first();

        if ($ultimo === null) {
            return null;
        }

        // Se conserva el mismo número de dígitos (con ceros a la izquierda).
        $siguiente = (string) ((int) $ultimo + 1);

        return str_pad($siguiente, strlen($ultimo), '0', STR_PAD_LEFT);
    }

    /**
     * Arbol completo del catalogo para los selects encadenados.
     *
     * Cabe en la pagina, asi que el formulario no necesita ir al servidor
     * cada vez que se cambia un select.
     */
    private function catalogoEquipo(): array
    {
        $subtipos = Subtype::with('brands')->orderBy('name')->get();

        return [
            'tipos' => EquipmentType::orderBy('name')->get(['id', 'name'])->all(),

            'subtipos' => $subtipos
                ->groupBy('equipment_type_id')
                ->map(fn ($lista) => $lista->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values())
                ->all(),

            'marcas' => $subtipos
                ->mapWithKeys(fn ($s) => [
                    $s->id => $s->brands->sortBy('name')->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
                ])
                ->all(),

            // La llave es "subtipo-marca": el modelo depende de las dos cosas.
            'modelos' => EquipmentModel::orderBy('name')->get(['id', 'name', 'brand_id', 'subtype_id'])
                ->groupBy(fn ($m) => $m->subtype_id . '-' . $m->brand_id)
                ->map(fn ($lista) => $lista->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])->values())
                ->all(),
        ];
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

        return array_merge($options, [
            'subtypes_by_type' => $subtypesByType,
        ]);
    }
}
