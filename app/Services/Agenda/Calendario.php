<?php

namespace App\Services\Agenda;

use App\Models\AgendaActividad;
use App\Models\ClienteSeguimiento;
use App\Models\Congress;
use App\Models\Customer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lo que ve cada quien en la agenda, por rango de fechas.
 *
 * Junta tres fuentes y las deja en un mismo formato:
 *  - seguimientos de Clientes (origen automático),
 *  - actividades y eventos de la agenda (manuales),
 *  - congresos (solo lectura, agenda de la empresa).
 *
 * Todo pasa por Acceso: lo que no le toca ver no sale, ni en la lista, ni
 * en la búsqueda, ni en los contadores, ni en el ICS.
 */
class Calendario
{
    public function __construct(private readonly Acceso $acceso) {}

    public function hoy(): CarbonImmutable
    {
        return CarbonImmutable::now(config('agenda.timezone'))->startOfDay();
    }

    /**
     * Usuarios cuya agenda se está pidiendo. Sin filtro, la propia.
     *
     * @return array<int, int>
     */
    public function usuariosObjetivo(User $u, array $f): array
    {
        if (! empty($f['usuario'])) {
            $id = (int) $f['usuario'];
            abort_unless($this->acceso->esSupervisorDe($u, $id), 403, 'No puedes consultar la agenda de esa persona.');

            return [$id];
        }

        if (! empty($f['departamento'])) {
            $delRol = DB::table('role_user')->where('role_id', (int) $f['departamento'])->pluck('user_id')->map(fn ($i) => (int) $i)->all();

            return array_values(array_intersect($this->acceso->supervisados($u), $delRol));
        }

        return [$u->id];
    }

    /** @return array<int, array> items listos para la pantalla */
    public function rango(User $u, string $desde, string $hasta, array $f = []): array
    {
        $ambito = $f['ambito'] ?? 'todas';
        $usuarios = $this->usuariosObjetivo($u, $f);
        $propia = $usuarios === [$u->id];
        $items = collect();

        if ($ambito !== 'empresa' && ($f['origen'] ?? '') !== 'manual') {
            $seg = ClienteSeguimiento::query()
                ->with(['customer:id,nombre,apellido,etapa,telefono,gmail,asesor_id', 'responsable:id,name'])
                ->whereIn('user_id', $usuarios ?: [0])
                ->whereBetween('fecha', [$desde, $hasta]);
            $this->filtrosSeguimiento($seg, $f);

            foreach ($seg->get() as $s) {
                if ($this->acceso->nivelSeguimiento($s, $u) === 'completo') {
                    $items->push($this->deSeguimiento($s, $u));
                }
            }
        }

        if (($f['origen'] ?? '') !== 'cliente') {
            $act = AgendaActividad::query()
                ->with(['responsable:id,name', 'customer:id,nombre,apellido', 'participantes:id,name', 'roles:id,name,label', 'equipos:id,nombre'])
                ->where('fecha_inicio', '<=', $hasta)
                ->where('fecha_fin', '>=', $desde)
                ->where(function ($q) use ($usuarios, $propia, $ambito) {
                    if ($ambito !== 'empresa') {
                        $q->whereIn('responsable_id', $usuarios ?: [0])
                            ->orWhereIn('created_by', $usuarios ?: [0])
                            ->orWhereHas('participantes', fn ($p) => $p->whereIn('users.id', $usuarios ?: [0]));
                    }
                    // Los eventos de grupo/empresa: solo en la agenda propia o la de la empresa.
                    if ($propia || $ambito === 'empresa') {
                        $q->orWhereIn('visibilidad', ['grupo', 'empresa']);
                    }
                });
            $this->filtrosActividad($act, $f);

            foreach ($act->get() as $a) {
                if ($ambito === 'empresa' && ! in_array($a->visibilidad, ['grupo', 'empresa'], true)) {
                    continue;
                }
                $nivel = $this->acceso->nivelActividad($a, $u);
                if ($nivel === 'completo') {
                    $items->push($this->deActividad($a, $u));
                } elseif ($nivel === 'ocupado') {
                    $items->push($this->ocupado($a));
                }
            }
        }

        if ($ambito !== 'mia' && $propia && empty($f['cliente']) && ($f['origen'] ?? '') !== 'cliente'
            && (empty($f['tipo']) || $f['tipo'] === 'congreso') && empty($f['estado']) && empty($f['prioridad'])) {
            $cong = Congress::query()->where('fecha_inicio', '<=', $hasta)->where('fecha_finalizacion', '>=', $desde);
            if (! empty($f['q'])) {
                $cong->where('nombre', 'like', '%'.$f['q'].'%');
            }
            foreach ($cong->get() as $c) {
                $items->push($this->deCongreso($c, $u));
            }
        }

        return $items->sortBy(fn ($i) => $i['fecha'].' '.($i['hora'] ?? '00:00'))->values()->all();
    }

