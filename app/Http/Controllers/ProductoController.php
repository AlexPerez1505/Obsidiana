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
            'equipmentOptions' => $this->equipmentOptions(),
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
    public function edit(Producto $producto): View
    {
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
    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->delete();

        return redirect()->route('inventory.productos.index')->with('status', 'Producto eliminado correctamente.');
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
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'no_serie' => ['nullable', 'string', 'max:255'],
            'imagen' => ['nullable', 'image', 'max:5120'], // max 5MB
        ]);
    }
}
