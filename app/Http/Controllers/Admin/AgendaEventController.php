<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Services\Administracion\AgendaAdministrativa;
use Illuminate\Http\Request;

/**
 * Altas, cambios y bajas de la agenda administrativa. La lógica vive en
 * AgendaAdministrativa, que comparte con la API.
 */
class AgendaEventController extends Controller
{
    public function __construct(private AgendaAdministrativa $agenda)
    {
    }

    /**
     * Guarda una nueva cita de la agenda administrativa.
     */
    public function store(Request $request)
    {
        $data = $request->validate(AgendaAdministrativa::REGLAS);

        $event = $this->agenda->crear($data, $request->user());

        return redirect()
            ->route('admin.agenda.index')
            ->with('status', 'Cita registrada para el '.$event->start_date->format('d/m/Y').'.');
    }

    /**
     * Actualiza una cita existente (reprogramar).
     */
    public function update(Request $request, AgendaEvent $event)
    {
        if (! $this->agenda->puedeModificar($event, $request->user())) {
            abort(403);
        }

        $data = $request->validate(AgendaAdministrativa::REGLAS);

        $event = $this->agenda->actualizar($event, $data, $request->user());

        return redirect()
            ->route('admin.agenda.index')
            ->with('status', 'Cita reprogramada para el '.$event->start_date->format('d/m/Y').'.');
    }

    /**
     * Elimina una cita de la agenda (solo el creador o un admin).
     */
    public function destroy(Request $request, AgendaEvent $event)
    {
        if (! $this->agenda->puedeModificar($event, $request->user())) {
            abort(403);
        }

        $this->agenda->eliminar($event);

        return redirect()
            ->route('admin.agenda.index')
            ->with('status', 'La cita "'.$event->title.'" fue eliminada.');
    }
}
