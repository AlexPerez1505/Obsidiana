<?php

namespace App\Http\Controllers;

use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Producto;
use App\Models\Subtype;
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
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        Producto::create($data);

        return redirect()->route('inventory.productos.index')->with('status', 'Producto guardado correctamente.');
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
