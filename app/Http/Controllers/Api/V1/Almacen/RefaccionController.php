<?php

namespace App\Http\Controllers\Api\V1\Almacen;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Almacen\RefaccionResource;
use App\Models\Refaccion;
use App\Services\Almacen\Refacciones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Refacciones sueltas: qué hay, cuántas y con qué equipo son compatibles.
 *
 * @tags Almacén · Refacciones
 */
class RefaccionController extends Controller
{
    use ListadoApi;

    public function __construct(private readonly Refacciones $refacciones)
    {
    }

    /**
     * Listar refacciones.
     *
     * Las más recientes primero. `resumen` trae los números de la
     * pantalla: total, piezas en stock, subtipos distintos y cuántas tienen
     * compatibilidad capturada.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            // Nombre, subtipo o compatibilidad.
            'buscar' => ['nullable', 'string', 'max:100'],
            'subtype' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Refaccion::query()
            ->when($filtros['buscar'] ?? null, fn ($q, string $texto) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$texto}%")
                ->orWhere('subtype', 'like', "%{$texto}%")
                ->orWhere('compatible_with', 'like', "%{$texto}%")))
            ->when($filtros['subtype'] ?? null, fn ($q, string $s) => $q->where('subtype', $s));

        $this->ordenar($query, $request, ['id', 'name', 'subtype', 'stock', 'created_at'], '-created_at');

        return RefaccionResource::collection($query->paginate($this->porPagina($request)))
            ->additional(['resumen' => $this->refacciones->resumen()]);
    }

    /**
     * Catálogos del formulario.
     *
     * Los subtipos del catálogo para elegir el de la refacción.
     */
    public function catalogos(): JsonResponse
    {
        return response()->json(['data' => ['subtypes' => $this->refacciones->subtipos()]]);
    }

    /**
     * Ver una refacción.
     */
    public function show(Refaccion $refaccion): RefaccionResource
    {
        return new RefaccionResource($refaccion);
    }

    /**
     * Registrar una refacción.
     *
     * `subtype` es el nombre del subtipo de equipo (ver catalogos). Sin
     * `stock`, se registra en 0.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(Refacciones::reglas('subtype'));

        $refaccion = $this->refacciones->crear($data);

        return (new RefaccionResource($refaccion->refresh()))
            ->additional(['message' => 'Refacción guardada correctamente.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Eliminar una refacción.
     */
    public function destroy(Refaccion $refaccion): JsonResponse
    {
        $refaccion->delete();

        return response()->json(['message' => 'Refacción eliminada correctamente.']);
    }
}
