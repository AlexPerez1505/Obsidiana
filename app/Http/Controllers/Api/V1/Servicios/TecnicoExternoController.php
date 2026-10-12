<?php

namespace App\Http\Controllers\Api\V1\Servicios;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Servicios\TecnicoExternoResource;
use App\Models\ExternalTechnician;
use App\Services\Servicios\OrdenesDeServicio;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Servicios
 */
class TecnicoExternoController extends Controller
{
    use ListadoApi;

    /**
     * Técnicos externos.
     *
     * Los talleres y técnicos externos activos, por nombre (los que ofrece
     * el formulario de nueva orden). `buscar` filtra por nombre, empresa o
     * especialidad.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ExternalTechnician::where('is_active', true);

        if ($request->filled('buscar')) {
            $texto = '%'.$request->query('buscar').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $texto)
                ->orWhere('company', 'like', $texto)
                ->orWhere('specialty', 'like', $texto));
        }

        $tecnicos = $this->ordenar($query, $request, ['name', 'created_at', 'id'], 'name')
            ->paginate($this->porPagina($request));

        return TecnicoExternoResource::collection($tecnicos);
    }

    /**
     * Dar de alta un técnico externo.
     *
     * Alta rápida, como desde el formulario de la orden. `photo` es una
     * imagen opcional de hasta 2 MB (multipart).
     */
    #[BodyParameter('name', 'Nombre del técnico o taller.', required: true, type: 'string')]
    #[BodyParameter('phone', 'Teléfono: números, espacios y + - ( ).', type: 'string', example: '+52 (477) 123-4567')]
    #[BodyParameter('email', 'Correo.', type: 'string', format: 'email')]
    #[BodyParameter('company', 'Empresa.', type: 'string')]
    #[BodyParameter('specialty', 'Especialidad.', type: 'string')]
    #[BodyParameter('address', 'Dirección.', type: 'string')]
    #[BodyParameter('location', 'Ciudad o ubicación.', type: 'string')]
    #[BodyParameter('description', 'Notas.', type: 'string')]
    #[BodyParameter('photo', 'Foto o logo (imagen, máx. 2 MB).', type: 'string', format: 'binary')]
    public function store(Request $request, OrdenesDeServicio $ordenes): JsonResponse
    {
        $tecnico = $ordenes->registrarTecnicoExterno($request);

        return (new TecnicoExternoResource($tecnico->refresh()))
            ->additional(['message' => 'Técnico externo registrado.'])
            ->response()
            ->setStatusCode(201);
    }
}
