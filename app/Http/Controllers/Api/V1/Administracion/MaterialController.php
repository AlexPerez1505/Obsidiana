<?php

namespace App\Http\Controllers\Api\V1\Administracion;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Administracion\SolicitudMaterialResource;
use App\Models\MaterialRequest;
use App\Services\Administracion\SolicitudesDeMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Solicitudes de material (papelería, limpieza, herramientas…).
 *
 * Como en la web, cualquier usuario con acceso pide material y ve sus
 * solicitudes; el administrador las ve todas y las aprueba o rechaza.
 *
 * @tags Administración · Materiales
 */
class MaterialController extends Controller
{
    use ListadoApi;

    public function __construct(private SolicitudesDeMaterial $solicitudes)
    {
    }

    /**
     * Listar solicitudes.
     *
     * Las propias (todas, si es administrador), de la más reciente a la
     * más vieja. Filtro opcional `status` (borrador, pendiente, aprobada,
     * rechazada, entregada). `meta` trae las categorías y urgencias que
     * acepta el alta y cuántas siguen pendientes.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['status' => ['nullable', 'in:'.implode(',', array_keys(SolicitudesDeMaterial::ESTADOS))]]);

        $query = $this->solicitudes->visiblesPara($request->user());
        $pendientes = (clone $query)->where('status', MaterialRequest::STATUS_PENDING)->count();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return SolicitudMaterialResource::collection($query->paginate($this->porPagina($request)))
            ->additional(['meta' => [
                'pendientes' => $pendientes,
                'categorias' => SolicitudesDeMaterial::CATEGORIAS,
                'urgencias' => SolicitudesDeMaterial::URGENCIAS,
                'estados' => SolicitudesDeMaterial::ESTADOS,
            ]]);
    }

    /**
     * Ver una solicitud.
     *
     * Solo la propia, o cualquiera si es administrador.
     */
    public function show(Request $request, MaterialRequest $materialRequest): SolicitudMaterialResource
    {
        abort_unless($this->solicitudes->esVisiblePara($materialRequest, $request->user()), 403, 'Esta solicitud la hizo otra persona.');

        return new SolicitudMaterialResource($materialRequest->load(['requester', 'reviewer']));
    }

    /**
     * Pedir material.
     *
     * Queda pendiente de revisión con un folio nuevo (SOL-0001…).
     * `urgency`: Normal, Urgente o Programada.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(SolicitudesDeMaterial::reglasAlta());

        $solicitud = $this->solicitudes->registrar($data, $request->user(), 'api');

        return (new SolicitudMaterialResource($solicitud->load('requester')))
            ->additional(['message' => 'Solicitud enviada a revision.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Aprobar o rechazar.
     *
     * Solo administradores. `decision`: approve o reject. Al aprobar se
     * autoriza la cantidad pedida.
     */
    public function revisar(Request $request, MaterialRequest $materialRequest): SolicitudMaterialResource
    {
        $data = $request->validate(SolicitudesDeMaterial::reglasRevision());

        $aprobada = $this->solicitudes->revisar($materialRequest, $data, $request->user());

        return (new SolicitudMaterialResource($materialRequest->load(['requester', 'reviewer'])))
            ->additional(['message' => $aprobada ? 'Solicitud aprobada.' : 'Solicitud rechazada.']);
    }
}
