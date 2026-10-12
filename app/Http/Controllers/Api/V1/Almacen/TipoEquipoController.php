<?php

namespace App\Http\Controllers\Api\V1\Almacen;

use App\Http\Controllers\Api\V1\Almacen\Concerns\BorradoSeguro;
use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Almacen\TipoEquipoResource;
use App\Models\EquipmentType;
use App\Services\Almacen\CatalogoDeEquipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catálogo de equipo, primer nivel: tipos (tipo → subtipo → marca →
 * modelo). Los nombres se guardan en mayúsculas.
 *
 * @tags Almacén · Catálogo
 */
class TipoEquipoController extends Controller
{
    use BorradoSeguro, ListadoApi;

    public function __construct(private readonly CatalogoDeEquipo $catalogo)
    {
    }

    /**
     * Listar tipos de equipo.
     *
     * Con cuántos subtipos tiene cada uno.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate(['buscar' => ['nullable', 'string', 'max:100']]);

        $query = EquipmentType::withCount('subtypes')
            ->when($filtros['buscar'] ?? null, fn ($q, string $texto) => $q->where('name', 'like', "%{$texto}%"));

        $this->ordenar($query, $request, ['id', 'name', 'created_at'], 'name');

        return TipoEquipoResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Ver un tipo de equipo.
     *
     * Con sus subtipos.
     */
    public function show(EquipmentType $tipo): TipoEquipoResource
    {
        return new TipoEquipoResource($tipo->load(['subtypes' => fn ($q) => $q->orderBy('name')])->loadCount('subtypes'));
    }

    /**
     * Crear un tipo de equipo.
     *
     * `requiere_emplayado` dice si lo que se venda de este tipo se emplaya
     * antes de salir (lo usa la orden de salida).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(CatalogoDeEquipo::reglasTipo(), CatalogoDeEquipo::MENSAJES_TIPO);

        $tipo = $this->catalogo->crearTipo($data['name'], $request->boolean('requiere_emplayado'));

        return (new TipoEquipoResource($tipo->refresh()))
            ->additional(['message' => 'Tipo de equipo guardado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar un tipo de equipo.
     *
     * Si no se manda `requiere_emplayado`, queda en falso (como la casilla
     * de la web).
     */
    public function update(Request $request, EquipmentType $tipo): TipoEquipoResource
    {
        $data = $request->validate(CatalogoDeEquipo::reglasTipo($tipo), CatalogoDeEquipo::MENSAJES_TIPO);

        $this->catalogo->actualizarTipo($tipo, $data['name'], $request->boolean('requiere_emplayado'));

        return (new TipoEquipoResource($tipo->refresh()))->additional(['message' => 'Tipo de equipo actualizado correctamente.']);
    }

    /**
     * Eliminar un tipo de equipo.
     *
     * Se borran en cascada sus subtipos y, con ellos, sus modelos. Si algo
     * más lo usa y la base no deja borrarlo, 409.
     */
    public function destroy(EquipmentType $tipo): JsonResponse
    {
        $this->borrarSinRomper(fn () => $tipo->delete(), 'No se puede eliminar: hay registros que todavía usan este tipo de equipo.');

        return response()->json(['message' => 'Tipo de equipo eliminado correctamente.']);
    }
}
