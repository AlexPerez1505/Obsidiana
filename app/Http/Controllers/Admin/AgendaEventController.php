<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\AgendaEventDay;
use App\Models\User;
use App\Notifications\AgendaEventAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class AgendaEventController extends Controller
{
    /**
     * Guarda una nueva cita de la agenda administrativa.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:40'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:540'],
            'visibility' => ['required', 'in:publico,participantes,privado'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['integer', 'exists:users,id'],
        ]);

        $event = new AgendaEvent();
        $event->forceFill([
            'title' => $data['title'],
            'event_type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['time'],
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'visibility' => $data['visibility'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'participants' => array_values(array_map('intval', $data['participants'] ?? [])),
            'status' => 'programado',
            'created_by' => $request->user()->id,
        ])->save();

        $start = Carbon::parse($event->start_date);
        $end = Carbon::parse($event->end_date);

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            AgendaEventDay::query()->firstOrCreate([
                'agenda_event_id' => $event->id,
                'event_date' => $cursor->toDateString(),
            ]);
        }

        $participantIds = collect($event->participants ?? [])
            ->reject(fn ($id) => (int) $id === (int) $request->user()->id);

        if ($participantIds->isNotEmpty()) {
            Notification::send(
                User::whereIn('id', $participantIds)->get(),
                new AgendaEventAssigned($event)
            );
        }

        return redirect()
            ->route('admin.agenda.index')
            ->with('status', 'Cita registrada para el '.$event->start_date->format('d/m/Y').'.');
    }

    /**
     * Actualiza una cita existente (reprogramar).
     */
    public function update(Request $request, AgendaEvent $event)
    {
        $user = $request->user();

        if ((int) $event->created_by !== (int) $user->id && ! $user->isAdmin()) {
            abort(403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:40'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:540'],
            'visibility' => ['required', 'in:publico,participantes,privado'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['integer', 'exists:users,id'],
        ]);

        $previousParticipants = collect($event->participants ?? [])->map(fn ($id) => (int) $id);

        $event->forceFill([
            'title' => $data['title'],
            'event_type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['time'],
            'duration_minutes' => $data['duration_minutes'] ?? $event->duration_minutes ?? 60,
            'visibility' => $data['visibility'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'participants' => array_values(array_map('intval', $data['participants'] ?? [])),
            'updated_by' => $user->id,
        ])->save();

        $event->days()->delete();

        $start = Carbon::parse($event->start_date);
        $end = Carbon::parse($event->end_date);

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            AgendaEventDay::query()->firstOrCreate([
                'agenda_event_id' => $event->id,
                'event_date' => $cursor->toDateString(),
            ]);
        }

        $newParticipants = collect($event->participants ?? [])
            ->map(fn ($id) => (int) $id)
            ->diff($previousParticipants)
            ->reject(fn ($id) => $id === (int) $user->id);

        if ($newParticipants->isNotEmpty()) {
            Notification::send(
                User::whereIn('id', $newParticipants)->get(),
                new AgendaEventAssigned($event)
            );
        }

        return redirect()
            ->route('admin.agenda.index')
            ->with('status', 'Cita reprogramada para el '.$event->start_date->format('d/m/Y').'.');
    }

    /**
     * Elimina una cita de la agenda (solo el creador o un admin).
     */
    public function destroy(Request $request, AgendaEvent $event)
    {
        $user = $request->user();

        if ((int) $event->created_by !== (int) $user->id && ! $user->isAdmin()) {
            abort(403);
        }

        $event->days()->delete();
        $event->delete();

        return redirect()
            ->route('admin.agenda.index')
            ->with('status', 'La cita "'.$event->title.'" fue eliminada.');
    }
}
