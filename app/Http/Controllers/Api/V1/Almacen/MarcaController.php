<?php

namespace App\Http\Controllers\Api\V1\Almacen;

use App\Http\Controllers\Api\V1\Almacen\Concerns\BorradoSeguro;
use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Almacen\MarcaResource;
use App\Models\Brand;
use App\Services\Almacen\CatalogoDeEquipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de equipo: marcas. Una marca es global (el nombre no se
 * repite), pero se declara en qué subtipos se ofrece; por eso dar de alta
 * y quitar una marca siempre es "en un subtipo".
 *
 * @tags Almacén · Catálogo
 */
class MarcaController extends Controller
{
    use BorradoSeguro, ListadoApi;

    public function __construct(private readonly CatalogoDeEquipo $catalogo)
    {
    }

    /**
     * Listar marcas.
     *
     * Con `subtype_id`, solo las que se ofrecen en ese subtipo (la cascada
     * de los formularios).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'subtype_id' => ['nullable', 'integer'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Brand::with('subtypes')
            ->when($filtros['subtype_id'] ?? null, fn ($q, $id) => $q->whereHas('subtypes', fn ($s) => $s->where('subtypes.id', $id)))
            ->when($filtros['buscar'] ?? null, fn ($q, string $texto) => $q->where('name', 'like', "%{$texto}%"));

        $this->ordenar($query, $request, ['id', 'name', 'created_at'], 'name');

        return MarcaResource::collection($query->paginate($this->porPagina($request)));
    }

    /**
     * Ver una marca.
     *
     * Con los subtipos donde se ofrece.
     */
    public function show(Brand $marca): MarcaResource
    {
        return new MarcaResource($marca->load('subtypes'));
    }

    /**
     * Dar de alta una marca en un subtipo.
     *
     * Si la marca ya existe (en otro subtipo) se reutiliza; si ya estaba en
     * ese subtipo, 422.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_MARCA);

        $marca = $this->catalogo->crearMarca($data['subtype_id'], $data['name']);

        return (new MarcaResource($marca->load('subtypes')))
            ->additional(['message' => 'Marca guardada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Editar una marca.
     *
     * Renombra la marca en todo el sistema. Si `subtype_id` es distinto de
     * `subtype_anterior`, la mueve de subtipo y sus modelos se mudan con
     * ella.
     */
    public function update(Request $request, Brand $marca): MarcaResource
    {
        $data = $request->validate(CatalogoDeEquipo::reglasActualizarMarca($marca), CatalogoDeEquipo::MENSAJES_ACTUALIZAR_MARCA);

        $this->catalogo->actualizarMarca($marca, $data['name'], $data['subtype_id'], $data['subtype_anterior']);

        return (new MarcaResource($marca->refresh()->load('subtypes')))
            ->additional(['message' => 'Marca actualizada correctamente.']);
    }

    /**
     * Quitar una marca de un subtipo.
     *
     * `subtype_id` (en la query o en el cuerpo) dice de cuál. Se borran sus
     * modelos en ese subtipo; si ya no se ofrece en ninguno, la marca se
     * retira del catálogo.
     */
    public function destroy(Request $request, Brand $marca): JsonResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_QUITAR_MARCA);

        $this->borrarSinRomper(
            fn () => DB::transaction(fn () => $this->catalogo->quitarMarca($marca, $data['subtype_id'])),
            'No se puede quitar: hay registros que todavía usan esta marca o sus modelos.'
        );

        return response()->json(['message' => 'Marca eliminada correctamente.']);
    }
}
