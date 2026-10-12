<?php

namespace App\Services\Administracion;

use App\Models\AgendaEvent;
use App\Models\AgendaEventDay;
use App\Models\User;
use App\Notifications\AgendaEventAssigned;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Citas de la agenda administrativa: quién las ve, alta, reprogramación y
 * baja. Lo usan la pantalla de Agenda y la API.
 */
class AgendaAdministrativa
{
    public const VISIBILIDADES = ['publico', 'participantes', 'privado'];

    public const TIPOS = [
        'training' => 'Capacitación',
        'delivery' => 'Entrega de equipo',
        'install' => 'Instalación',
        'maintenance' => 'Mantenimiento',
        'meeting' => 'Reunión',
        'congress' => 'Congreso',
    ];

    public const ESTADOS = [
        'completado' => 'Completado',
        'en_espera' => 'En espera',
        'cancelado' => 'Cancelado',
        'programado' => 'Próximamente',
    ];

    public const REGLAS = [
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
    ];

    /**
     * Lo que ve cada quien: lo público, lo que creó y aquello donde lo
     * pusieron de participante. Lo privado de otro no aparece.
     *
     * Se usa como ->where(AgendaAdministrativa::alcanceVisible($id)).
     */
    public static function alcanceVisible(int $userId): Closure
    {
        return function ($query) use ($userId) {
            $query->where('visibility', 'publico')
                ->orWhere('created_by', $userId)
                ->orWhere(function ($q) use ($userId) {
                    $q->where('visibility', 'participantes')
                        ->whereJsonContains('participants', $userId);
                });
        };
    }

    public function esVisiblePara(AgendaEvent $event, User $user): bool
    {
        return AgendaEvent::whereKey($event->id)->where(self::alcanceVisible((int) $user->id))->exists();
    }

    /** Solo quien la creó o un administrador la cambia o la borra. */
    public function puedeModificar(AgendaEvent $event, User $user): bool
    {
        return (int) $event->created_by === (int) $user->id || Administrador::pleno($user);
    }

    public function crear(array $data, User $quien): AgendaEvent
    {
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
            'created_by' => $quien->id,
        ])->save();

        $this->marcarDias($event);

        $this->avisar($event, collect($event->participants ?? [])
            ->reject(fn ($id) => (int) $id === (int) $quien->id));

        return $event;
    }

    /** Reprograma. Solo se avisa a los participantes que se agregaron. */
    public function actualizar(AgendaEvent $event, array $data, User $quien): AgendaEvent
    {
        $previos = collect($event->participants ?? [])->map(fn ($id) => (int) $id);

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
            'updated_by' => $quien->id,
        ])->save();

        $event->days()->delete();
        $this->marcarDias($event);

        $this->avisar($event, collect($event->participants ?? [])
            ->map(fn ($id) => (int) $id)
            ->diff($previos)
            ->reject(fn ($id) => $id === (int) $quien->id));

        return $event;
    }

    public function eliminar(AgendaEvent $event): void
    {
        $event->days()->delete();
        $event->delete();
    }

    /** Un renglón por cada día que dura la cita. */
    private function marcarDias(AgendaEvent $event): void
    {
        $inicio = Carbon::parse($event->start_date);
        $fin = Carbon::parse($event->end_date);

        for ($dia = $inicio->copy(); $dia->lte($fin); $dia->addDay()) {
            AgendaEventDay::query()->firstOrCreate([
                'agenda_event_id' => $event->id,
                'event_date' => $dia->toDateString(),
            ]);
        }
    }

    private function avisar(AgendaEvent $event, $ids): void
    {
        if ($ids->isNotEmpty()) {
            Notification::send(User::whereIn('id', $ids)->get(), new AgendaEventAssigned($event));
        }
    }
}