    private function filtrosSeguimiento($q, array $f): void
    {
        if (! empty($f['tipo']) && $f['tipo'] !== 'seguimiento' && ! str_starts_with($f['tipo'], 'seg:')) {
            $q->whereRaw('1 = 0');
        }
        if (! empty($f['tipo']) && str_starts_with($f['tipo'], 'seg:')) {
            $q->where('tipo', substr($f['tipo'], 4));
        }
        if (! empty($f['estado'])) {
            $f['estado'] === 'vencido'
                ? $q->pendientes()->whereDate('fecha', '<', $this->hoy()->toDateString())
                : $q->where('estado', $f['estado']);
        }
        if (! empty($f['prioridad'])) {
            $q->where('prioridad', $f['prioridad']);
        }
        if (! empty($f['cliente'])) {
            $q->where('customer_id', (int) $f['cliente']);
        }
        if (! empty($f['q'])) {
            $t = '%'.$f['q'].'%';
            $q->where(fn ($w) => $w->where('nota', 'like', $t)->orWhereHas('customer', fn ($c) => $c->whereRaw("CONCAT_WS(' ', nombre, apellido) LIKE ?", [$t])));
        }
    }

    private function filtrosActividad($q, array $f): void
    {
        if (! empty($f['tipo'])) {
            str_starts_with($f['tipo'], 'seg:') || $f['tipo'] === 'congreso' ? $q->whereRaw('1 = 0') : $q->where('tipo', $f['tipo']);
        }
        if (! empty($f['estado'])) {
            $f['estado'] === 'vencido'
                ? $q->where('clase', 'tarea')->whereIn('estado', ['pendiente', 'en_proceso'])->where('fecha_fin', '<', $this->hoy()->toDateString())
                : $q->where('estado', $f['estado']);
        }
        if (! empty($f['prioridad'])) {
            $q->where('prioridad', $f['prioridad']);
        }
        if (! empty($f['cliente'])) {
            $q->where('customer_id', (int) $f['cliente']);
        }
        if (! empty($f['q'])) {
            $t = '%'.$f['q'].'%';
            $q->where(fn ($w) => $w->where('titulo', 'like', $t)->orWhere('descripcion', 'like', $t)->orWhere('ubicacion', 'like', $t));
        }
    }

    // ===================== Formato común =====================

