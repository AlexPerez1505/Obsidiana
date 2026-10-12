<?php

namespace App\Http\Controllers\Api\V1\Almacen;

use App\Http\Controllers\Api\V1\Almacen\Concerns\BorradoSeguro;
use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Almacen\SubtipoResource;
use App\Models\Subtype;
use App\Services\Almacen\CatalogoDeEquipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catálogo de equipo: subtipos, dentro de un tipo. Un subtipo no se
 * repite dentro del mismo tipo.
 *
 * @tags Almacén · Catálogo
 */
class SubtipoController extends Controller
{
    use BorradoSeguro, ListadoApi;

    public function __construct(private readonly CatalogoDeEquipo $catalogo)
    {
    }

    /**
     * Listar subtipos.
     *
     * Con `equipment_type_id`, solo los de ese tipo (la cascada de los
     * formularios).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'equipment_type_id' => ['nullable', 'integer'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Subtype::with('equipmentType')
            ->when($filtros['equipment_type_id'] ?? null, fn ($q, $id) => $q->where('equipment_type_id', $id))
            ->when($filtros['buscar'] ?? null, fn ($q, string $texto) => $q->where('name', 'like', "%{$texto}%"));

        $this->ordenar($query, $request, ['id', 'name', 'created_at'], 'name');

        return SubtipoResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Ver un subtipo.
     *
     * Con su tipo y las marcas que se ofrecen en él.
     */
    public function show(Subtype $subtipo): SubtipoResource
    {
        return new SubtipoResource($subtipo->load(['equipmentType', 'brands' => fn ($q) => $q->orderBy('name')]));
    }

    /**
     * Crear un subtipo.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_SUBTIPO);

        $subtipo = $this->catalogo->crearSubtipo($data);

        return (new SubtipoResource($subtipo->load('equipmentType')))
            ->additional(['message' => 'Subtipo guardado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar un subtipo.
     *
     * Se puede renombrar o mover a otro tipo.
     */
    public function update(Request $request, Subtype $subtipo): SubtipoResource
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_SUBTIPO);

        $this->catalogo->actualizarSubtipo($subtipo, $data);

        return (new SubtipoResource($subtipo->refresh()->load('equipmentType')))
            ->additional(['message' => 'Subtipo actualizado correctamente.']);
    }

    /**
     * Eliminar un subtipo.
     *
     * Se llevan con él sus modelos. Si algo más lo usa y la base no deja
     * borrarlo, 409.
     */
    public function destroy(Subtype $subtipo): JsonResponse
    {
        $this->borrarSinRomper(fn () => $subtipo->delete(), 'No se puede eliminar: hay registros que todavía usan este subtipo.');

        return response()->json(['message' => 'Subtipo eliminado correctamente.']);
    }
}
