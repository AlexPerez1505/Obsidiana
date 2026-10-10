<?php

namespace App\Http\Controllers\Api\V1\Almacen;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Almacen\CategoriaResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Categorías del catálogo (las que se le ponen a un congreso).
 *
 * Ojo al borrar: en la base, los congresos de una categoría se borran con
 * ella, igual que en la web.
 *
 * @tags Almacén · Catálogo
 */
class CategoriaController extends Controller
{
    use ListadoApi;

    /**
     * Listar categorías.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate(['buscar' => ['nullable', 'string', 'max:100']]);

        $query = Category::query()
            ->when($filtros['buscar'] ?? null, fn ($q, string $texto) => $q->where('nombre', 'like', "%{$texto}%"));

        $this->ordenar($query, $request, ['id', 'nombre', 'created_at'], '-created_at');

        return CategoriaResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Ver una categoría.
     */
    public function show(Category $categoria): CategoriaResource
    {
        return new CategoriaResource($categoria);
    }

    /**
     * Crear una categoría.
     *
     * El nombre no se puede repetir.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('categorias', 'nombre')],
        ]);

        $categoria = Category::create(['nombre' => $data['nombre']]);

        return (new CategoriaResource($categoria))
            ->additional(['message' => 'Categoría guardada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Renombrar una categoría.
     */
    public function update(Request $request, Category $categoria): CategoriaResource
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('categorias', 'nombre')->ignore($categoria->id)],
        ]);

        $categoria->update(['nombre' => $data['nombre']]);

        return (new CategoriaResource($categoria))->additional(['message' => 'Categoría actualizada correctamente.']);
    }

    /**
     * Eliminar una categoría.
     *
     * Se llevan con ella los congresos que la tienen.
     */
    public function destroy(Category $categoria): JsonResponse
    {
        $categoria->delete();

        return response()->json(['message' => 'Categoría eliminada correctamente.']);
    }
}
