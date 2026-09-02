<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Congress;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;
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
        $subtypes = Subtype::with(['equipmentType', 'brands'])
            ->orderBy('name')
            ->get();

        // Una fila por pareja marca + subtipo: asi se administra la marca
        // dentro del subtipo donde se ofrece.
        $brandLinks = $subtypes->flatMap(fn (Subtype $subtype) => $subtype->brands->map(fn (Brand $brand) => [
            'brand' => $brand,
            'subtype' => $subtype,
        ]))->sortBy(fn (array $fila) => $fila['brand']->name)->values();

        return view('structure.Configuracion.Catalogos.menu_catalogos', [
            'categories' => Category::query()->latest()->get(),
            'congresses' => Congress::query()->with('category')->latest()->get(),

            'equipmentTypes' => EquipmentType::withCount('subtypes')->orderBy('name')->get(),
            'subtypes' => $subtypes,
            'brandLinks' => $brandLinks,
            'equipmentModels' => EquipmentModel::with(['brand', 'subtype.equipmentType'])
                ->orderBy('name')
                ->get(),
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
            'name' => ['required', 'string', 'max:255', 'unique:categorias,nombre'],
        ]);

        Category::create(['nombre' => $data['name']]);

        return redirect()->route('configuracion.catalogos.index')
            ->with('status', 'Categoría guardada correctamente.');
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
            'name' => ['required', 'string', 'max:255', 'unique:categorias,nombre,' . $category->id],
        ]);

        $category->update(['nombre' => $data['name']]);

        return redirect()->route('configuracion.catalogos.index')
            ->with('status', 'Categoría actualizada correctamente.');
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
            ->with('status', 'Categoría eliminada correctamente.');
    }
}
