<?php

namespace App\Http\Controllers\Api\V1\Administracion;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Administracion\AgendaEventoResource;
use App\Models\AgendaEvent;
use App\Services\Administracion\AgendaAdministrativa;
use App\Support\CalendarioEmpresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

/**
 * Agenda administrativa: citas, capacitaciones, entregas, instalaciones…
 *
 * Como en la web, la usa cualquier usuario con acceso. Cada quien ve lo
 * público, lo suyo y donde lo pusieron de participante; solo quien creó
 * la cita (o un administrador) la cambia o la borra.
 *
 * @tags Administración · Agenda
 */
class AgendaController extends Controller
{
    use ListadoApi;

    public function __construct(private AgendaAdministrativa $agenda)
    {
    }

    /**
     * Listar citas.
     *
     * Las que tocan el rango `desde`–`hasta` (Y-m-d; por omisión el mes en
     * curso), en orden de fecha y hora. `meta.calendario_empresa` trae
     * además los días festivos y eventos de la empresa del rango (vienen de
     * Asistencia y son de solo lectura).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
        ]);

        // Con una sola fecha, el resto del rango es su mes.
        $hasta = $request->filled('hasta') ? Carbon::parse($request->query('hasta'))->endOfDay() : null;
        $desde = $request->filled('desde')
            ? Carbon::parse($request->query('desde'))->startOfDay()
            : ($hasta?->copy()->startOfMonth() ?? now()->startOfMonth());
        $hasta ??= $desde->copy()->endOfMonth();

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        $citas = AgendaEvent::with('creator')
            ->whereDate('start_date', '<=', $hasta->toDateString())
            ->whereDate('end_date', '>=', $desde->toDateString())
            ->where(AgendaAdministrativa::alcanceVisible((int) $request->user()->id))
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->paginate($this->porPagina($request));

        $empresa = collect(CalendarioEmpresa::eventos($desde, $hasta, $request->user()))
            ->map(fn (array $e) => [
                'id' => $e['id'],
                'title' => $e['title'],
                'start_date' => $e['start_date'],
                'end_date' => $e['end_date'],
                'type' => $e['type'],
                'type_label' => $e['type_label'],
                'reason' => $e['reason'],
                'participants' => $e['participants'] ?: null,
            ])
            ->values();

        return AgendaEventoResource::collection($citas)->additional(['meta' => [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'calendario_empresa' => $empresa,
        ]]);
    }

    /**
     * Ver una cita.
     *
     * Solo si quien pregunta la puede ver (pública, suya o participa).
     */
    public function show(Request $request, AgendaEvent $event): AgendaEventoResource
    {
        abort_unless($this->agenda->esVisiblePara($event, $request->user()), 404, 'No existe el registro solicitado.');

        return new AgendaEventoResource($event->load('creator'));
    }

    /**
     * Registrar cita.
     *
     * `type`: training, delivery, install, maintenance, meeting (o el que
     * use la empresa). `time` en H:i. `visibility`: publico, participantes
     * o privado. A los `participants` (ids de usuario) les llega una
     * notificación.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(AgendaAdministrativa::REGLAS);

        $event = $this->agenda->crear($data, $request->user());

        return (new AgendaEventoResource($event->load('creator')))
            ->additional(['message' => 'Cita registrada para el '.$event->start_date->format('d/m/Y').'.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Reprogramar cita.
     *
     * Mismos campos que el alta. Solo quien la creó o un administrador.
     * Solo se avisa a los participantes que se agregaron.
     */
    public function update(Request $request, AgendaEvent $event): AgendaEventoResource
    {
        abort_unless($this->agenda->puedeModificar($event, $request->user()), 403, 'Solo quien registró la cita o un administrador puede cambiarla.');

        $data = $request->validate(AgendaAdministrativa::REGLAS);

        $event = $this->agenda->actualizar($event, $data, $request->user());

        return (new AgendaEventoResource($event->load('creator')))
            ->additional(['message' => 'Cita reprogramada para el '.$event->start_date->format('d/m/Y').'.']);
    }

    /**
     * Eliminar cita.
     *
     * Solo quien la creó o un administrador.
     */
    public function destroy(Request $request, AgendaEvent $event): JsonResponse
    {
        abort_unless($this->agenda->puedeModificar($event, $request->user()), 403, 'Solo quien registró la cita o un administrador puede eliminarla.');

        $this->agenda->eliminar($event);

        return response()->json(['message' => 'La cita "'.$event->title.'" fue eliminada.']);
    }
}
