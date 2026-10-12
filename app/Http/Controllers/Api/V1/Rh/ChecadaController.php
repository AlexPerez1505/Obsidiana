<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\ChecadaResource;
use App\Models\AttendancePunch;
use App\Models\User;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Checadas crudas del lector y checadas manuales. Una checada nunca se
 * edita ni se borra: si está mal se anula y queda en el historial.
 *
 * @tags RH · Asistencia
 */
class ChecadaController extends Controller
{
    use ListadoApi;

    public function __construct(private ServicioDeAsistencia $asistencia)
    {
    }

    /**
     * Listar checadas.
     *
     * Por defecto las del día de hoy (fecha local), incluidas las anuladas,
     * de la más reciente a la más antigua. Con `desde`/`hasta` se consulta un
     * rango de días en lugar de un solo día.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // Día laboral (Y-m-d). Se ignora si viene desde o hasta.
            'fecha' => ['nullable', 'date'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            // ID del empleado (users.id).
            'empleado' => ['nullable', 'integer'],
            'dispositivo' => ['nullable', 'integer'],
            'metodo' => ['nullable', 'in:fingerprint,manual'],
            // incluir (por defecto) | excluir | solo
            'anuladas' => ['nullable', 'in:incluir,excluir,solo'],
        ]);

        $q = AttendancePunch::with(['user', 'device', 'anuladaPor']);

        if ($request->filled('desde') || $request->filled('hasta')) {
            $q->when($request->filled('desde'), fn ($q) => $q->whereDate('work_date', '>=', $request->date('desde')))
                ->when($request->filled('hasta'), fn ($q) => $q->whereDate('work_date', '<=', $request->date('hasta')));
        } else {
            $q->whereDate('work_date', $request->filled('fecha') ? $request->date('fecha') : ServicioDeAsistencia::diaLocal(now()));
        }

        $q->when($request->filled('empleado'), fn ($q) => $q->where('user_id', $request->integer('empleado')))
            ->when($request->filled('dispositivo'), fn ($q) => $q->where('device_id', $request->integer('dispositivo')))
            ->when($request->filled('metodo'), fn ($q) => $q->where('method', $request->query('metodo')))
            ->when($request->query('anuladas') === 'excluir', fn ($q) => $q->whereNull('voided_at'))
            ->when($request->query('anuladas') === 'solo', fn ($q) => $q->whereNotNull('voided_at'));

        $this->ordenar($q, $request, ['punched_at', 'work_date', 'id'], '-punched_at');

        return ChecadaResource::collection($q->paginate($this->porPagina($request)));
    }

    /**
     * Ver una checada.
     */
    public function show(AttendancePunch $punch): ChecadaResource
    {
        return new ChecadaResource($punch->load(['user', 'device', 'anuladaPor']));
    }

    /**
     * Registrar checada manual.
     *
     * Para cuando el empleado olvidó checar o falló el lector. `fecha` y
     * `hora` son locales; no se aceptan checadas en el futuro. Queda marcada
     * como manual, con su motivo y quién la registró, y el día se recalcula
     * (entrada, salida, retardo).
     */
    public function store(Request $request): ChecadaResource
    {
        $data = $request->validate(ServicioDeAsistencia::reglasChecadaManual(), ServicioDeAsistencia::mensajesChecadaManual());

        $user = User::findOrFail($data['user_id']);
        $punch = $this->asistencia->registrarManual($user, $data['fecha'], $data['hora'], $data['motivo'], $request->user()->id);

        /** @status 201 */
        return (new ChecadaResource($punch->load(['user', 'device', 'anuladaPor'])))
            ->additional(['message' => 'Checada manual registrada para '.$user->name.'.']);
    }

    /**
     * Anular una checada.
     *
     * La checada no se borra: queda en el historial con quién la anuló y
     * por qué. Las del día se renumeran y el día se recalcula (si ya pasó,
     * puede convertirse en falta). Una checada ya anulada responde 409.
     */
    public function anular(Request $request, AttendancePunch $punch): ChecadaResource
    {
        $data = $request->validate(['motivo' => ['required', 'string', 'max:255']], ['motivo.required' => 'Escribe por qué se anula la checada.']);

        abort_if($punch->voided_at !== null, 409, 'Esta checada ya estaba anulada.');

        $this->asistencia->anular($punch, $request->user()->id, $data['motivo']);

        return (new ChecadaResource($punch->refresh()->load(['user', 'device', 'anuladaPor'])))
            ->additional(['message' => 'Checada anulada. Queda en el historial con su motivo.']);
    }
}
