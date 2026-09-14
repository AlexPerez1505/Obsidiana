<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\Congress;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
    private const TYPES = ['training', 'delivery', 'install', 'meeting', 'maintenance'];

    public function index(Request $request): View
    {
        $requestedMonth = (string) $request->input('month', now()->format('Y-m'));

        try {
            $month = Carbon::createFromFormat('Y-m', $requestedMonth)->startOfMonth();
        } catch (\Throwable $e) {
            $month = now()->startOfMonth();
        }

        $rangeStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $rangeEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $agendaEvents = AgendaEvent::query()
            ->where('start_date', '<=', $rangeEnd)
            ->where('end_date', '>=', $rangeStart)
            ->orderBy('start_date')
            ->get()
            ->map(fn (AgendaEvent $event): array => $this->mapAgendaEvent($event));

        $congresses = Congress::query()
            ->with('category')
            ->where('fecha_inicio', '<=', $rangeEnd)
            ->where('fecha_finalizacion', '>=', $rangeStart)
            ->orderBy('fecha_inicio')
            ->get()
            ->map(fn (Congress $congress): array => $this->mapCongress($congress));

        $eventList = $agendaEvents->concat($congresses)
            ->sortBy('start_date')
            ->values()
            ->all();

        $events = [];
        foreach ($eventList as $event) {
            $start = Carbon::parse($event['start_date']);
            $end = Carbon::parse($event['end_date']);

            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                if ($cursor->format('Y-m') !== $month->format('Y-m')) {
                    continue;
                }

                $isStart = $cursor->isSameDay($start);
                $isEnd = $cursor->isSameDay($end);

                $dayEvent = $event['source'] === 'congress'
                    ? $this->congressParaEseDia($event, $isStart, $isEnd)
                    : $event;

                $dayNumber = (int) $cursor->format('j');
                $events[$dayNumber][] = array_merge($dayEvent, [
                    'date' => $cursor->format('Y-m-d'),
                    'is_start' => $isStart,
                    'is_end' => $isEnd,
                ]);
            }
        }

        $calendar = [];
        for ($cursor = $rangeStart->copy(); $cursor->lte($rangeEnd); $cursor->addDay()) {
            $calendar[] = [
                'num' => (int) $cursor->format('j'),
                'muted' => $cursor->format('Y-m') !== $month->format('Y-m'),
                'date' => $cursor->format('Y-m-d'),
            ];
        }

        $upcoming = collect($eventList)
            ->filter(fn (array $event): bool => Carbon::parse($event['end_date'])->gte(now()->startOfDay()))
            ->sortBy('start_date')
            ->take(8)
            ->values()
            ->all();

        return view('admin.agenda.index', [
            'eventColors' => $this->colors(),
            'eventList' => $eventList,
            'events' => $events,
            'calendar' => $calendar,
            'upcoming' => $upcoming,
            'month' => $month,
            'monthLabel' => ucfirst($month->translatedFormat('F Y')),
            'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'currentMonth' => now()->format('Y-m'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        AgendaEvent::create([
            ...$data,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.agenda.index', ['month' => Carbon::parse($data['start_date'])->format('Y-m')])
            ->with('status', 'Evento registrado correctamente.');
    }

    public function update(Request $request, AgendaEvent $agendaEvent): RedirectResponse
    {
        $data = $this->validated($request);

        $agendaEvent->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.agenda.index', ['month' => Carbon::parse($data['start_date'])->format('Y-m')])
            ->with('status', 'Evento actualizado correctamente.');
    }

    public function destroy(Request $request, AgendaEvent $agendaEvent): RedirectResponse
    {
        $month = $agendaEvent->start_date->format('Y-m');
        $agendaEvent->delete();

        return redirect()
            ->route('admin.agenda.index', ['month' => $month])
            ->with('status', 'Evento eliminado correctamente.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'time' => ['required', 'date_format:H:i'],
            'participants' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $participants = trim((string) ($data['participants'] ?? ''));

        return [
            'title' => $data['title'],
            'event_type' => $data['type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['time'],
            'participants' => $participants !== '' ? array_map('trim', explode(',', $participants)) : [],
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function mapAgendaEvent(AgendaEvent $event): array
    {
        $time = $event->start_time ? Carbon::parse($event->start_time) : null;
        $participants = $event->participants ?? [];

        return [
            'id' => 'evt-'.$event->id,
            'source' => 'agenda_event',
            'model_id' => $event->id,
            'start_date' => $event->start_date->format('Y-m-d'),
            'end_date' => $event->end_date->format('Y-m-d'),
            'time' => $time ? $time->format('h:i a') : 'Todo el dia',
            'time_value' => $time ? $time->format('H:i') : '',
            'title' => $event->title,
            'type' => $event->event_type,
            'notes' => $event->notes ?? '',
            'participants' => is_array($participants) ? implode(', ', $participants) : (string) $participants,
        ];
    }

    /**
     * El congreso sí ocupa su cuadro en cada día que dura (como cualquier
     * evento de varios días), pero el texto cambia según el día:
     * - Primer día: "Montaje: {nombre}" con hora_montaje.
     * - Último día: "Desmontaje: {nombre}" con hora_desmontaje.
     * - Días intermedios: solo el nombre del congreso, sin hora.
     *
     * Aquí solo se guardan las dos horas; congressParaEseDia() decide cuál
     * mostrar (o ninguna) cuando se expande día por día en el calendario.
     */
    private function mapCongress(Congress $congress): array
    {
        $montaje = $congress->hora_montaje;
        $desmontaje = $congress->hora_desmontaje;

        return [
            'id' => 'congress-'.$congress->id,
            'source' => 'congress',
            'model_id' => $congress->id,
            'start_date' => $congress->fecha_inicio->format('Y-m-d'),
            'end_date' => $congress->fecha_finalizacion->format('Y-m-d'),
            'title' => $congress->nombre,
            // Fuera del calendario (ej. "Próximos eventos") se muestra la
            // hora de montaje como referencia de cuándo arranca; dentro del
            // calendario, congressParaEseDia() la sobrescribe por día.
            'time' => $montaje ? $montaje->format('h:i a') : 'Todo el dia',
            'time_value' => $montaje ? $montaje->format('H:i') : '',
            'time_montaje' => $montaje ? $montaje->format('h:i a') : 'Todo el dia',
            'time_montaje_value' => $montaje ? $montaje->format('H:i') : '',
            'time_desmontaje' => $desmontaje ? $desmontaje->format('h:i a') : 'Todo el dia',
            'time_desmontaje_value' => $desmontaje ? $desmontaje->format('H:i') : '',
            'type' => 'congress',
            'notes' => $congress->direccion ?: ($congress->comments ?? ''),
            'participants' => $congress->category?->nombre ?? '',
        ];
    }

    /**
     * Aplica el texto y la hora que corresponden a ese día específico del
     * congreso, sin tocar el evento base (cada día se calcula aparte).
     */
    private function congressParaEseDia(array $event, bool $isStart, bool $isEnd): array
    {
        if ($isStart && $isEnd) {
            $event['title'] = "Montaje y desmontaje: {$event['title']}";
            $event['time'] = $event['time_montaje'];
            $event['time_value'] = $event['time_montaje_value'];

            return $event;
        }

        if ($isStart) {
            $event['title'] = "Montaje: {$event['title']}";
            $event['time'] = $event['time_montaje'];
            $event['time_value'] = $event['time_montaje_value'];

            return $event;
        }

        if ($isEnd) {
            $event['title'] = "Desmontaje: {$event['title']}";
            $event['time'] = $event['time_desmontaje'];
            $event['time_value'] = $event['time_desmontaje_value'];

            return $event;
        }

        // Día intermedio: solo el nombre, sin hora de montaje/desmontaje.
        $event['time'] = '';
        $event['time_value'] = '';

        return $event;
    }

    private function colors(): array
    {
        return [
            'training' => ['bg' => '#9be8f3', 'text' => '#075f6f', 'line' => '#8ee6f0'],
            'delivery' => ['bg' => '#96f5ad', 'text' => '#0f7a2d', 'line' => '#9cf3b1'],
            'install' => ['bg' => '#c7a7ff', 'text' => '#3d178b', 'line' => '#c8a7ff'],
            'meeting' => ['bg' => '#ffd9a3', 'text' => '#9a4f00', 'line' => '#ffd39a'],
            'maintenance' => ['bg' => '#ff9ea5', 'text' => '#a3131e', 'line' => '#ffa0a6'],
            'congress' => ['bg' => '#a9bcff', 'text' => '#053394', 'line' => '#a9bcff'],
        ];
    }
}
