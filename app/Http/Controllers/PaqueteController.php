<?php

namespace App\Http\Controllers;

use App\Models\Paquete;
use App\Models\Producto;
use App\Services\Inventario\Paquetes;
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
     *
     * Si viene con ?productos[]=1&productos[]=2 (desde la selección múltiple
     * en Productos), esos renglones ya llegan precargados con cantidad 1
     * en vez de partir de un formulario vacío.
     */
    public function create(Request $request): View
    {
        $preseleccionados = Producto::query()
            ->whereIn('id', $request->input('productos', []))
            ->get();

        return view('structure.gestion_Inventario.paquetes.create', [
            'productos' => Producto::query()->orderBy('tipo_equipo')->get(),
            'preseleccionados' => $preseleccionados,
            // [producto_id => cantidad] de los que ya deben quedar marcados.
            'seleccionados' => $preseleccionados->mapWithKeys(fn (Producto $p) => [$p->id => 1])->all(),
        ]);
    }

    /**
     * Guarda un nuevo paquete.
     */
    public function store(Request $request, Paquetes $paquetes): RedirectResponse
    {
        $data = $request->validate(Paquetes::REGLAS);

        $paquetes->guardar($data);

        return redirect()->route('inventory.paquetes.index')->with('status', 'Paquete guardado correctamente.');
    }

    /**
     * Muestra el formulario de edición de paquete.
     */
    public function edit(Paquete $paquete): View
    {
        $paquete->load('productos');

        return view('structure.gestion_Inventario.paquetes.edit', [
            'paquete' => $paquete,
            'productos' => Producto::query()->orderBy('tipo_equipo')->get(),
            // [producto_id => cantidad] ya guardada en este paquete.
            'seleccionados' => $paquete->productos->pluck('pivot.cantidad', 'id')->all(),
        ]);
    }

    /**
     * Actualiza un paquete existente.
     */
    public function update(Request $request, Paquete $paquete, Paquetes $paquetes): RedirectResponse
    {
        $data = $request->validate(Paquetes::REGLAS);

        $paquetes->guardar($data, $paquete);

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
