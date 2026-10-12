<?php

namespace App\Http\Controllers\Api\V1\Servicios;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Servicios\ServicioDetalleResource;
use App\Http\Resources\Api\V1\Servicios\ServicioResource;
use App\Http\Resources\Api\V1\Servicios\TecnicoExternoResource;
use App\Models\Brand;
use App\Models\Equipo;
use App\Models\EquipmentType;
use App\Models\ExternalTechnician;
use App\Models\Service;
use App\Models\User;
use App\Services\Servicios\OrdenesDeServicio;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

/**
 * @tags Servicios
 */
class ServicioController extends Controller
{
    use ListadoApi;

    /** Lo que trae cada renglón de los listados. */
    private const RELACIONES_LISTA = ['customer', 'currentStep'];

    public function __construct(private OrdenesDeServicio $ordenes)
    {
    }

    /**
     * Historial de servicios.
     *
     * Todas las órdenes, de la más nueva a la más vieja, con cliente y paso
     * actual. Filtros: `status` (registrado, en_progreso, validado,
     * entregado, cancelado), `service_type` (interno, externo),
     * `customer_id`, `buscar` (folio, serie o nombre del cliente) y
     * `desde`/`hasta` (fecha de registro, Y-m-d). Orden por `created_at`,
     * `service_number`, `status` o `id`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['nullable', 'in:registrado,en_progreso,validado,entregado,cancelado'],
            'service_type' => ['nullable', 'in:interno,externo'],
            'customer_id' => ['nullable', 'integer'],
            'buscar' => ['nullable', 'string', 'max:100'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $query = $this->filtrar(Service::with(self::RELACIONES_LISTA), $request);

        $servicios = $this->ordenar($query, $request, ['created_at', 'service_number', 'status', 'id'], '-id')
            ->paginate($this->porPagina($request));

        return ServicioResource::collection($servicios);
    }

    /**
     * Órdenes por aprobar.
     *
     * Las que siguen en `registrado`: esperan que alguien con permiso de
     * crear órdenes las apruebe o las deniegue.
     */
    public function aprobaciones(Request $request): AnonymousResourceCollection
    {
        $servicios = Service::where('status', 'registrado')
            ->with(self::RELACIONES_LISTA)
            ->latest()
            ->orderByDesc('id')
            ->paginate($this->porPagina($request));

        return ServicioResource::collection($servicios);
    }

    /**
     * Ver una orden.
     *
     * La orden completa: cliente, técnicos, equipo con evidencias,
     * refacciones, firma y cada paso del seguimiento.
     */
    public function show(Service $service): ServicioDetalleResource
    {
        return new ServicioDetalleResource($this->conDetalle($service));
    }

