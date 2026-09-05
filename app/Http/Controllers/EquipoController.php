<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ConstruyeCatalogoEquipo;
use App\Models\Equipo;
use App\Support\PrecioVisible;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EquipoController extends Controller
{
    use ConstruyeCatalogoEquipo;

    public function index(): View
    {
        $equipos = Equipo::latest()->get();

        return view('structure.gestion_Inventario.equipos.menu_productos', [
            'equipos' => $equipos,
        ]);
    }

    public function create(): View
    {
        // El árbol completo del catálogo viaja en la página: cambiar un
        // select no consulta al servidor.
        return view('structure.gestion_Inventario.equipos.c_productos', [
            'catalogo' => $this->catalogoEquipo(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Tipo, subtipo, marca y modelo llegan como ids del catálogo; el
        // modelo copia los nombres a las columnas de texto al guardar.
        $data = $request->validate([
            'equipment_type_id' => ['required', 'exists:equipment_types,id'],
            'subtype_id' => ['nullable', 'exists:subtypes,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'equipment_model_id' => ['nullable', 'exists:equipment_models,id'],
            // Solo el admin define precios de venta; ver PrecioVisible.
            'precio' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'activo' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('equipos', 'public');
        }

        $data['activo'] = $request->boolean('activo', true);

        // El formulario no dibuja el campo para quien no puede definir precios;
        // si aun así llegó uno, se descarta.
        if (! PrecioVisible::editable($request->user())) {
            $data['precio'] = null;
        }

        Equipo::create($data);

        return redirect()->route('inventory.equipos.index')
            ->with('status', 'Equipo registrado correctamente.');
    }
}