    public function deSeguimiento(ClienteSeguimiento $s, User $u): array
    {
        $cliente = $s->customer;
        $puede = $this->acceso->puedeEditarSeguimiento($s, $u);

        return [
            'key' => $s->llaveAgenda(),
            'fuente' => 'seguimiento',
            'origen' => $s->origen === 'agenda' ? 'manual' : 'cliente',
            'titulo' => $s->tipoLabel().': '.$s->nombreCliente(),
            'tipo' => 'seguimiento',
            'subtipo' => $s->tipo,
            'tipo_label' => 'Seguimiento · '.$s->tipoLabel(),
            'clase' => 'tarea',
            'fecha' => $s->fecha->toDateString(),
            'fecha_fin' => $s->fecha->toDateString(),
            'hora' => $s->horaCorta(),
            'hora_fin' => null,
            'todo_el_dia' => ! $s->horaCorta(),
            'estado' => $s->estado,
            'estado_label' => $s->estadoLabel(),
            'vencido' => $s->vencido(),
            'prioridad' => $s->prioridad,
            'responsable' => ['id' => (int) $s->user_id, 'nombre' => $s->responsable?->name],
            'cliente' => $cliente ? [
                'id' => $cliente->id,
                'nombre' => $s->nombreCliente(),
                'etapa' => $cliente->etapa,
                'url' => $cliente->visiblePara($u) ? route('commercial.clientes.show', $cliente).'#seguimientos' : null,
            ] : null,
            'motivo' => $s->nota,
            'visibilidad' => 'privado',
            'ocupa_horario' => false,
            'puede' => [
                'editar' => $puede && $s->abierto(),
                'mover' => $puede && $s->abierto(),
                'completar' => $puede && $s->abierto(),
                'cancelar' => $puede && $s->abierto(),
                'reasignar' => $s->abierto() && $this->acceso->puedeReasignarSeguimiento($s, $u),
                'responder' => false,
                'duplicar' => false,
            ],
        ];
    }

    public function deActividad(AgendaActividad $a, User $u): array
    {
        $editar = $this->acceso->puedeEditarActividad($a, $u);
        $mia = $a->participantes->firstWhere('id', $u->id);

        return [
            'key' => $a->llaveAgenda(),
            'fuente' => 'actividad',
            'origen' => 'manual',
            'titulo' => $a->titulo,
            'tipo' => $a->tipo,
            'subtipo' => null,
            'tipo_label' => $a->tipoLabel(),
            'clase' => $a->clase,
            'fecha' => $a->fecha_inicio->toDateString(),
            'fecha_fin' => $a->fecha_fin->toDateString(),
            'hora' => $a->hora_inicio && ! $a->todo_el_dia ? substr($a->hora_inicio, 0, 5) : null,
            'hora_fin' => $a->hora_fin && ! $a->todo_el_dia ? substr($a->hora_fin, 0, 5) : null,
            'todo_el_dia' => $a->todo_el_dia || ! $a->hora_inicio,
            'estado' => $a->estado,
            'estado_label' => $a->estadoLabel(),
            'vencido' => $a->vencida(),
            'prioridad' => $a->prioridad,
            'responsable' => ['id' => (int) $a->responsable_id, 'nombre' => $a->responsable?->name],
            'cliente' => $a->customer ? [
                'id' => $a->customer->id,
                'nombre' => trim($a->customer->nombre.' '.$a->customer->apellido),
                'url' => $a->customer->visiblePara($u) ? route('commercial.clientes.show', $a->customer) : null,
            ] : null,
            'motivo' => null,
            'ubicacion' => $a->ubicacion,
            'visibilidad' => $a->visibilidad,
            'ocupa_horario' => (bool) $a->ocupa_horario,
            'serie' => (bool) $a->serie_id,
            'mi_respuesta' => $mia?->pivot->respuesta,
            'puede' => [
                'editar' => $editar && $a->estado !== 'cancelado',
                'mover' => $editar && ! $a->cerrada(),
                'completar' => ($editar || (int) $a->responsable_id === $u->id) && ! $a->cerrada(),
                'cancelar' => $editar && $a->estado !== 'cancelado',
                'reasignar' => $this->acceso->puedeReasignarActividad($a, $u) && ! $a->cerrada(),
                'responder' => (bool) $mia && $a->estado !== 'cancelado',
                'duplicar' => true,
            ],
        ];
    }

