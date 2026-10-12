<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\IncidenciaResource;
use App\Models\AttendanceModality;
use App\Services\Asistencia\ConfiguracionDeAsistencia;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

/**
 * Incidencias del día (en la base, "modalidades"): trabajo en campo, home
 * office, comisión, vacaciones, incapacidad o permiso de un empleado en un
 * día o rango. Justifican que no cheque; al crearlas o borrarlas, los días
 * que ya pasaron se recalculan (una falta queda justificada, o regresa).
 *
 * @tags RH · Asistencia
 */
class IncidenciaController extends Controller
{
    use ListadoApi;

    public function __construct(private ConfiguracionDeAsistencia $config)
    {
    }

    /**
     * Listar incidencias.
     *
     * Las que tocan el mes pedido (por defecto el actual), de la más
     * reciente a la más antigua. Con `desde`/`hasta` se usa ese rango en
     * lugar del mes.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // Mes (Y-m). Por defecto, el actual.
            'mes' => ['nullable', 'date_format:Y-m'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            // ID del empleado (users.id).
            'empleado' => ['nullable', 'integer'],
            // campo | home_office | comision | vacaciones | incapacidad | permiso
            'type' => ['nullable', 'in:'.implode(',', array_keys(AttendanceModality::TIPOS))],
        ]);

        if ($request->filled('desde') || $request->filled('hasta')) {
            $desde = $request->filled('desde') ? $request->date('desde')->toDateString() : null;
            $hasta = $request->filled('hasta') ? $request->date('hasta')->toDateString() : null;
        } else {
            $mes = $request->filled('mes') ? Carbon::parse($request->query('mes').'-01') : ServicioDeAsistencia::diaLocal(now());
            $desde = $mes->copy()->startOfMonth()->toDateString();
            $hasta = $mes->copy()->endOfMonth()->toDateString();
        }

        // Se cruzan con el rango: empiezan antes de que termine y terminan después de que empiece
        $q = AttendanceModality::with(['user', 'creador'])
            ->when($desde, fn ($q) => $q->whereDate('date_to', '>=', $desde))
            ->when($hasta, fn ($q) => $q->whereDate('date_from', '<=', $hasta))
            ->when($request->filled('empleado'), fn ($q) => $q->where('user_id', $request->integer('empleado')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')));

        $this->ordenar($q, $request, ['date_from', 'date_to', 'id'], '-date_from');

        return IncidenciaResource::collection($q->paginate($this->porPagina($request)));
    }

    /**
     * Ver una incidencia.
     */
    public function show(AttendanceModality $modalidad): IncidenciaResource
    {
        return new IncidenciaResource($modalidad->load(['user', 'creador']));
    }

    /**
     * Registrar incidencia.
     *
     * `requires_fingerprint: true` indica que, aun así, debe checar (por
     * ejemplo, una comisión de medio día).
     */
    public function store(Request $request): IncidenciaResource
    {
        $data = $request->validate(ConfiguracionDeAsistencia::reglasIncidencia(), ConfiguracionDeAsistencia::mensajesIncidencia());

        $m = $this->config->crearIncidencia($data, $request->user()->id);

        /** @status 201 */
        return (new IncidenciaResource($m->load(['user', 'creador'])))
            ->additional(['message' => 'Incidencia registrada para '.$m->user->name.'.']);
    }

    /**
     * Eliminar incidencia.
     *
     * Sin la justificación, los días que ya pasaron vuelven a evaluarse y
     * pueden quedar como falta.
     */
    public function destroy(AttendanceModality $modalidad): JsonResponse
    {
        $this->config->eliminarIncidencia($modalidad);

        return response()->json(['message' => 'Incidencia eliminada y días recalculados.']);
    }
}
