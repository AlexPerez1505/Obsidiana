<?php

namespace App\Http\Controllers\Api\V1\Inventario;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Inventario\PaqueteResource;
use App\Models\Paquete;
use App\Services\Inventario\Paquetes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Paquetes armados con productos del inventario (lo que luego se cotiza y
 * se vende junto). Cada producto lleva cuántas piezas entran al paquete.
 *
 * @tags Inventario · Paquetes
 */
class PaqueteController extends Controller
{
    use ListadoApi;

    /**
     * Listar paquetes.
     *
     * Con sus productos. `buscar` busca en el nombre.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['buscar' => ['nullable', 'string', 'max:100']]);

        $query = Paquete::query()->with('productos')
            ->when($request->query('buscar'), fn ($q, $b) => $q->where('nombre', 'like', "%{$b}%"));

        $this->ordenar($query, $request, ['nombre', 'created_at', 'updated_at', 'id'], '-created_at');

        return PaqueteResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Ver un paquete.
     */
    public function show(Paquete $paquete): PaqueteResource
    {
        return new PaqueteResource($paquete->load('productos'));
    }

    /**
     * Armar un paquete.
     *
     * `productos` es la lista de `{id, cantidad}` que lo forman.
     */
    public function store(Request $request, Paquetes $paquetes): JsonResponse
    {
        $data = $request->validate(Paquetes::REGLAS);

        $paquete = $paquetes->guardar($data);

        return (new PaqueteResource($paquete->load('productos')))
            ->additional(['message' => 'Paquete guardado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar un paquete.
     *
     * `productos` reemplaza la lista completa: los que no vengan se quitan.
     */
    public function update(Request $request, Paquete $paquete, Paquetes $paquetes): PaqueteResource
    {
        $data = $request->validate(Paquetes::REGLAS);

        $paquetes->guardar($data, $paquete);

        return (new PaqueteResource($paquete->fresh()->load('productos')))
            ->additional(['message' => 'Paquete actualizado correctamente.']);
    }

    /**
     * Eliminar un paquete.
     *
     * Los productos no se tocan; solo se deshace el paquete.
     */
    public function destroy(Paquete $paquete): JsonResponse
    {
        $paquete->delete();

        return response()->json(['message' => 'Paquete eliminado correctamente.']);
    }
}
