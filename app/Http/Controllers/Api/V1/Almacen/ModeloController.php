<?php

namespace App\Http\Controllers\Api\V1\Almacen;

use App\Http\Controllers\Api\V1\Almacen\Concerns\BorradoSeguro;
use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Almacen\ModeloResource;
use App\Models\EquipmentModel;
use App\Services\Almacen\CatalogoDeEquipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catálogo de equipo: modelos, de una marca dentro de un subtipo. Un
 * modelo no se repite para la misma marca y subtipo.
 *
 * @tags Almacén · Catálogo
 */
class ModeloController extends Controller
{
    use BorradoSeguro, ListadoApi;

    public function __construct(private readonly CatalogoDeEquipo $catalogo)
    {
    }

    /**
     * Listar modelos.
     *
     * Con `brand_id` y `subtype_id`, solo los de esa marca en ese subtipo
     * (la cascada de los formularios).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'brand_id' => ['nullable', 'integer'],
            'subtype_id' => ['nullable', 'integer'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $query = EquipmentModel::with(['brand', 'subtype.equipmentType'])
            ->when($filtros['brand_id'] ?? null, fn ($q, $id) => $q->where('brand_id', $id))
            ->when($filtros['subtype_id'] ?? null, fn ($q, $id) => $q->where('subtype_id', $id))
            ->when($filtros['buscar'] ?? null, fn ($q, string $texto) => $q->where('name', 'like', "%{$texto}%"));

        $this->ordenar($query, $request, ['id', 'name', 'created_at'], 'name');

        return ModeloResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Ver un modelo.
     */
    public function show(EquipmentModel $modelo): ModeloResource
    {
        return new ModeloResource($modelo->load(['brand', 'subtype.equipmentType']));
    }

    /**
     * Crear un modelo.
     *
     * Dar de alta el modelo declara también que la marca se ofrece en ese
     * subtipo.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_MODELO);

        $modelo = $this->catalogo->crearModelo($data);

        return (new ModeloResource($modelo->load(['brand', 'subtype.equipmentType'])))
            ->additional(['message' => 'Modelo guardado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar un modelo.
     */
    public function update(Request $request, EquipmentModel $modelo): ModeloResource
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_MODELO);

        $this->catalogo->actualizarModelo($modelo, $data);

        return (new ModeloResource($modelo->refresh()->load(['brand', 'subtype.equipmentType'])))
            ->additional(['message' => 'Modelo actualizado correctamente.']);
    }

    /**
     * Eliminar un modelo.
     *
     * Si algo más lo usa y la base no deja borrarlo, 409.
     */
    public function destroy(EquipmentModel $modelo): JsonResponse
    {
        $this->borrarSinRomper(fn () => $modelo->delete(), 'No se puede eliminar: hay registros que todavía usan este modelo.');

        return response()->json(['message' => 'Modelo eliminado correctamente.']);
    }
}
