<?php

namespace App\Http\Controllers\Api\V1\Almacen;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Almacen\EquipoResource;
use App\Models\Brand;
use App\Models\Equipment;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;
use App\Services\Almacen\CatalogoDeEquipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Alta rápida de equipo (Configuración > Tipos de equipo): se escriben los
 * cuatro nombres (tipo, subtipo, marca, modelo); lo que ya existe se
 * reutiliza y lo que no, se crea.
 *
 * @tags Almacén · Catálogo
 */
class EquipoController extends Controller
{
    use ListadoApi;

    /**
     * Listar equipos dados de alta.
     *
     * Cada combinación tipo + subtipo + marca + modelo con sus
     * descripciones. `totales` trae los números de la pantalla.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'equipment_type_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
        ]);

        $query = Equipment::with(['equipmentType', 'subtype', 'brand', 'equipmentModel'])
            ->when($filtros['equipment_type_id'] ?? null, fn ($q, $id) => $q->where('equipment_type_id', $id))
            ->when($filtros['brand_id'] ?? null, fn ($q, $id) => $q->where('brand_id', $id));

        $this->ordenar($query, $request, ['id', 'created_at'], '-id');

        return EquipoResource::collection($query->paginate($this->porPagina($request)))
            ->additional(['totales' => [
                'totalTypes' => EquipmentType::count(),
                'totalSubtypes' => Subtype::count(),
                'totalBrands' => Brand::count(),
                'totalModels' => EquipmentModel::count(),
            ]]);
    }

    /**
     * Dar de alta un equipo.
     *
     * Con los nombres de tipo, subtipo, marca y modelo (y descripciones
     * opcionales de hasta 120 letras).
     */
    public function store(Request $request, CatalogoDeEquipo $catalogo): JsonResponse
    {
        $data = $request->validate(CatalogoDeEquipo::REGLAS_ALTA_RAPIDA);

        $equipo = $catalogo->altaRapida($data);

        return (new EquipoResource($equipo->load(['equipmentType', 'subtype', 'brand', 'equipmentModel'])))
            ->additional(['message' => 'Equipo guardado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Subtipos de un tipo, por nombre.
     *
     * Para el formulario de alta rápida: con el nombre del tipo escrito,
     * los subtipos que ya tiene. Vacío si el tipo no existe.
     */
    public function subtipos(Request $request): JsonResponse
    {
        $request->validate(['equipment_type_name' => ['nullable', 'string', 'max:255']]);

        $tipo = EquipmentType::where('name', $request->input('equipment_type_name'))->first();

        return response()->json([
            'data' => $tipo ? $tipo->subtypes()->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    /**
     * Modelos de una marca, por nombre.
     *
     * Para el formulario de alta rápida. Vacío si la marca no existe.
     */
    public function modelos(Request $request): JsonResponse
    {
        $request->validate(['brand_name' => ['nullable', 'string', 'max:255']]);

        $marca = Brand::where('name', $request->input('brand_name'))->first();

        return response()->json([
            'data' => $marca ? $marca->equipmentModels()->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }
}