    /** Lo privado de otra persona: solo se sabe que está ocupada. */
    public function ocupado(AgendaActividad $a): array
    {
        return [
            'key' => 'o-'.$a->id,
            'fuente' => 'ocupado',
            'origen' => 'manual',
            'titulo' => 'Ocupado',
            'tipo' => 'ocupado',
            'tipo_label' => 'Ocupado',
            'clase' => 'evento',
            'fecha' => $a->fecha_inicio->toDateString(),
            'fecha_fin' => $a->fecha_fin->toDateString(),
            'hora' => $a->hora_inicio && ! $a->todo_el_dia ? substr($a->hora_inicio, 0, 5) : null,
            'hora_fin' => $a->hora_fin && ! $a->todo_el_dia ? substr($a->hora_fin, 0, 5) : null,
            'todo_el_dia' => $a->todo_el_dia || ! $a->hora_inicio,
            'estado' => 'programado',
            'estado_label' => '',
            'vencido' => false,
            'prioridad' => 'normal',
            'responsable' => ['id' => (int) $a->responsable_id, 'nombre' => null],
            'cliente' => null,
            'visibilidad' => 'privado',
            'ocupa_horario' => true,
            'puede' => array_fill_keys(['editar', 'mover', 'completar', 'cancelar', 'reasignar', 'responder', 'duplicar'], false),
        ];
    }

    public function deCongreso(Congress $c, User $u): array
    {
        return [
            'key' => 'c-'.$c->id,
            'fuente' => 'congreso',
            'origen' => 'manual',
            'titulo' => 'Congreso: '.$c->nombre,
            'tipo' => 'congreso',
            'tipo_label' => 'Congreso',
            'clase' => 'evento',
            'fecha' => $c->fecha_inicio->toDateString(),
            'fecha_fin' => $c->fecha_finalizacion->toDateString(),
            'hora' => $c->hora_montaje?->format('H:i'),
            'hora_fin' => null,
            'todo_el_dia' => true,
            'estado' => 'programado',
            'estado_label' => 'Programado',
            'vencido' => false,
            'prioridad' => 'normal',
            'responsable' => ['id' => null, 'nombre' => null],
            'cliente' => null,
            'ubicacion' => $c->direccion,
            'url' => $u->can('congresos.ver') ? route('inventory.congresos.index') : null,
            'visibilidad' => 'empresa',
            'ocupa_horario' => false,
            'puede' => array_fill_keys(['editar', 'mover', 'completar', 'cancelar', 'reasignar', 'responder', 'duplicar'], false),
        ];
    }

    // ===================== Paneles =====================

    public function paneles(User $u, array $f = []): array
    {
        $hoy = $this->hoy();
        $fHoy = $hoy->toDateString();
        $propios = array_diff_key($f, array_flip(['ambito']));
        $propios['ambito'] = 'todas';

        $deHoy = $this->rango($u, $fHoy, $fHoy, $propios);
        $proximos = collect($this->rango($u, $hoy->addDay()->toDateString(), $hoy->addDays(7)->toDateString(), $propios))
            ->reject(fn ($i) => in_array($i['estado'], ['cancelado', 'completado', 'realizado'], true));

        return [
            'hoy' => collect($deHoy)->reject(fn ($i) => $i['estado'] === 'cancelado')->values()->all(),
            'vencidos' => $this->vencidos($u, $f),
            'proximos' => $proximos->values()->all(),
            'sin_seguimiento' => $this->sinSeguimiento($u),
            'indicadores' => [
                'hoy' => collect($deHoy)->whereIn('estado', ['pendiente', 'en_proceso', 'programado'])->count(),
                'vencidos' => count($this->vencidos($u, $f)),
                'completadas_semana' => $this->completadasSemana($u, $f),
                'proximos_eventos' => $proximos->where('clase', 'evento')->count(),
            ],
        ];
    }

