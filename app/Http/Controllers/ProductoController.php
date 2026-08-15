<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Producto;
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
        $all = Equipment::with(['equipmentType', 'subtype', 'brand', 'equipmentModel'])
            ->orderBy('name')
            ->get();

        $equipos = $all;

        if ($search = $request->get('search')) {
            $term = mb_strtolower($search);
            $equipos = $equipos->filter(function ($equipo) use ($term) {
                return str_contains(mb_strtolower($equipo->name ?? ''), $term)
                    || str_contains(mb_strtolower($equipo->serial_number ?? ''), $term)
                    || str_contains(mb_strtolower($equipo->equipmentType?->name ?? ''), $term)
                    || str_contains(mb_strtolower($equipo->brand?->name ?? ''), $term)
                    || str_contains(mb_strtolower($equipo->equipmentModel?->name ?? ''), $term);
            });
        }

        if ($tipo = $request->get('tipo')) {
            $equipos = $equipos->filter(fn ($equipo) => $equipo->equipmentType?->name === $tipo);
        }

        if ($marca = $request->get('marca')) {
            $equipos = $equipos->filter(fn ($equipo) => $equipo->brand?->name === $marca);
        }

        $tipos = $all->pluck('equipmentType.name')->filter()->unique()->sort()->values();
        $marcas = $all->pluck('brand.name')->filter()->unique()->sort()->values();

        $productos = Producto::whereIn('equipment_id', $equipos->pluck('id'))
            ->get()
            ->keyBy('equipment_id');

        return view('structure.gestion_Inventario.productos.index', [
            'equipos' => $equipos->values(),
            'productos' => $productos,
            'tipos' => $tipos,
            'marcas' => $marcas,
            'filters' => $request->only('search', 'tipo', 'marca'),
        ]);
    }

    /**
     * Muestra el formulario de creación de producto.
     */
    public function create(): View
    {
        return view('structure.gestion_Inventario.productos.create', [
            'productoOptions' => $this->productoOptions(),
            'equipmentOptions' => $this->equipmentOptions(),
            'productCatalog' => $this->productCatalog(),
        ]);
    }

    /**
     * Guarda un nuevo producto.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data = $this->syncFromEquipment($data);

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        Producto::create($data);

        return redirect()->route('inventory.productos.index')->with('status', 'Producto guardado correctamente.');
    }

    /**
     * Muestra el formulario de edición de producto.
     */
    public function edit(Request $request, Producto $producto): View|RedirectResponse
    {
        if ($request->get('pin') !== '123456') {
            return redirect()->route('inventory.productos.index')->with('error', 'PIN incorrecto. No se puede editar.');
        }

        return view('structure.gestion_Inventario.productos.edit', [
            'producto' => $producto,
            'equipmentOptions' => $this->equipmentOptions(),
        ]);
    }

    /**
     * Actualiza un producto existente.
     */
    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $data = $this->validated($request);
        $data = $this->syncFromEquipment($data);

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
    public function destroy(Request $request, Producto $producto): RedirectResponse
    {
        if (! \Illuminate\Support\Facades\Hash::check($request->input('pin'), auth()->user()->password)) {
            return back()->with('error', 'PIN incorrecto. El producto no se eliminó.');
        }

        $producto->delete();

        return redirect()->route('inventory.productos.index')->with('status', 'Producto eliminado correctamente.');
    }

    /**
     * Crea o actualiza un producto a partir de un equipo con precio y stock.
     */
    public function sync(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipment_id' => ['required', 'integer', 'exists:equipment,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
        ]);

        $equipo = Equipment::with(['equipmentType', 'subtype', 'brand', 'equipmentModel'])
            ->findOrFail($data['equipment_id']);

        Producto::updateOrCreate(
            ['equipment_id' => $data['equipment_id']],
            [
                'tipo_equipo' => $equipo->equipmentType?->name ?? $equipo->name,
                'subtipo' => $equipo->subtype?->name,
                'marca' => $equipo->brand?->name,
                'modelo' => $equipo->equipmentModel?->name,
                'no_serie' => $equipo->serial_number,
                'descripcion' => $equipo->description,
                'proveedor' => $equipo->supplier,
                'precio' => $data['precio'],
                'stock' => $data['stock'],
            ]
        );

        return redirect()->route('inventory.productos.index')->with('status', 'Producto guardado correctamente.');
    }

    /**
     * Equipos del inventario disponibles para convertir en producto.
     */
    private function equipmentOptions(): array
    {
        return Equipment::with(['equipmentType', 'subtype', 'brand', 'equipmentModel'])
            ->orderBy('name')
            ->get()
            ->map(fn (Equipment $equipo) => [
                'id' => $equipo->id,
                'code' => $equipo->code,
                'name' => $equipo->name,
                'tipo_equipo' => $equipo->equipmentType?->name ?? $equipo->name,
                'subtipo' => $equipo->subtype?->name ?? '',
                'marca' => $equipo->brand?->name ?? '',
                'modelo' => $equipo->equipmentModel?->name ?? '',
                'proveedor' => $equipo->supplier ?? '',
                'no_serie' => $equipo->serial_number ?? '',
                'descripcion' => $equipo->description ?? '',
            ])
            ->values()
            ->toArray();
    }

    /**
     * Si el producto viene de un equipo del inventario, toma sus datos
     * del catalogo para evitar duplicados escritos a mano.
     */
    private function syncFromEquipment(array $data): array
    {
        if (empty($data['equipment_id'])) {
            return $data;
        }

        $equipo = Equipment::with(['equipmentType', 'subtype', 'brand', 'equipmentModel'])
            ->find($data['equipment_id']);

        if (!$equipo) {
            return $data;
        }

        $data['tipo_equipo'] = $equipo->equipmentType?->name ?? $equipo->name;
        $data['subtipo'] = $equipo->subtype?->name;
        $data['marca'] = $equipo->brand?->name;
        $data['modelo'] = $equipo->equipmentModel?->name;
        $data['no_serie'] = $equipo->serial_number;
        $data['proveedor'] = $equipo->supplier;

        return $data;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'equipment_id' => ['nullable', 'integer', 'exists:equipment,id'],
            'tipo_equipo' => ['required_without:equipment_id', 'nullable', 'string', 'max:255'],
            'subtipo' => ['nullable', 'string', 'max:255'],
            'marca' => ['required', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
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
