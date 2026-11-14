<?php

namespace App\Services\Agenda;

use App\Models\AgendaActividad;
use App\Models\ClienteSeguimiento;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Quién ve y quién puede qué en la agenda. Todo pasa por aquí: el
 * calendario, las búsquedas, los contadores, el detalle por URL y el ICS.
 *
 * Niveles al ver un item:
 *  - 'completo': ve todo.
 *  - 'ocupado':  solo sabe que esa persona está ocupada en ese horario
 *                (supervisor viendo algo privado de su gente).
 *  - null:       no le aparece.
 *
 * Reglas:
 *  - Cada quien ve y administra lo suyo (responsable, creador, participante).
 *  - Los eventos de grupo/empresa los ve quien sea destinatario.
 *  - Los seguimientos comerciales NUNCA salen en la agenda general: solo los
 *    ve su responsable, quien lo programó, el líder de su equipo o quien
 *    tiene `agenda.ver_todos`.
 *  - Lo privado de otra persona, a lo más, aparece como "Ocupado".
 */
class Acceso
{
    /** @var array<int, array<int>> */
    private array $cacheSupervisados = [];

    /** @var array<int, array<int>> */
    private array $cacheRoles = [];

    /** @var array<int, array<int>> */
    private array $cacheEquipos = [];

    /** Usuarios cuya agenda puede consultar (incluye la propia). */
    public function supervisados(User $u): array
    {
        return $this->cacheSupervisados[$u->id] ??= $u->can('agenda.ver_todos')
            ? User::query()->pluck('id')->map(fn ($id) => (int) $id)->all()
            : array_values(array_unique(array_merge([$u->id], $this->miembrosDeEquiposQueLidera($u))));
    }

    public function miembrosDeEquiposQueLidera(User $u): array
    {
        $equipos = DB::table('agenda_equipo_usuario')->where('user_id', $u->id)->where('es_lider', true)->pluck('equipo_id');

        if ($equipos->isEmpty()) {
            return [];
        }

        return DB::table('agenda_equipo_usuario')->whereIn('equipo_id', $equipos)->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    public function esSupervisorDe(User $u, ?int $otroId): bool
    {
        return $otroId !== null && in_array((int) $otroId, $this->supervisados($u), true);
    }

    public function rolesDe(int $userId): array
    {
        return $this->cacheRoles[$userId] ??= DB::table('role_user')->where('user_id', $userId)->pluck('role_id')->map(fn ($id) => (int) $id)->all();
    }

    public function equiposDe(int $userId): array
    {
        return $this->cacheEquipos[$userId] ??= DB::table('agenda_equipo_usuario')->where('user_id', $userId)->pluck('equipo_id')->map(fn ($id) => (int) $id)->all();
    }

    // ===================== Seguimientos =====================

    public function nivelSeguimiento(ClienteSeguimiento $s, User $u): ?string
    {
        if ((int) $s->user_id === $u->id || (int) $s->created_by === $u->id || $this->esSupervisorDe($u, $s->user_id)) {
            return 'completo';
        }

        // Quien ve a todos los clientes también puede consultarlo desde la ficha.
        if ($u->can('clientes.ver_todos') && $u->can('agenda.ver_todos')) {
            return 'completo';
        }

        return null;
    }

    public function puedeEditarSeguimiento(ClienteSeguimiento $s, User $u): bool
    {
        return (int) $s->user_id === $u->id
            || (int) $s->created_by === $u->id
            || ($u->can('agenda.reasignar') && $this->esSupervisorDe($u, $s->user_id))
            || ($s->customer && $s->customer->visiblePara($u) && $u->can('clientes.editar'));
    }

    public function puedeReasignarSeguimiento(ClienteSeguimiento $s, User $u): bool
    {
        return $u->can('clientes.ver_todos') || ($u->can('agenda.reasignar') && $this->esSupervisorDe($u, $s->user_id));
    }

    // ===================== Actividades =====================

    public function esDestinatario(AgendaActividad $a, User $u): bool
    {
        if ($a->visibilidad === 'empresa') {
            return true;
        }

        if ($a->visibilidad !== 'grupo') {
            return false;
        }

        $roles = $a->relationLoaded('roles') ? $a->roles->pluck('id')->all() : $a->roles()->pluck('roles.id')->all();
        $equipos = $a->relationLoaded('equipos') ? $a->equipos->pluck('id')->all() : $a->equipos()->pluck('agenda_equipos.id')->all();

        return (bool) array_intersect(array_map('intval', $roles), $this->rolesDe($u->id))
            || (bool) array_intersect(array_map('intval', $equipos), $this->equiposDe($u->id));
    }

    public function esParticipante(AgendaActividad $a, User $u): bool
    {
        $ids = $a->relationLoaded('participantes') ? $a->participantes->pluck('id')->all() : $a->participantes()->pluck('users.id')->all();

        return in_array($u->id, array_map('intval', $ids), true);
    }

    public function esDueno(AgendaActividad $a, User $u): bool
    {
        return (int) $a->responsable_id === $u->id || (int) $a->created_by === $u->id;
    }

    public function nivelActividad(AgendaActividad $a, User $u): ?string
    {
        if ($this->esDueno($a, $u) || $this->esParticipante($a, $u) || $this->esDestinatario($a, $u)) {
            return 'completo';
        }

        if ($this->esSupervisorDe($u, $a->responsable_id)) {
            return $a->visibilidad === 'privado' ? ($a->ocupa_horario ? 'ocupado' : null) : 'completo';
        }

        return null;
    }

    /** ¿Puede cambiar los datos? Los participantes solo responden. */
    public function puedeEditarActividad(AgendaActividad $a, User $u): bool
    {
        if (in_array($a->visibilidad, ['grupo', 'empresa'], true)) {
            return $u->can('agenda.empresa');
        }

        return $this->esDueno($a, $u) || ($u->can('agenda.reasignar') && $this->esSupervisorDe($u, $a->responsable_id));
    }

    public function puedeReasignarActividad(AgendaActividad $a, User $u): bool
    {
        return $u->can('agenda.reasignar') && $this->puedeEditarActividad($a, $u);
    }

    public function puedeEliminarActividad(AgendaActividad $a, User $u): bool
    {
        return $this->puedeEditarActividad($a, $u) && ((int) $a->created_by === $u->id || $u->can('agenda.eliminar'));
    }

    /** ¿Puede asignarle trabajo a esta persona? A sí mismo siempre. */
    public function puedeAsignarA(User $u, int $userId): bool
    {
        return $userId === $u->id || ($u->can('agenda.reasignar') && $this->esSupervisorDe($u, $userId)) || $u->can('agenda.empresa');
    }
}
