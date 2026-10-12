<?php

namespace App\Http\Controllers\Api\V1\Administracion;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Administracion\VehiculoResource;
use App\Models\Vehicle;
use App\Services\Administracion\Vehiculos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Vehículos de la empresa. Solo administradores, como en la web.
 * Combustible y mantenimiento están en Logística (`/flota`).
 *
 * @tags Administración · Vehículos
 */
class VehiculoController extends Controller
{
    use ListadoApi;

    public function __construct(private Vehiculos $vehiculos)
    {
    }

    /**
     * Listar vehículos.
     *
     * Filtros: `buscar` (placa, marca, modelo o VIN) y `status` (active,
     * maintenance, inactive).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,maintenance,inactive'],
        ]);

        $query = $this->vehiculos->consulta($request->query('buscar'), $request->query('status'));

        return VehiculoResource::collection(
            $this->ordenar($query, $request, ['id', 'plate_number', 'brand', 'year', 'created_at'], '-created_at')
                ->paginate($this->porPagina($request))
        );
    }

    /**
     * Ver un vehículo.
     */
    public function show(Vehicle $vehicle): VehiculoResource
    {
        return new VehiculoResource($vehicle);
    }

    /**
     * Registrar vehículo.
     *
     * Multipart si lleva archivos: `photos[]` (hasta 10 imágenes de 5 MB) y
     * los documentos `circulation_card_doc`, `verification_doc`,
     * `tenancy_doc`, `insurance_doc` (JPG, PNG o PDF de 10 MB). La placa
     * no se puede repetir.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(Vehiculos::reglasAlta());

        $vehiculo = $this->vehiculos->registrar($data, $request);

        // fresh(): el estado por omisión lo pone la base.
        return (new VehiculoResource($vehiculo->fresh()))
            ->additional(['message' => 'Vehículo registrado correctamente.'])
            ->response()
            ->setStatusCode(201);
    }
}
