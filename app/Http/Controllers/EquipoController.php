<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EquipoController extends Controller
{
    public function index(): View
    {
        $equipos = Equipo::latest()->get();

        return view('structure.gestion_Inventario.equipos.menu_productos', [
            'equipos' => $equipos,
        ]);
    }

    public function create(): View
    {
        return view('structure.gestion_Inventario.equipos.c_productos');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:4096'],
            'activo' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('equipos', 'public');
        }

        $data['activo'] = $request->boolean('activo', true);

        Equipo::create($data);

        return redirect()->route('inventory.equipos.index')
            ->with('status', 'Equipo registrado correctamente.');
    }
}