    /**
     * Registrar una orden.
     *
     * Mismos campos que el formulario web (multipart si llevas evidencias).
     * El tipo se indica con `mantenimiento_interno=1` o
     * `mantenimiento_externo=1`; por comodidad también se acepta
     * `service_type` (interno|externo). Para externo el cliente es
     * obligatorio. `refacciones` es una lista de `{nombre, cantidad,
     * precio_unitario}`. Las evidencias (`evidencia_1..3`, imágenes, y
     * `evidencia_video`) pesan máx. 10 MB cada una.
     *
     * La orden nace en `registrado`, con folio `OS-<id>`, su primer paso
     * pendiente y QR válido 24 horas.
     */
    #[BodyParameter('service_type', 'interno o externo (atajo de mantenimiento_interno / mantenimiento_externo).', type: 'string', example: 'interno')]
    #[BodyParameter('mantenimiento_interno', '"1" si es mantenimiento interno.', type: 'string')]
    #[BodyParameter('mantenimiento_externo', '"1" si es mantenimiento externo.', type: 'string')]
    #[BodyParameter('customer_id', 'Cliente (obligatorio en externos).', type: 'integer')]
    #[BodyParameter('internal_technician_id', 'Técnico interno (solo internos).', type: 'integer')]
    #[BodyParameter('external_technician_id', 'Técnico externo (solo externos).', type: 'integer')]
    #[BodyParameter('tipo_equipo', 'Tipo de equipo.', required: true, type: 'string', example: 'GASTROSCOPIO')]
    #[BodyParameter('subtipo', 'Subtipo.', type: 'string')]
    #[BodyParameter('marca', 'Marca.', type: 'string')]
    #[BodyParameter('modelo', 'Modelo.', type: 'string')]
    #[BodyParameter('serie', 'Número de serie.', type: 'string')]
    #[BodyParameter('descripcion_equipo', 'Falla o diagnóstico de entrada.', type: 'string')]
    #[BodyParameter('observaciones', 'Observaciones.', type: 'string')]
    #[BodyParameter('firma', 'Firma de quien entrega (data URL de imagen).', type: 'string')]
    #[BodyParameter('evidencia_1', 'Foto (jpeg, png, webp; máx. 10 MB). También evidencia_2 y evidencia_3.', type: 'string', format: 'binary')]
    #[BodyParameter('evidencia_video', 'Video (mp4, mov, m4v; máx. 10 MB).', type: 'string', format: 'binary')]
    #[BodyParameter('refacciones', 'Lista de {nombre, cantidad, precio_unitario}.', type: 'array')]
    public function store(Request $request): JsonResponse
    {
        $tipo = $request->input('service_type');
        if (in_array($tipo, ['interno', 'externo'], true)
            && ! $request->filled('mantenimiento_interno') && ! $request->filled('mantenimiento_externo')) {
            $request->merge(['mantenimiento_'.$tipo => '1']);
        }

        $service = $this->ordenes->registrar($request, $request->user()->id);

        return (new ServicioDetalleResource($this->conDetalle($service->refresh())))
            ->additional(['message' => "Servicio {$service->service_number} creado."])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Aprobar una orden.
     *
     * Pasa la orden de `registrado` a `en_progreso`. Si ya se decidió
     * antes, responde 409.
     */
    public function aprobar(Service $service): ServicioResource
    {
        abort_unless($this->ordenes->aprobar($service), 409, 'La orden no puede aprobarse en su estado actual.');

        return (new ServicioResource($service->load(self::RELACIONES_LISTA)))
            ->additional(['message' => "Orden {$service->service_number} aprobada."]);
    }

    /**
     * Denegar una orden.
     *
     * Pasa la orden de `registrado` a `cancelado`. Si ya se decidió antes,
     * responde 409.
     */
    public function denegar(Service $service): ServicioResource
    {
        abort_unless($this->ordenes->denegar($service), 409, 'La orden no puede denegarse en su estado actual.');

        return (new ServicioResource($service->load(self::RELACIONES_LISTA)))
            ->additional(['message' => "Orden {$service->service_number} denegada."]);
    }

    /**
     * Renovar el QR.
     *
     * Solo mantenimientos externos: genera un QR nuevo válido 24 horas y
     * el anterior deja de servir (hay que volver a imprimir la hoja). Para
     * internos responde 409.
     */
    public function renovarQr(Service $service): ServicioResource
    {
        abort_unless($this->ordenes->renovarQr($service), 409, 'Solo se pueden renovar QR de mantenimientos externos.');

        return (new ServicioResource($service->load(self::RELACIONES_LISTA)))
            ->additional(['message' => 'QR renovado. Válido por 24 horas.']);
    }

    /**
     * Catálogos para una orden nueva.
     *
     * Lo que ofrece el formulario de nueva orden: tipos de equipo, marcas,
     * equipos del catálogo (para prellenar tipo, marca, modelo y serie),
     * técnicos internos (con cuántos servicios traen en progreso) y
     * técnicos externos activos. Los clientes se buscan en `/clientes`.
     */
    public function catalogos(): JsonResponse
    {
        $internos = $this->ordenes->soloTecnicos($this->ordenes->candidatosATecnicoInterno());

        $activos = Service::where('status', 'en_progreso')
            ->whereIn('internal_technician_id', $internos->modelKeys())
            ->selectRaw('internal_technician_id, COUNT(*) as total')
            ->groupBy('internal_technician_id')
            ->pluck('total', 'internal_technician_id');

        return response()->json(['data' => [
            'tipos_equipo' => EquipmentType::orderBy('name')->get(['id', 'name']),
            'marcas' => Brand::orderBy('name')->get(['id', 'name']),
            'equipos' => Equipo::all()->map(fn (Equipo $e) => [
                'id' => $e->id,
                'externo_interno' => $e->externo_interno,
                'tipo' => $e->tipo,
                'subtipo' => $e->subtipo,
                'marca' => $e->marca,
                'modelo' => $e->modelo,
                'serie' => $e->serie,
                'descripcion' => $e->descripcion,
                'observaciones' => $e->observaciones,
                'imagen_url' => $e->imagen ? Storage::disk('public')->url($e->imagen) : null,
            ])->values(),
            'tecnicos_internos' => $internos->map(fn (User $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'servicios_en_progreso' => (int) ($activos[$t->id] ?? 0),
            ])->values(),
            'tecnicos_externos' => TecnicoExternoResource::collection(
                ExternalTechnician::where('is_active', true)->orderBy('name')->get()
            ),
        ]]);
    }

    private function filtrar(Builder $query, Request $request): Builder
    {
        foreach (['status', 'service_type', 'customer_id'] as $campo) {
            if ($request->filled($campo)) {
                $query->where($campo, $request->query($campo));
            }
        }

        if ($request->filled('buscar')) {
            $texto = '%'.$request->query('buscar').'%';
            $query->where(function (Builder $q) use ($texto) {
                $q->where('service_number', 'like', $texto)
                    ->orWhereHas('serviceEquipment', fn (Builder $e) => $e->where('serial_number', 'like', $texto))
                    ->orWhereHas('customer', fn (Builder $c) => $c->whereRaw("CONCAT_WS(' ', nombre, apellido) LIKE ?", [$texto]));
            });
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->query('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->query('hasta'));
        }

        return $query;
    }

    private function conDetalle(Service $service): Service
    {
        return $service->load([
            'customer', 'currentStep', 'serviceEquipment', 'internalTechnician', 'externalTechnician',
            'registeredBy', 'spareParts', 'serviceTrackings.serviceStep', 'serviceTrackings.performer',
        ]);
    }
}
