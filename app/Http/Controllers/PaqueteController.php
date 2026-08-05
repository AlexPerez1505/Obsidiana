<?php

namespace App\Http\Controllers;

use App\Models\Paquete;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaqueteController extends Controller
{
    /**
     * Lista los paquetes armados a partir de productos.
     */
    public function index(): View
    {
        return view('structure.gestion_Inventario.paquetes.index', [
            'paquetes' => Paquete::with('productos')->latest()->get(),
        ]);
    }

    /**
     * Muestra el formulario de creación de paquete.
     */
    public function create(): View
    {
        return view('structure.gestion_Inventario.paquetes.create', [
            'productos' => Producto::query()->orderBy('tipo_equipo')->get(),
        ]);
    }

    /**
     * Guarda un nuevo paquete.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'productos' => ['required', 'array'],
            'productos.*.id' => ['required', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $paquete = Paquete::create(['nombre' => $data['nombre']]);

        $syncData = [];
        foreach ($data['productos'] as $producto) {
            $syncData[$producto['id']] = ['cantidad' => $producto['cantidad']];
        }
        $paquete->productos()->sync($syncData);

        return redirect()->route('inventory.paquetes.index')->with('status', 'Paquete guardado correctamente.');
    }

    /**
     * Muestra el formulario de edición de paquete.
     */
    public function edit(Paquete $paquete): View
    {
        return view('structure.gestion_Inventario.paquetes.edit', [
            'paquete' => $paquete->load('productos'),
            'productos' => Producto::query()->orderBy('tipo_equipo')->get(),
        ]);
    }

    /**
     * Actualiza un paquete existente.
     */
    public function update(Request $request, Paquete $paquete): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'productos' => ['required', 'array'],
            'productos.*.id' => ['required', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $paquete->update(['nombre' => $data['nombre']]);

        $syncData = [];
        foreach ($data['productos'] as $producto) {
            $syncData[$producto['id']] = ['cantidad' => $producto['cantidad']];
        }
        $paquete->productos()->sync($syncData);

        return redirect()->route('inventory.paquetes.index')->with('status', 'Paquete actualizado correctamente.');
    }

    /**
     * Elimina un paquete.
     */
    public function destroy(Paquete $paquete): RedirectResponse
    {
        $paquete->delete();

        return redirect()->route('inventory.paquetes.index')->with('status', 'Paquete eliminado correctamente.');
    }
}