    /** Tareas y seguimientos cuya fecha pasó y siguen abiertos (sin límite de rango). */
    public function vencidos(User $u, array $f = []): array
    {
        $usuarios = $this->usuariosObjetivo($u, $f);
        $hoy = $this->hoy()->toDateString();
        $lista = collect();

        ClienteSeguimiento::query()->with(['customer:id,nombre,apellido,etapa,asesor_id', 'responsable:id,name'])
            ->whereIn('user_id', $usuarios ?: [0])->pendientes()->where('fecha', '<=', $hoy)
            ->orderBy('fecha')->limit(200)->get()
            ->filter(fn ($s) => $s->vencido() && $this->acceso->nivelSeguimiento($s, $u) === 'completo')
            ->each(fn ($s) => $lista->push($this->deSeguimiento($s, $u)));

        AgendaActividad::query()->with(['responsable:id,name', 'customer:id,nombre,apellido', 'participantes:id,name', 'roles:id', 'equipos:id'])
            ->where('clase', 'tarea')->whereIn('estado', ['pendiente', 'en_proceso'])
            ->whereIn('responsable_id', $usuarios ?: [0])->where('fecha_fin', '<=', $hoy)
            ->orderBy('fecha_fin')->limit(200)->get()
            ->filter(fn ($a) => $a->vencida() && $this->acceso->nivelActividad($a, $u) === 'completo')
            ->each(fn ($a) => $lista->push($this->deActividad($a, $u)));

        return $lista->sortBy('fecha')->values()->all();
    }

    private function completadasSemana(User $u, array $f): int
    {
        $usuarios = $this->usuariosObjetivo($u, $f);
        $desde = CarbonImmutable::now(config('agenda.timezone'))->startOfWeek()->utc();

        return ClienteSeguimiento::whereIn('user_id', $usuarios ?: [0])->where('estado', 'completado')->where('hecho_en', '>=', $desde)->count()
            + AgendaActividad::whereIn('responsable_id', $usuarios ?: [0])->whereIn('estado', ['completado', 'realizado'])->where('completado_en', '>=', $desde)->count();
    }

    /**
     * Prospectos activos sin ningún seguimiento abierto: no se les inventa
     * fecha, solo se listan para que alguien decida.
     */
    public function sinSeguimiento(User $u, int $limite = 30): array
    {
        $q = Customer::visiblesPara($u)
            ->where('etapa', 'prospecto')->where('activo', true)
            ->whereDoesntHave('seguimientos', fn ($s) => $s->whereIn('estado', ClienteSeguimiento::ABIERTOS));

        $total = (clone $q)->count();

        return [
            'total' => $total,
            'lista' => $q->with('asesor:id,name')->latest('updated_at')->limit($limite)->get()->map(fn (Customer $c) => [
                'id' => $c->id,
                'nombre' => trim($c->nombre.' '.$c->apellido),
                'asesor' => $c->asesor?->name,
                'telefono' => $c->telefono,
                'alta' => $c->created_at?->format('d/m/Y'),
                'url' => route('commercial.clientes.show', $c).'#seguimientos',
            ])->all(),
        ];
    }

    // ===================== Carga de trabajo =====================

