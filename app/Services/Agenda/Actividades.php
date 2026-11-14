<?php

namespace App\Services\Agenda;

use App\Models\AgendaActividad;
use App\Models\Customer;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Actividades y eventos manuales de la agenda.
 *
 * Un evento compartido es una sola fila: participantes, departamentos y
 * equipos lo ven por relación, así que un cambio se ve igual para todos.
 * Las repeticiones se guardan como ocurrencias reales con la misma
 * serie_id; se puede editar una sola o "esta y las siguientes".
 */
class Actividades
{
    public function __construct(
        private readonly Acceso $acceso,
        private readonly Recordatorios $recordatorios,
        private readonly Avisos $avisos,
    ) {}

    public function reglas(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'tipo' => ['required', Rule::in(array_keys(AgendaActividad::TIPOS))],
            'fecha_inicio' => ['required', 'date'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'hora_fin' => ['nullable', 'date_format:H:i'],
            'todo_el_dia' => ['nullable', 'boolean'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
            'participantes' => ['nullable', 'array', 'max:200'],
            'participantes.*' => ['integer', 'exists:users,id'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'equipos' => ['nullable', 'array'],
            'equipos.*' => ['integer', 'exists:agenda_equipos,id'],
            'customer_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'enlace' => ['nullable', 'url', 'max:500'],
            'prioridad' => ['nullable', Rule::in(array_keys(AgendaActividad::PRIORIDADES))],
            'visibilidad' => ['nullable', Rule::in(array_keys(AgendaActividad::VISIBILIDADES))],
            'ocupa_horario' => ['nullable', 'boolean'],
            'recordatorios' => ['nullable', 'array'],
            'recordatorios.*' => ['integer', Rule::in(array_keys(config('agenda.recordatorios')))],
            'recurrencia' => ['nullable', 'array'],
            'recurrencia.freq' => ['nullable', Rule::in(['ninguna', 'diaria', 'semanal', 'mensual'])],
            'recurrencia.intervalo' => ['nullable', 'integer', 'min:1', 'max:30'],
            'recurrencia.dias' => ['nullable', 'array'],
            'recurrencia.dias.*' => ['integer', 'between:1,7'],
            'recurrencia.hasta' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'recurrencia.veces' => ['nullable', 'integer', 'min:1', 'max:'.config('agenda.recurrencia_max_ocurrencias')],
            'checklist' => ['nullable', 'array', 'max:50'],
            'checklist.*' => ['string', 'max:255'],
            'confirmar_conflictos' => ['nullable', 'boolean'],
        ];
    }

    public const MENSAJES = [
        'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser antes del inicio.',
        'enlace.url' => 'El enlace debe ser una dirección completa (https://...).',
        'recurrencia.hasta.after_or_equal' => 'La repetición no puede terminar antes de empezar.',
    ];

    /** Limpia y completa los datos validados: fechas, horas, defaults. */
    public function normalizar(array $d, User $u, ?AgendaActividad $actual = null): array
    {
        $tipo = $d['tipo'] ?? $actual?->tipo;
        $todoElDia = (bool) ($d['todo_el_dia'] ?? false);

        $d['fecha_fin'] = $d['fecha_fin'] ?? $d['fecha_inicio'];
        if ($todoElDia) {
            $d['hora_inicio'] = null;
            $d['hora_fin'] = null;
        }
        if (empty($d['hora_inicio'])) {
            $d['hora_inicio'] = null;
            $d['hora_fin'] = null;
        }
        if (! empty($d['hora_fin']) && $d['fecha_fin'] === $d['fecha_inicio'] && $d['hora_fin'] <= $d['hora_inicio']) {
            throw ValidationException::withMessages(['hora_fin' => 'La hora de fin debe ser después del inicio.']);
        }

        $d['todo_el_dia'] = $todoElDia;
        $d['clase'] = AgendaActividad::claseDe($tipo);
        $d['prioridad'] = $d['prioridad'] ?? 'normal';
        $d['visibilidad'] = $d['visibilidad'] ?? ($tipo === 'evento_empresa' ? 'empresa' : 'participantes');
        $d['ocupa_horario'] = array_key_exists('ocupa_horario', $d) && $d['ocupa_horario'] !== null
            ? (bool) $d['ocupa_horario']
            : (bool) (AgendaActividad::TIPOS[$tipo][2] ?? true);
        $d['responsable_id'] = (int) ($d['responsable_id'] ?? $actual?->responsable_id ?? $u->id);

        if ($d['visibilidad'] !== 'grupo') {
            $d['roles'] = [];
            $d['equipos'] = [];
        } elseif (empty($d['roles']) && empty($d['equipos'])) {
            throw ValidationException::withMessages(['roles' => 'Elige al menos un departamento o equipo.']);
        }

        return $d;
    }

    /** Permisos que no caben en una regla de validación. */
    public function autorizar(array $d, User $u, ?AgendaActividad $actual = null): void
    {
        if (in_array($d['visibilidad'], ['grupo', 'empresa'], true) && ! $u->can('agenda.empresa')) {
            throw ValidationException::withMessages(['visibilidad' => 'No tienes permiso para publicar eventos de departamento, equipo o empresa.']);
        }

        $cambiaResponsable = ! $actual || (int) $actual->responsable_id !== (int) $d['responsable_id'];
        if ($cambiaResponsable && ! $this->acceso->puedeAsignarA($u, (int) $d['responsable_id'])) {
            throw ValidationException::withMessages(['responsable_id' => 'No puedes asignar actividades a esa persona.']);
        }

        if (! empty($d['customer_id']) && (! $actual || (int) $actual->customer_id !== (int) $d['customer_id'])) {
            $cliente = Customer::find($d['customer_id']);
            if (! $cliente || ! $cliente->visiblePara($u)) {
                throw ValidationException::withMessages(['customer_id' => 'Ese cliente no está entre los que puedes ver.']);
            }
        }
    }

    // ===================== Repeticiones =====================

    /** @return array<int, string> fechas (Y-m-d) de inicio de cada ocurrencia */
    public function fechasDeRecurrencia(string $inicio, ?array $regla): array
    {
        $freq = $regla['freq'] ?? 'ninguna';
        if (! $regla || $freq === 'ninguna') {
            return [$inicio];
        }

        $tz = config('agenda.timezone');
        $max = (int) config('agenda.recurrencia_max_ocurrencias');
        $veces = min((int) ($regla['veces'] ?? $max), $max);
        $intervalo = max(1, (int) ($regla['intervalo'] ?? 1));
        $primera = CarbonImmutable::parse($inicio, $tz);
        $tope = CarbonImmutable::parse($regla['hasta'] ?? $primera->addMonths((int) config('agenda.recurrencia_max_meses'))->toDateString(), $tz);
        $tope = $tope->min($primera->addMonths((int) config('agenda.recurrencia_max_meses')));

        $fechas = [];

        if ($freq === 'semanal') {
            $dias = array_map('intval', $regla['dias'] ?? []) ?: [$primera->dayOfWeekIso];
            sort($dias);
            for ($semana = $primera->startOfWeek(); $semana->lte($tope) && count($fechas) < $veces; $semana = $semana->addWeeks($intervalo)) {
                foreach ($dias as $dia) {
                    $f = $semana->addDays($dia - 1);
                    if ($f->lt($primera) || $f->gt($tope) || count($fechas) >= $veces) {
                        continue;
                    }
                    $fechas[] = $f->toDateString();
                }
            }

            return $fechas;
        }

        for ($i = 0; count($fechas) < $veces; $i++) {
            $f = $freq === 'diaria' ? $primera->addDays($i * $intervalo) : $primera->addMonthsNoOverflow($i * $intervalo);
            if ($f->gt($tope)) {
                break;
            }
            $fechas[] = $f->toDateString();
        }

        return $fechas;
    }

    // ===================== Choques de horario =====================

    /**
     * Actividades que ocupan el horario de las mismas personas.
     *
     * @return array<int, array{usuario:string, titulo:string, cuando:string}>
     */
    public function conflictos(array $d, User $u, array $ignorar = []): array
    {
        if (! $d['ocupa_horario']) {
            return [];
        }

        $personas = collect([(int) $d['responsable_id']])->merge(array_map('intval', $d['participantes'] ?? []))->unique()->values();
        $tz = config('agenda.timezone');
        $inicio = CarbonImmutable::parse($d['fecha_inicio'].' '.($d['hora_inicio'] ?? '00:00'), $tz);
        $fin = $d['hora_inicio']
            ? CarbonImmutable::parse($d['fecha_fin'].' '.($d['hora_fin'] ?? CarbonImmutable::parse($d['hora_inicio'])->addMinutes((int) config('agenda.duracion_default_min'))->format('H:i')), $tz)
            : CarbonImmutable::parse($d['fecha_fin'].' 23:59', $tz);
        if ($fin->lte($inicio)) {
            $fin = $inicio->addMinutes((int) config('agenda.duracion_default_min'));
        }

        $candidatas = AgendaActividad::query()
            ->with(['participantes:id,name', 'responsable:id,name'])
            ->where('ocupa_horario', true)
            ->whereNotIn('estado', ['cancelado', 'completado', 'realizado'])
            ->whereNotIn('id', $ignorar)
            ->where('fecha_inicio', '<=', $d['fecha_fin'])
            ->where('fecha_fin', '>=', $d['fecha_inicio'])
            ->where(fn ($q) => $q->whereIn('responsable_id', $personas)
                ->orWhereHas('participantes', fn ($p) => $p->whereIn('users.id', $personas)->where('respuesta', '!=', 'rechazada')))
            ->get();

        $choques = [];
        foreach ($candidatas as $c) {
            if (! ($c->inicio()->lt($fin) && $c->fin()->gt($inicio))) {
                continue;
            }
            $ocupados = collect([$c->responsable])->merge($c->participantes)->filter()
                ->filter(fn ($p) => $personas->contains((int) $p->id))->pluck('name')->unique();
            $visible = $this->acceso->nivelActividad($c, $u) === 'completo';
            $choques[] = [
                'usuario' => $ocupados->implode(', '),
                'titulo' => $visible ? $c->titulo : 'Ocupado',
                'cuando' => $c->fecha_inicio->format('d/m/Y').($c->hora_inicio && ! $c->todo_el_dia ? ' '.substr($c->hora_inicio, 0, 5).($c->hora_fin ? '–'.substr($c->hora_fin, 0, 5) : '') : ' (todo el día)'),
            ];
        }

        return $choques;
    }

    // ===================== Crear =====================

    /** @return Collection<int, AgendaActividad> todas las ocurrencias creadas */
    public function crear(array $d, User $u): Collection
    {
        $d = $this->normalizar($d, $u);
        $this->autorizar($d, $u);

        $fechas = $this->fechasDeRecurrencia($d['fecha_inicio'], $d['recurrencia'] ?? null);
        $duracion = CarbonImmutable::parse($d['fecha_inicio'])->diffInDays(CarbonImmutable::parse($d['fecha_fin']));
        $serie = count($fechas) > 1 ? (string) Str::uuid() : null;

        $creadas = DB::transaction(function () use ($d, $u, $fechas, $duracion, $serie) {
            $lista = collect();
            foreach ($fechas as $fecha) {
                $a = AgendaActividad::create($this->columnas($d, $u) + [
                    'fecha_inicio' => $fecha,
                    'fecha_fin' => CarbonImmutable::parse($fecha)->addDays($duracion)->toDateString(),
                    'estado' => AgendaActividad::estadoInicial($d['tipo']),
                    'serie_id' => $serie,
                    'recurrencia' => $serie ? $d['recurrencia'] : null,
                    'created_by' => $u->id,
                    'updated_by' => $u->id,
                ]);
                $this->sincronizarRelaciones($a, $d);
                foreach (array_values(array_filter($d['checklist'] ?? [], 'filled')) as $i => $texto) {
                    $a->checklistAgenda()->create(['texto' => $texto, 'orden' => $i]);
                }
                Historial::registrar($a, 'creado', $u->id, null, $this->foto($a));
                $this->recordatorios->programar($a);
                $lista->push($a);
            }

            return $lista;
        });

        $this->avisarAlta($creadas->first(), $u, count($fechas));

        return $creadas;
    }

    private function columnas(array $d, User $u): array
    {
        return [
            'titulo' => $d['titulo'],
            'descripcion' => $d['descripcion'] ?? null,
            'tipo' => $d['tipo'],
            'clase' => $d['clase'],
            'hora_inicio' => $d['hora_inicio'],
            'hora_fin' => $d['hora_fin'] ?? null,
            'todo_el_dia' => $d['todo_el_dia'],
            'responsable_id' => $d['responsable_id'],
            'customer_id' => $d['customer_id'] ?? null,
            'ubicacion' => $d['ubicacion'] ?? null,
            'enlace' => $d['enlace'] ?? null,
            'prioridad' => $d['prioridad'],
            'visibilidad' => $d['visibilidad'],
            'ocupa_horario' => $d['ocupa_horario'],
            'recordatorios' => array_key_exists('recordatorios', $d) ? array_map('intval', (array) $d['recordatorios']) : null,
        ];
    }

    private function sincronizarRelaciones(AgendaActividad $a, array $d): void
    {
        $ids = collect($d['participantes'] ?? [])->map(fn ($id) => (int) $id)->reject(fn ($id) => $id === (int) $a->responsable_id)->unique();
        // Conserva la respuesta de quien ya contestó.
        $actuales = $a->participantes()->get()->keyBy('id');
        $sync = [];
        foreach ($ids as $id) {
            $sync[$id] = $actuales->has($id)
                ? ['respuesta' => $actuales[$id]->pivot->respuesta, 'respondido_en' => $actuales[$id]->pivot->respondido_en]
                : ['respuesta' => 'pendiente'];
        }
        $a->participantes()->sync($sync);
        $a->roles()->sync($d['roles'] ?? []);
        $a->equipos()->sync($d['equipos'] ?? []);
    }

    // ===================== Editar =====================

    /**
     * @param  string  $alcance  'una' o 'futuras' (esta y las siguientes de la serie)
     */
    public function actualizar(AgendaActividad $a, array $d, User $u, string $alcance = 'una'): AgendaActividad
    {
        abort_unless($this->acceso->puedeEditarActividad($a, $u), 403, 'No puedes modificar esta actividad.');

        $d = $this->normalizar($d + ['tipo' => $a->tipo], $u, $a);
        $this->autorizar($d, $u, $a);

        if ((int) $a->responsable_id !== (int) $d['responsable_id'] && ! $this->acceso->puedeReasignarActividad($a, $u)) {
            throw ValidationException::withMessages(['responsable_id' => 'No tienes permiso para reasignar esta actividad.']);
        }

        $afectadas = $this->afectadas($a, $alcance);
        $deltaDias = CarbonImmutable::parse($a->fecha_inicio->toDateString())->diffInDays(CarbonImmutable::parse($d['fecha_inicio']), false);
        $duracion = CarbonImmutable::parse($d['fecha_inicio'])->diffInDays(CarbonImmutable::parse($d['fecha_fin']));
        $reglaCambio = $alcance === 'futuras' && json_encode($d['recurrencia'] ?? null) !== json_encode($a->recurrencia) && isset($d['recurrencia']);

        $avisos = DB::transaction(function () use ($a, $d, $u, $afectadas, $deltaDias, $duracion, $reglaCambio) {
            $cambiosRelevantes = [];

            foreach ($afectadas as $o) {
                $antes = $this->foto($o);
                $inicio = $o->is($a) ? $d['fecha_inicio'] : CarbonImmutable::parse($o->fecha_inicio->toDateString())->addDays($deltaDias)->toDateString();
                $o->update($this->columnas($d, $u) + [
                    'clase' => $d['clase'],
                    'fecha_inicio' => $inicio,
                    'fecha_fin' => CarbonImmutable::parse($inicio)->addDays($duracion)->toDateString(),
                    'updated_by' => $u->id,
                ]);
                $this->sincronizarRelaciones($o, $d);
                [$x, $y] = Historial::diferencias($antes, $this->foto($o));
                if ($y) {
                    $accion = isset($y['fecha_inicio']) || isset($y['hora_inicio']) ? 'reprogramado' : (isset($y['responsable_id']) && count($y) === 1 ? 'reasignado' : 'editado');
                    Historial::registrar($o, $accion, $u->id, $x, $y, $d['motivo'] ?? null);
                    $cambiosRelevantes[$o->id] = [$x, $y];
                }
                $this->recordatorios->programar($o);
            }

            if ($reglaCambio) {
                $this->regenerarSerie($a->fresh(), $d, $u);
            }

            return $cambiosRelevantes;
        });

        if ($avisos) {
            [$x, $y] = $avisos[$a->id] ?? reset($avisos);
            $this->avisarCambio($a->fresh(), $u, $x, $y, count($afectadas));
        }

        return $a->fresh();
    }

    /** Cambia la regla: quita las siguientes ocurrencias y genera las nuevas desde esta. */
    private function regenerarSerie(AgendaActividad $a, array $d, User $u): void
    {
        AgendaActividad::where('serie_id', $a->serie_id)->where('fecha_inicio', '>', $a->fecha_inicio)->get()
            ->each(function (AgendaActividad $o) use ($u) {
                $this->recordatorios->cancelar($o);
                Historial::registrar($o, 'cancelado', $u->id, null, null, 'Se cambió la regla de repetición');
                $o->delete();
            });

        $fechas = $this->fechasDeRecurrencia($a->fecha_inicio->toDateString(), $d['recurrencia']);
        $duracion = $a->fecha_inicio->diffInDays($a->fecha_fin);
        $serie = (string) Str::uuid();
        $a->update(['serie_id' => $serie, 'recurrencia' => $d['recurrencia']]);

        foreach (array_slice($fechas, 1) as $fecha) {
            $n = $a->replicate(['legado_id']);
            $n->fill(['fecha_inicio' => $fecha, 'fecha_fin' => CarbonImmutable::parse($fecha)->addDays($duracion)->toDateString(), 'estado' => AgendaActividad::estadoInicial($a->tipo), 'completado_en' => null, 'cancelado_en' => null, 'resultado' => null]);
            $n->save();
            $this->sincronizarRelaciones($n, $d);
            Historial::registrar($n, 'creado', $u->id, null, $this->foto($n));
            $this->recordatorios->programar($n);
        }
    }

    /** @return Collection<int, AgendaActividad> */
    private function afectadas(AgendaActividad $a, string $alcance): Collection
    {
        if ($alcance !== 'futuras' || ! $a->serie_id) {
            return collect([$a]);
        }

        return AgendaActividad::where('serie_id', $a->serie_id)
            ->where('fecha_inicio', '>=', $a->fecha_inicio->toDateString())
            ->whereNotIn('estado', ['completado', 'realizado', 'cancelado'])
            ->orderBy('fecha_inicio')->get()
            ->prepend($a)->unique('id')->values();
    }

    /** Arrastrar en el calendario o "Mover" en el formulario. Conserva la duración. */
    public function mover(AgendaActividad $a, string $fecha, string|null|false $hora, User $u, ?string $motivo = null, string $alcance = 'una'): AgendaActividad
    {
        $datos = [
            'titulo' => $a->titulo, 'descripcion' => $a->descripcion, 'tipo' => $a->tipo,
            'fecha_inicio' => $fecha,
            'fecha_fin' => CarbonImmutable::parse($fecha)->addDays($a->fecha_inicio->diffInDays($a->fecha_fin))->toDateString(),
            'hora_inicio' => $hora === false ? ($a->hora_inicio ? substr($a->hora_inicio, 0, 5) : null) : $hora,
            'todo_el_dia' => $hora === false ? $a->todo_el_dia : ($hora === null),
            'responsable_id' => $a->responsable_id,
            'participantes' => $a->participantes()->pluck('users.id')->all(),
            'roles' => $a->roles()->pluck('roles.id')->all(),
            'equipos' => $a->equipos()->pluck('agenda_equipos.id')->all(),
            'customer_id' => $a->customer_id, 'ubicacion' => $a->ubicacion, 'enlace' => $a->enlace,
            'prioridad' => $a->prioridad, 'visibilidad' => $a->visibilidad, 'ocupa_horario' => $a->ocupa_horario,
            'recordatorios' => $a->recordatorios,
            'motivo' => $motivo,
        ];

        // La hora de fin se corre lo mismo que el inicio.
        if ($a->hora_inicio && $a->hora_fin && $datos['hora_inicio'] && ! $datos['todo_el_dia']) {
            $min = CarbonImmutable::parse($a->hora_inicio)->diffInMinutes(CarbonImmutable::parse($a->hora_fin), false);
            $finNuevo = CarbonImmutable::parse($datos['hora_inicio'])->addMinutes($min);
            $datos['hora_fin'] = $finNuevo->isSameDay(CarbonImmutable::parse($datos['hora_inicio'])) ? $finNuevo->format('H:i') : '23:59';
        } elseif ($a->hora_fin && $hora === false) {
            $datos['hora_fin'] = substr($a->hora_fin, 0, 5);
        }

        if ($a->recordatorios === null) {
            unset($datos['recordatorios']);
        }

        return $this->actualizar($a, $datos, $u, $alcance);
    }

    // ===================== Estado, cancelar, reasignar, responder =====================

    public function cambiarEstado(AgendaActividad $a, string $estado, User $u, ?string $resultado = null): AgendaActividad
    {
        $puede = $this->acceso->puedeEditarActividad($a, $u) || (int) $a->responsable_id === $u->id;
        abort_unless($puede, 403, 'No puedes cambiar el estado de esta actividad.');

        if (! array_key_exists($estado, AgendaActividad::estadosDe($a->clase)) || $estado === 'cancelado') {
            throw ValidationException::withMessages(['estado' => 'Estado no válido para esta actividad.']);
        }

        DB::transaction(function () use ($a, $estado, $u, $resultado) {
            $antes = $this->foto($a);
            $cerrar = in_array($estado, ['completado', 'realizado'], true);
            $a->update([
                'estado' => $estado,
                'resultado' => $resultado ?? $a->resultado,
                'completado_en' => $cerrar ? now() : null,
                'completado_por' => $cerrar ? $u->id : null,
                'updated_by' => $u->id,
            ]);
            Historial::registrar($a, $cerrar ? 'completado' : ($antes['estado'] !== $estado && in_array($antes['estado'], ['completado', 'realizado'], true) ? 'reabierto' : 'estado'),
                $u->id, ['estado' => $antes['estado']], ['estado' => $estado], $resultado);
            $this->recordatorios->programar($a);
            if ($cerrar) {
                Avisos::marcarLeidos($a->llaveAgenda());
            }
        });

        return $a->fresh();
    }

    public function cancelar(AgendaActividad $a, User $u, ?string $motivo, string $alcance = 'una'): int
    {
        abort_unless($this->acceso->puedeEditarActividad($a, $u), 403, 'No puedes cancelar esta actividad.');

        $afectadas = $this->afectadas($a, $alcance)->reject(fn ($o) => $o->estado === 'cancelado');

        DB::transaction(function () use ($afectadas, $u, $motivo) {
            foreach ($afectadas as $o) {
                $o->update(['estado' => 'cancelado', 'cancelado_en' => now(), 'motivo_cancelacion' => $motivo, 'updated_by' => $u->id]);
                Historial::registrar($o, 'cancelado', $u->id, null, null, $motivo);
                $this->recordatorios->cancelar($o);
                Avisos::marcarLeidos($o->llaveAgenda());
            }
        });

        if ($afectadas->isNotEmpty()) {
            $o = $afectadas->first();
            $this->avisos->enviar($this->interesados($o), 'cancelada', 'Cancelado: '.$o->titulo,
                $o->tipoLabel().' del '.$o->fecha_inicio->format('d/m/Y').($afectadas->count() > 1 ? ' y '.($afectadas->count() - 1).' repetición(es) siguientes' : '').($motivo ? ' · '.$motivo : ''),
                route('agenda.index', ['item' => $o->llaveAgenda()]), $o->llaveAgenda(), $u->id);
        }

        return $afectadas->count();
    }

    public function responder(AgendaActividad $a, User $u, string $respuesta, ?string $comentario = null): void
    {
        abort_unless($this->acceso->esParticipante($a, $u), 403, 'No estás invitado a este evento.');
        abort_unless(in_array($respuesta, ['aceptada', 'rechazada', 'pendiente'], true), 422);

        DB::transaction(function () use ($a, $u, $respuesta, $comentario) {
            $previa = $a->participantes()->where('users.id', $u->id)->first()?->pivot->respuesta;
            $a->participantes()->updateExistingPivot($u->id, ['respuesta' => $respuesta, 'respondido_en' => now(), 'comentario' => $comentario]);
            if ($previa !== $respuesta) {
                Historial::registrar($a, 'respuesta', $u->id, ['respuesta' => $previa], ['respuesta' => $respuesta], $comentario);
            }
            // Quien rechaza deja de recibir recordatorios.
            $this->recordatorios->programar($a);
        });

        if ($a->responsable && (int) $a->responsable_id !== $u->id) {
            $this->avisos->enviar($a->responsable, 'invitacion', $u->name.' '.($respuesta === 'aceptada' ? 'aceptó' : ($respuesta === 'rechazada' ? 'rechazó' : 'dejó pendiente')).': '.$a->titulo,
                $comentario ?: $a->fecha_inicio->format('d/m/Y'), route('agenda.index', ['item' => $a->llaveAgenda()]), $a->llaveAgenda());
        }
    }

    /** Copia una actividad manual (sola, sin repetición ni respuestas). */
    public function duplicar(AgendaActividad $a, User $u): AgendaActividad
    {
        abort_unless($this->acceso->nivelActividad($a, $u) === 'completo', 403);

        $d = [
            'titulo' => $a->titulo.' (copia)', 'descripcion' => $a->descripcion, 'tipo' => $a->tipo,
            'fecha_inicio' => $a->fecha_inicio->toDateString(), 'fecha_fin' => $a->fecha_fin->toDateString(),
            'hora_inicio' => $a->hora_inicio ? substr($a->hora_inicio, 0, 5) : null, 'hora_fin' => $a->hora_fin ? substr($a->hora_fin, 0, 5) : null,
            'todo_el_dia' => $a->todo_el_dia,
            // Si no puede asignarle al responsable original, la copia queda a su nombre.
            'responsable_id' => $this->acceso->puedeAsignarA($u, (int) $a->responsable_id) ? $a->responsable_id : $u->id,
            'participantes' => $a->participantes()->pluck('users.id')->all(),
            'roles' => $a->roles()->pluck('roles.id')->all(), 'equipos' => $a->equipos()->pluck('agenda_equipos.id')->all(),
            'customer_id' => $a->customer_id, 'ubicacion' => $a->ubicacion, 'enlace' => $a->enlace,
            'prioridad' => $a->prioridad, 'visibilidad' => $a->visibilidad, 'ocupa_horario' => $a->ocupa_horario,
            'recordatorios' => $a->recordatorios,
            'checklist' => $a->checklistAgenda()->pluck('texto')->all(),
        ];

        if (in_array($d['visibilidad'], ['grupo', 'empresa'], true) && ! $u->can('agenda.empresa')) {
            $d['visibilidad'] = 'participantes';
        }

        return $this->crear($d, $u)->first();
    }

    // ===================== Avisos =====================

    /** Responsable, participantes (que no rechazaron) y destinatarios del grupo/empresa. */
    public function interesados(AgendaActividad $a, bool $conDestinatarios = true): Collection
    {
        $gente = collect([$a->responsable])
            ->merge($a->participantes()->wherePivot('respuesta', '!=', 'rechazada')->get());

        if ($conDestinatarios) {
            $gente = $gente->merge($this->destinatarios($a));
        }

        return $gente->filter()->unique('id')->values();
    }

    public function destinatarios(AgendaActividad $a): Collection
    {
        $aprobados = fn ($q) => $q->where('status', User::STATUS_APPROVED);

        return match ($a->visibilidad) {
            'empresa' => User::query()->tap($aprobados)->get(),
            'grupo' => User::query()->tap($aprobados)->where(function ($q) use ($a) {
                $q->whereIn('id', DB::table('role_user')->whereIn('role_id', $a->roles()->pluck('roles.id'))->select('user_id'))
                    ->orWhereIn('id', DB::table('agenda_equipo_usuario')->whereIn('equipo_id', $a->equipos()->pluck('agenda_equipos.id'))->select('user_id'));
            })->get(),
            default => collect(),
        };
    }

    private function cuando(AgendaActividad $a): string
    {
        $txt = $a->fecha_inicio->format('d/m/Y');
        if (! $a->fecha_inicio->equalTo($a->fecha_fin)) {
            $txt .= ' al '.$a->fecha_fin->format('d/m/Y');
        }

        return $txt.($a->hora_inicio && ! $a->todo_el_dia ? ' · '.substr($a->hora_inicio, 0, 5).($a->hora_fin ? '–'.substr($a->hora_fin, 0, 5) : '') : ' · todo el día');
    }

    private function avisarAlta(?AgendaActividad $a, User $u, int $ocurrencias): void
    {
        if (! $a) {
            return;
        }

        $url = route('agenda.index', ['item' => $a->llaveAgenda()]);
        $extra = ($ocurrencias > 1 ? ' · se repite ('.$ocurrencias.' veces)' : '').($a->ubicacion ? ' · '.$a->ubicacion : '');
        $ya = [$u->id];

        if ($a->responsable && (int) $a->responsable_id !== $u->id) {
            $this->avisos->enviar($a->responsable, 'asignada', 'Te asignaron: '.$a->titulo, $a->tipoLabel().' · '.$this->cuando($a).$extra.' · de '.$u->name, $url, $a->llaveAgenda());
            $ya[] = (int) $a->responsable_id;
        }

        $invitados = $a->participantes()->whereNotIn('users.id', $ya)->get();
        $this->avisos->enviar($invitados, 'invitacion', 'Invitación: '.$a->titulo, $a->tipoLabel().' · '.$this->cuando($a).$extra.' · organiza '.$u->name.'. Confirma tu asistencia en la agenda.', $url, $a->llaveAgenda());
        $ya = array_merge($ya, $invitados->pluck('id')->all());

        if ($a->clase === 'evento' && in_array($a->visibilidad, ['grupo', 'empresa'], true)) {
            $this->avisos->enviar($this->destinatarios($a)->whereNotIn('id', $ya), 'evento_empresa', 'Evento: '.$a->titulo, $a->tipoLabel().' · '.$this->cuando($a).$extra, $url, $a->llaveAgenda());
        }
    }

    private function avisarCambio(AgendaActividad $a, User $u, array $antes, array $despues, int $cuantas): void
    {
        $url = route('agenda.index', ['item' => $a->llaveAgenda()]);
        $extra = $cuantas > 1 ? ' (y '.($cuantas - 1).' repetición(es) siguientes)' : '';

        if (isset($despues['responsable_id'])) {
            $nuevo = User::find($despues['responsable_id']);
            $viejo = User::find($antes['responsable_id'] ?? 0);
            $this->avisos->enviar($nuevo, 'asignada', 'Te asignaron: '.$a->titulo, $a->tipoLabel().' · '.$this->cuando($a).' · lo reasignó '.$u->name, $url, $a->llaveAgenda(), $u->id);
            $this->avisos->enviar($viejo, 'reasignada', 'Reasignado: '.$a->titulo, 'Ahora lo lleva '.($nuevo?->name ?? 'otra persona').'.', $url, $a->llaveAgenda(), $u->id);
        }

        $relevante = array_intersect(array_keys($despues), ['fecha_inicio', 'fecha_fin', 'hora_inicio', 'hora_fin', 'todo_el_dia', 'ubicacion', 'enlace']);
        if ($relevante) {
            $que = in_array('ubicacion', $relevante, true) || in_array('enlace', $relevante, true) ? 'Cambio de lugar o fecha' : 'Cambio de fecha';
            $this->avisos->enviar($this->interesados($a, $a->clase === 'evento'), 'reprogramada', $que.': '.$a->titulo,
                'Ahora: '.$this->cuando($a).($a->ubicacion ? ' · '.$a->ubicacion : '').$extra.' · cambió '.$u->name, $url, $a->llaveAgenda(), $u->id);
        }
    }

    public function foto(AgendaActividad $a): array
    {
        return [
            'titulo' => $a->titulo,
            'tipo' => $a->tipo,
            'fecha_inicio' => $a->fecha_inicio?->toDateString(),
            'fecha_fin' => $a->fecha_fin?->toDateString(),
            'hora_inicio' => $a->hora_inicio ? substr($a->hora_inicio, 0, 5) : null,
            'hora_fin' => $a->hora_fin ? substr($a->hora_fin, 0, 5) : null,
            'todo_el_dia' => (bool) $a->todo_el_dia,
            'responsable_id' => (int) $a->responsable_id,
            'ubicacion' => $a->ubicacion,
            'enlace' => $a->enlace,
            'prioridad' => $a->prioridad,
            'visibilidad' => $a->visibilidad,
            'estado' => $a->estado,
        ];
    }
}
