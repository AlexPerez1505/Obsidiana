<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\EventoResource;
use App\Models\CompanyEvent;
use App\Services\Asistencia\ConfiguracionDeAsistencia;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Eventos de empresa: días de descanso del equipo (`descanso`), días en
 * que se trabaja fuera y no se checa (`sin_checar`) y días con otro
 * horario (`horario_especial`). Al crear o borrar uno, los días que ya
 * pasaron se vuelven a evaluar.
 *
 * @tags RH · Asistencia
 */
class EventoController extends Controller
{
    use ListadoApi;

    public function __construct(private ConfiguracionDeAsistencia $config)
    {
    }

    /**
     * Listar eventos.
     *
     * `periodo=proximos` los que no han terminado (del más cercano al más
     * lejano); `periodo=pasados` los que ya terminaron (del más reciente al
     * más antiguo). Sin `periodo`, todos.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // proximos | pasados
            'periodo' => ['nullable', 'in:proximos,pasados'],
            // descanso | sin_checar | horario_especial
            'type' => ['nullable', 'in:'.implode(',', array_keys(CompanyEvent::TIPOS))],
        ]);

        $hoy = ServicioDeAsistencia::diaLocal(now())->toDateString();
        $periodo = $request->query('periodo');

        $q = CompanyEvent::with('creador')
            ->when($periodo === 'proximos', fn ($q) => $q->whereDate('date_to', '>=', $hoy))
            ->when($periodo === 'pasados', fn ($q) => $q->whereDate('date_to', '<', $hoy))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')));

        $this->ordenar($q, $request, ['date_from', 'date_to', 'id'], $periodo === 'proximos' ? 'date_from' : '-date_from');

        return EventoResource::collection($q->paginate($this->porPagina($request)));
    }

    /**
     * Ver un evento.
     */
    public function show(CompanyEvent $evento): EventoResource
    {
        return new EventoResource($evento->load('creador'));
    }

    /**
     * Crear evento.
     *
     * `scope: todos` aplica a toda la empresa; `scope: seleccion` solo a los
     * `user_ids`. Un `horario_especial` necesita `time_in` y `time_out` (H:i)
     * y puede caer en un día que normalmente no se trabaja.
     */
    public function store(Request $request): EventoResource
    {
        $data = $request->validate(ConfiguracionDeAsistencia::reglasEvento(), ConfiguracionDeAsistencia::mensajesEvento());

        $evento = $this->config->crearEvento($data, $request->user()->id);

        /** @status 201 */
        return (new EventoResource($evento->load('creador')))
            ->additional(['message' => "Evento «{$evento->name}» registrado."]);
    }

    /**
     * Eliminar evento.
     *
     * Los días del evento que ya pasaron se recalculan sin él.
     */
    public function destroy(CompanyEvent $evento): JsonResponse
    {
        $this->config->eliminarEvento($evento);

        return response()->json(['message' => 'Evento eliminado y días recalculados.']);
    }
}
