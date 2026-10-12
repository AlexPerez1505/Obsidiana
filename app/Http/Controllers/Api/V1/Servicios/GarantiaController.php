<?php

namespace App\Http\Controllers\Api\V1\Servicios;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Servicios\CartaGarantiaResource;
use App\Models\GarantiaDocumento;
use App\Services\Servicios\CartasDeGarantia;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Servicios
 */
class GarantiaController extends Controller
{
    use ListadoApi;

    /**
     * Cartas de garantía.
     *
     * Las cartas registradas, de la más nueva a la más vieja. Filtros:
     * `tipo_equipo` (exacto) y `buscar` (folio o nombre).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = GarantiaDocumento::query();

        if ($request->filled('tipo_equipo')) {
            $query->where('tipo_equipo', $request->query('tipo_equipo'));
        }

        if ($request->filled('buscar')) {
            $texto = '%'.$request->query('buscar').'%';
            $query->where(fn ($q) => $q->where('folio', 'like', $texto)->orWhere('nombre', 'like', $texto));
        }

        $cartas = $this->ordenar($query, $request, ['created_at', 'folio', 'nombre', 'id'], '-id')
            ->paginate($this->porPagina($request));

        return CartaGarantiaResource::collection($cartas);
    }

    /**
     * Ver una carta de garantía.
     */
    public function show(GarantiaDocumento $garantia): CartaGarantiaResource
    {
        return new CartaGarantiaResource($garantia);
    }

    /**
     * Agregar una carta de garantía.
     *
     * Multipart: `nombre`, `tipo_equipo` y hasta 20 archivos en
     * `archivos[]` (cualquier tipo, máx. 20 MB cada uno). El folio
     * (GAR-año-consecutivo) se asigna solo.
     */
    #[BodyParameter('nombre', 'Nombre de la carta.', required: true, type: 'string')]
    #[BodyParameter('tipo_equipo', 'Tipo de equipo que cubre.', required: true, type: 'string')]
    #[BodyParameter('archivos', 'Archivos de la carta (archivos[]; hasta 20, máx. 20 MB cada uno).', type: 'array')]
    public function store(Request $request, CartasDeGarantia $cartas): JsonResponse
    {
        $carta = $cartas->registrar($request);

        return (new CartaGarantiaResource($carta->refresh()))
            ->additional(['message' => 'Carta agregada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }
}