    public function carga(User $u): array
    {
        $ids = $this->acceso->supervisados($u);
        $hoy = $this->hoy()->toDateString();
        $semana = $this->hoy()->addDays(7)->toDateString();

        $seg = ClienteSeguimiento::query()->pendientes()->whereIn('user_id', $ids)
            ->selectRaw('user_id, COUNT(*) total, SUM(fecha < ?) vencidos, SUM(fecha = ?) hoy, SUM(fecha > ? AND fecha <= ?) semana', [$hoy, $hoy, $hoy, $semana])
            ->groupBy('user_id')->get()->keyBy('user_id');

        $act = AgendaActividad::query()->whereIn('estado', ['pendiente', 'en_proceso', 'programado'])->whereIn('responsable_id', $ids)
            ->where('fecha_fin', '>=', $this->hoy()->subDays(60)->toDateString())
            ->selectRaw("responsable_id, COUNT(*) total, SUM(clase = 'tarea' AND fecha_fin < ?) vencidos, SUM(fecha_inicio <= ? AND fecha_fin >= ?) hoy, SUM(fecha_inicio > ? AND fecha_inicio <= ?) semana", [$hoy, $hoy, $hoy, $hoy, $semana])
            ->groupBy('responsable_id')->get()->keyBy('responsable_id');

        return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])->map(function (User $p) use ($seg, $act) {
            $s = $seg->get($p->id);
            $a = $act->get($p->id);

            return [
                'id' => $p->id,
                'nombre' => $p->name,
                'seguimientos' => (int) ($s->total ?? 0),
                'actividades' => (int) ($a->total ?? 0),
                'vencidos' => (int) ($s->vencidos ?? 0) + (int) ($a->vencidos ?? 0),
                'hoy' => (int) ($s->hoy ?? 0) + (int) ($a->hoy ?? 0),
                'semana' => (int) ($s->semana ?? 0) + (int) ($a->semana ?? 0),
            ];
        })->sortByDesc(fn ($f) => $f['vencidos'] * 1000 + $f['hoy'] * 10 + $f['semana'])->values()->all();
    }

    // ===================== Exportar a calendario (ICS) =====================

    public function ics(User $u, string $desde, string $hasta): string
    {
        $tz = config('agenda.timezone');
        $esc = fn (?string $t) => str_replace(["\\", ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], (string) $t);
        $lineas = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//'.$esc(config('app.name')).'//Agenda//ES', 'CALSCALE:GREGORIAN', 'X-WR-TIMEZONE:'.$tz];

        foreach ($this->rango($u, $desde, $hasta, ['ambito' => 'todas']) as $i) {
            if ($i['estado'] === 'cancelado') {
                continue;
            }
            $lineas[] = 'BEGIN:VEVENT';
            $lineas[] = 'UID:'.$i['key'].'@'.parse_url(config('app.url'), PHP_URL_HOST);
            $lineas[] = 'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z');

            if ($i['todo_el_dia'] || ! $i['hora']) {
                $lineas[] = 'DTSTART;VALUE=DATE:'.str_replace('-', '', $i['fecha']);
                $lineas[] = 'DTEND;VALUE=DATE:'.CarbonImmutable::parse($i['fecha_fin'])->addDay()->format('Ymd');
            } else {
                $ini = CarbonImmutable::parse($i['fecha'].' '.$i['hora'], $tz);
                $fin = $i['hora_fin'] ? CarbonImmutable::parse($i['fecha_fin'].' '.$i['hora_fin'], $tz) : $ini->addMinutes((int) config('agenda.duracion_default_min'));
                $lineas[] = 'DTSTART:'.$ini->utc()->format('Ymd\THis\Z');
                $lineas[] = 'DTEND:'.$fin->utc()->format('Ymd\THis\Z');
            }

            // Lo "Ocupado" sale sin ningún dato; lo demás, con lo que el usuario ya puede ver.
            $lineas[] = 'SUMMARY:'.$esc($i['titulo']);
            if ($i['fuente'] !== 'ocupado') {
                $desc = array_filter([$i['tipo_label'] ?? null, $i['cliente']['nombre'] ?? null, $i['motivo'] ?? null, $i['estado_label'] ?? null]);
                $lineas[] = 'DESCRIPTION:'.$esc(implode(' · ', $desc));
                if (! empty($i['ubicacion'])) {
                    $lineas[] = 'LOCATION:'.$esc($i['ubicacion']);
                }
            } else {
                $lineas[] = 'CLASS:PRIVATE';
            }
            $lineas[] = 'TRANSP:'.(! empty($i['ocupa_horario']) ? 'OPAQUE' : 'TRANSPARENT');
            $lineas[] = 'END:VEVENT';
        }

        $lineas[] = 'END:VCALENDAR';

        // Las líneas de más de 75 caracteres se doblan (RFC 5545).
        return implode("\r\n", array_map(fn ($l) => implode("\r\n ", mb_str_split($l, 70)), $lineas))."\r\n";
    }
}
