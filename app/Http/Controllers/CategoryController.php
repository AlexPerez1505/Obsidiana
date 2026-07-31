<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CongressEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Muestra el listado de categorías.
     */
    public function index(): View
    {
        return view('structure.Configuracion.Catalogos.menu_catalogos', [
            'categories' => Category::query()->latest()->get(),
            'congresses' => CongressEvent::query()->with('category')->latest()->get(),
        ]);
    }

    /**
     * Muestra el formulario de creación de categorías.
     */
    public function create(): View
    {
        return view('structure.Configuracion.Catalogos.Categoria.c_categorias');
    }

    /**
     * Guarda una nueva categoría.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        Category::create($data);

        return redirect()->route('configuracion.catalogos.index')
            ->with('status', 'Categoría guardada correctamente.');
    }
}
