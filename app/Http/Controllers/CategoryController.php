<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Congress;
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
            'congresses' => Congress::query()->with('category')->latest()->get(),
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
            ->with('status_category', 'Categoría guardada correctamente.');
    }

    /**
     * Muestra el formulario de edición de una categoría.
     */
    public function edit(Category $category): View
    {
        return view('structure.Configuracion.Catalogos.Categoria.u_categorias', [
            'category' => $category,
        ]);
    }

    /**
     * Actualiza una categoría.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id],
        ]);

        $category->update($data);

        return redirect()->route('configuracion.catalogos.index')
            ->with('status_category', 'Categoría actualizada correctamente.');
    }

    /**
     * Muestra la confirmación para eliminar una categoría.
     */
    public function delete(Category $category): View
    {
        return view('structure.Configuracion.Catalogos.Categoria.d_categoriaas', [
            'category' => $category,
        ]);
    }

    /**
     * Elimina una categoría.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('configuracion.catalogos.index')
            ->with('status_category', 'Categoría eliminada correctamente.');
    }
}
