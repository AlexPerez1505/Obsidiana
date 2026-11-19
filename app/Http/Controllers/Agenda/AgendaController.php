<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\AgendaActividad;
use App\Models\AgendaChecklist;
use App\Models\AgendaEquipo;
use App\Models\AgendaRecordatorio;
use App\Models\ClienteSeguimiento;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use App\Services\Agenda\Acceso;
use App\Services\Agenda\Actividades;
use App\Services\Agenda\Avisos;
use App\Services\Agenda\Calendario;
use App\Services\Agenda\Recordatorios;
use App\Services\Clientes\SeguimientosCliente;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Agenda empresarial y personal.
 *
 * La pantalla es una sola (calendario + panel lateral) y habla con estos
 * endpoints en JSON. Cada endpoint valida permisos en el servidor: un item
 * que no le toca ver a alguien responde 404, como si no existiera.
 */
class AgendaController extends Controller
{
    public function __construct(
        private readonly Acceso $acceso,
        private readonly Calendario $calendario,
        private readonly Actividades $actividades,
        private readonly SeguimientosCliente $seguimientos,
        private readonly Recordatorios $recordatorios,
    ) {}

    // ===================== Pantalla =====================

    public function index(Request $request): View
    {
        $u = $request->user();
        $this->recordatorios->despacharSiToca();

        $supervisados = $this->acceso->supervisados($u);
        $asignables = User::query()->where('status', User::STATUS_APPROVED)->orderBy('name')->get(['id', 'name'])
            ->filter(fn (User $p) => $this->acceso->puedeAsignarA($u, $p->id))->values();

        return view('agenda.index', [
            'config' => [
                'yo' => ['id' => $u->id, 'nombre' => $u->name],
                'hoy' => $this->calendario->hoy()->toDateString(),
                'zona' => config('agenda.timezone'),
                'horaAviso' => config('agenda.hora_aviso_sin_hora'),
                'tipos' => collect(AgendaActividad::TIPOS)->map(fn ($t, $k) => ['valor' => $k, 'label' => $t[0], 'clase' => $t[1], 'ocupa' => $t[2]])->values(),
                'tiposContacto' => ClienteSeguimiento::TIPOS,
                'estadosTarea' => AgendaActividad::ESTADOS_TAREA,
                'estadosEvento' => AgendaActividad::ESTADOS_EVENTO,
                'prioridades' => AgendaActividad::PRIORIDADES,
                'visibilidades' => collect(AgendaActividad::VISIBILIDADES)
                    ->filter(fn ($v, $k) => ! in_array($k, ['grupo', 'empresa'], true) || $u->can('agenda.empresa'))->all(),
                'recordatorios' => config('agenda.recordatorios'),
                'usuarios' => User::query()->where('status', User::STATUS_APPROVED)->orderBy('name')->get(['id', 'name']),
                'asignables' => $asignables,
                'supervisados' => User::whereIn('id', $supervisados)->orderBy('name')->get(['id', 'name']),
                'departamentos' => Role::query()->where('is_active', true)->orderBy('label')->get(['id', 'name', 'label'])
                    ->map(fn ($r) => ['id' => $r->id, 'nombre' => $r->label ?: ucfirst($r->name)]),
                'equipos' => AgendaEquipo::orderBy('nombre')->get(['id', 'nombre']),
                'puede' => [
                    'empresa' => $u->can('agenda.empresa'),
                    'reasignar' => $u->can('agenda.reasignar'),
                    'verOtros' => count($supervisados) > 1,
                    'carga' => $u->can('agenda.carga') && count($supervisados) > 1,
                    'equipos' => $u->can('agenda.equipos'),
                ],
                'preferencias' => Avisos::preferencias($u),
                'hayCorreo' => Avisos::hayCorreo(),
                'rutas' => [
                    'datos' => route('agenda.datos'),
                    'paneles' => route('agenda.paneles'),
                    'item' => url('/agenda/item'),
                    'crear' => route('agenda.store'),
                    'clientes' => route('agenda.clientes'),
                    'carga' => route('agenda.carga'),
                    'ics' => route('agenda.ics'),
                    'equipos' => route('agenda.equipos.index'),
                    'preferencias' => route('agenda.preferencias'),
                    'checklist' => url('/agenda/checklist'),
                ],
            ],
            'abrir' => $request->query('item'),
            'ambito' => in_array($request->query('ambito'), ['mia', 'empresa', 'todas'], true) ? $request->query('ambito') : null,
        ]);
    }

    // ===================== Lectura =====================

    public function datos(Request $request): JsonResponse
    {
        [$desde, $hasta] = $this->rangoValido($request);

        return response()->json(['items' => $this->calendario->rango($request->user(), $desde, $hasta, $this->filtros($request))]);
    }

    public function paneles(Request $request): JsonResponse
    {
        return response()->json($this->calendario->paneles($request->user(), $this->filtros($request)));
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);

        return response()->json(['item' => $this->detalle($item, $u)]);
    }

    public function clientes(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['clientes' => []]);
        }

        $lista = Customer::visiblesPara($request->user())->where('activo', true)
            ->where(fn ($w) => $w->whereRaw("CONCAT_WS(' ', nombre, apellido) LIKE ?", ['%'.$q.'%'])->orWhere('telefono', 'like', '%'.$q.'%')->orWhere('gmail', 'like', '%'.$q.'%'))
            ->orderBy('nombre')->limit(15)->get(['id', 'nombre', 'apellido', 'etapa', 'telefono'])
            ->map(fn ($c) => ['id' => $c->id, 'nombre' => trim($c->nombre.' '.$c->apellido), 'etapa' => $c->etapa, 'telefono' => $c->telefono]);

        return response()->json(['clientes' => $lista]);
    }

    public function carga(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('agenda.carga'), 403);

        return response()->json(['carga' => $this->calendario->carga($request->user())]);
    }

    public function ics(Request $request): Response
    {
        $hoy = $this->calendario->hoy();
        $desde = $request->date('desde')?->toDateString() ?? $hoy->subMonth()->toDateString();
        $hasta = $request->date('hasta')?->toDateString() ?? $hoy->addMonths(6)->toDateString();
        abort_if(CarbonImmutable::parse($desde)->diffInDays(CarbonImmutable::parse($hasta)) > 400, 422, 'Rango demasiado grande.');

        return response($this->calendario->ics($request->user(), $desde, $hasta), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="agenda-'.$hoy->format('Y-m-d').'.ics"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    // ===================== Crear =====================

    public function store(Request $request): JsonResponse
    {
        $u = $request->user();

        if ($request->input('tipo') === 'seguimiento') {
            return $this->crearSeguimiento($request);
        }

        $data = $request->validate($this->actividades->reglas(), Actividades::MENSAJES);
        $normal = $this->actividades->normalizar($data, $u);

        if ($alto = $this->revisarConflictos($normal, $u, [], (bool) ($data['confirmar_conflictos'] ?? false))) {
            return $alto;
        }

        $creadas = $this->actividades->crear($data, $u);

        return response()->json([
            'ok' => true,
            'mensaje' => $creadas->count() > 1 ? 'Actividad creada con '.$creadas->count().' repeticiones.' : 'Actividad creada.',
            'key' => $creadas->first()->llaveAgenda(),
        ], 201);
    }

    /** Un seguimiento comercial creado desde la agenda vive en Clientes (no se duplica). */
    private function crearSeguimiento(Request $request): JsonResponse
    {
        $u = $request->user();
        $request->validate(['customer_id' => ['required', 'integer', 'exists:clientes,id'], 'subtipo' => ['required', Rule::in(array_keys(ClienteSeguimiento::TIPOS))]],
            ['customer_id.required' => 'Elige el cliente o prospecto.']);

        $cliente = Customer::findOrFail($request->integer('customer_id'));
        if (! $cliente->visiblePara($u)) {
            throw ValidationException::withMessages(['customer_id' => 'Ese cliente no está entre los que puedes ver.']);
        }

        $datos = [
            'tipo' => $request->input('subtipo'),
            'fecha' => $request->input('fecha_inicio'),
            'hora' => $request->input('hora_inicio') ?: null,
            'prioridad' => $request->input('prioridad', 'normal'),
            'nota' => $request->input('descripcion') ?: $request->input('titulo'),
            'user_id' => $request->input('responsable_id'),
            'recordatorios' => $request->input('recordatorios'),
        ];
        if (! $request->has('recordatorios')) {
            unset($datos['recordatorios']);
        }

        $valid = validator($datos, $this->seguimientos->reglasProgramar(), $this->seguimientos->mensajesProgramar())->validate();

        if (! empty($valid['user_id']) && (int) $valid['user_id'] !== $u->id && ! $this->acceso->puedeAsignarA($u, (int) $valid['user_id']) && ! $u->can('clientes.ver_todos')) {
            throw ValidationException::withMessages(['responsable_id' => 'No puedes asignar seguimientos a esa persona.']);
        }
        if (! empty($valid['user_id']) && (int) $valid['user_id'] !== $u->id && ! $u->can('clientes.ver_todos') && ! $u->can('agenda.reasignar')) {
            $valid['user_id'] = $u->id;
        }

        $s = $this->seguimientos->programar($cliente, $valid, $u, 'agenda');

        return response()->json(['ok' => true, 'mensaje' => 'Seguimiento programado. También aparece en la ficha del cliente.', 'key' => $s->llaveAgenda()], 201);
    }

    // ===================== Editar =====================

    public function update(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);

        if ($item instanceof ClienteSeguimiento) {
            abort_unless($this->acceso->puedeEditarSeguimiento($item, $u), 403, 'No puedes modificar este seguimiento.');
            $data = $request->validate([
                'subtipo' => ['required', Rule::in(array_keys(ClienteSeguimiento::TIPOS))],
                'fecha_inicio' => ['required', 'date', 'after_or_equal:'.$this->calendario->hoy()->toDateString()],
                'hora_inicio' => ['nullable', 'date_format:H:i'],
                'prioridad' => ['nullable', Rule::in(array_keys(ClienteSeguimiento::PRIORIDADES))],
                'descripcion' => ['nullable', 'string', 'max:500'],
                'estado' => ['nullable', Rule::in(ClienteSeguimiento::ABIERTOS)],
                'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
                'recordatorios' => ['nullable', 'array'],
                'recordatorios.*' => ['integer', Rule::in(array_keys(config('agenda.recordatorios')))],
                'motivo' => ['nullable', 'string', 'max:255'],
            ], ['fecha_inicio.after_or_equal' => 'La fecha nueva no puede ser anterior a hoy.']);

            DB::transaction(function () use ($item, $data, $u, $request) {
                $cambios = ['tipo' => $data['subtipo'], 'prioridad' => $data['prioridad'] ?? $item->prioridad, 'nota' => $data['descripcion'] ?? null, 'estado' => $data['estado'] ?? null];
                if ($request->has('recordatorios')) {
                    $cambios['recordatorios'] = $data['recordatorios'] ?? [];
                }
                $this->seguimientos->actualizar($item, $cambios, $u);

                if ($item->fecha->toDateString() !== $data['fecha_inicio'] || $item->horaCorta() !== ($data['hora_inicio'] ?? null)) {
                    $this->seguimientos->reprogramar($item, $data['fecha_inicio'], $u, $data['hora_inicio'] ?? null, $data['motivo'] ?? null);
                }

                if (! empty($data['responsable_id']) && (int) $data['responsable_id'] !== (int) $item->user_id) {
                    abort_unless($this->acceso->puedeReasignarSeguimiento($item, $u), 403, 'No tienes permiso para reasignar este seguimiento.');
                    $this->seguimientos->reasignar($item, (int) $data['responsable_id'], $u, $data['motivo'] ?? null);
                }
            });

            return response()->json(['ok' => true, 'mensaje' => 'Seguimiento actualizado. El cambio también se ve en Clientes.', 'key' => $item->llaveAgenda()]);
        }

        abort_unless($this->acceso->puedeEditarActividad($item, $u), 403, 'No puedes modificar esta actividad.');
        $data = $request->validate($this->actividades->reglas() + [
            'alcance' => ['nullable', Rule::in(['una', 'futuras'])],
            'motivo' => ['nullable', 'string', 'max:255'],
        ], Actividades::MENSAJES);

        $normal = $this->actividades->normalizar($data, $u, $item);
        if ($alto = $this->revisarConflictos($normal, $u, [$item->id], (bool) ($data['confirmar_conflictos'] ?? false))) {
            return $alto;
        }

        $this->actividades->actualizar($item, $data, $u, $data['alcance'] ?? 'una');

        return response()->json(['ok' => true, 'mensaje' => 'Cambios guardados.', 'key' => $item->llaveAgenda()]);
    }

    /** Arrastrar y soltar, o "Mover" desde el formulario. */
    public function mover(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);
        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'conservar_hora' => ['nullable', 'boolean'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'alcance' => ['nullable', Rule::in(['una', 'futuras'])],
            'confirmar_conflictos' => ['nullable', 'boolean'],
        ]);
        $hora = ($data['conservar_hora'] ?? false) ? false : ($data['hora'] ?? null);

        if ($item instanceof ClienteSeguimiento) {
            abort_unless($this->acceso->puedeEditarSeguimiento($item, $u) && $item->abierto(), 403, 'No puedes mover este seguimiento.');
            if ($data['fecha'] < $this->calendario->hoy()->toDateString()) {
                throw ValidationException::withMessages(['fecha' => 'Un seguimiento no se puede mover a un día que ya pasó.']);
            }
            $this->seguimientos->reprogramar($item, $data['fecha'], $u, $hora, $data['motivo'] ?? null);

            return response()->json(['ok' => true, 'mensaje' => 'Seguimiento movido al '.CarbonImmutable::parse($data['fecha'])->format('d/m/Y').'. También cambió en Clientes.']);
        }

        abort_unless($this->acceso->puedeEditarActividad($item, $u) && ! $item->cerrada(), 403, 'No puedes mover esta actividad.');

        $prueba = [
            'fecha_inicio' => $data['fecha'],
            'fecha_fin' => CarbonImmutable::parse($data['fecha'])->addDays($item->fecha_inicio->diffInDays($item->fecha_fin))->toDateString(),
            'hora_inicio' => $hora === false ? ($item->hora_inicio ? substr($item->hora_inicio, 0, 5) : null) : $hora,
            'hora_fin' => null,
            'ocupa_horario' => $item->ocupa_horario,
            'responsable_id' => $item->responsable_id,
            'participantes' => $item->participantes()->pluck('users.id')->all(),
        ];
        if ($alto = $this->revisarConflictos($prueba, $u, [$item->id], (bool) ($data['confirmar_conflictos'] ?? false))) {
            return $alto;
        }

        $this->actividades->mover($item, $data['fecha'], $hora, $u, $data['motivo'] ?? null, $data['alcance'] ?? 'una');

        return response()->json(['ok' => true, 'mensaje' => 'Actividad movida al '.CarbonImmutable::parse($data['fecha'])->format('d/m/Y').'.']);
    }

    public function estado(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);

        if ($item instanceof ClienteSeguimiento) {
            abort_unless($this->acceso->puedeEditarSeguimiento($item, $u), 403);
            $data = $request->validate(['estado' => ['required', Rule::in(['pendiente', 'en_proceso', 'completado'])]] + $this->seguimientos->reglasHecho(),
                ['siguiente_fecha.after_or_equal' => 'El siguiente contacto no puede ser antes de hoy.']);

            if ($data['estado'] === 'completado') {
                $siguiente = $this->seguimientos->marcarHecho($item, $data['resultado'] ?? null, $u, $data);

                return response()->json([
                    'ok' => true,
                    'mensaje' => $siguiente ? 'Seguimiento completado y siguiente contacto programado.' : 'Seguimiento completado.',
                    'siguiente' => $siguiente?->llaveAgenda(),
                ]);
            }

            $this->seguimientos->actualizar($item, ['estado' => $data['estado']], $u);

            return response()->json(['ok' => true, 'mensaje' => 'Estado actualizado.']);
        }

        $data = $request->validate(['estado' => ['required', 'string'], 'resultado' => ['nullable', 'string', 'max:1000']]);
        $this->actividades->cambiarEstado($item, $data['estado'], $u, $data['resultado'] ?? null);

        return response()->json(['ok' => true, 'mensaje' => 'Estado actualizado.']);
    }

    public function cancelar(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);
        $data = $request->validate(['motivo' => ['nullable', 'string', 'max:255'], 'alcance' => ['nullable', Rule::in(['una', 'futuras'])]]);

        if ($item instanceof ClienteSeguimiento) {
            abort_unless($this->acceso->puedeEditarSeguimiento($item, $u), 403);
            $this->seguimientos->cancelar($item, $u, $data['motivo'] ?? null);

            return response()->json(['ok' => true, 'mensaje' => 'Seguimiento cancelado. Queda en el historial del cliente.']);
        }

        $n = $this->actividades->cancelar($item, $u, $data['motivo'] ?? null, $data['alcance'] ?? 'una');

        return response()->json(['ok' => true, 'mensaje' => $n > 1 ? "Se cancelaron {$n} actividades." : 'Actividad cancelada.']);
    }

    public function reasignar(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id'], 'motivo' => ['nullable', 'string', 'max:255']]);
        $nuevo = (int) $data['user_id'];

        if ($item instanceof ClienteSeguimiento) {
            abort_unless($this->acceso->puedeReasignarSeguimiento($item, $u), 403, 'No tienes permiso para reasignar este seguimiento.');
            $this->seguimientos->reasignar($item, $nuevo, $u, $data['motivo'] ?? null);
        } else {
            abort_unless($this->acceso->puedeReasignarActividad($item, $u) && $this->acceso->puedeAsignarA($u, $nuevo), 403, 'No tienes permiso para reasignar esta actividad.');
            $this->actividades->actualizar($item, $this->datosDe($item) + ['responsable_id' => $nuevo, 'motivo' => $data['motivo'] ?? null], $u);
        }

        return response()->json(['ok' => true, 'mensaje' => 'Reasignado a '.User::find($nuevo)?->name.'.']);
    }

    public function responder(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);
        abort_unless($item instanceof AgendaActividad, 404);
        $data = $request->validate(['respuesta' => ['required', Rule::in(['aceptada', 'rechazada', 'pendiente'])], 'comentario' => ['nullable', 'string', 'max:255']]);

        $this->actividades->responder($item, $u, $data['respuesta'], $data['comentario'] ?? null);

        return response()->json(['ok' => true, 'mensaje' => match ($data['respuesta']) {
            'aceptada' => 'Confirmaste tu asistencia.',
            'rechazada' => 'Avisaste que no asistirás.',
            default => 'Respuesta pendiente.',
        }]);
    }

    public function duplicar(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);
        abort_unless($item instanceof AgendaActividad, 422, 'Solo se duplican actividades manuales.');

        $copia = $this->actividades->duplicar($item, $u);

        return response()->json(['ok' => true, 'mensaje' => 'Actividad duplicada.', 'key' => $copia->llaveAgenda()], 201);
    }

    public function comentar(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);
        $data = $request->validate(['texto' => ['required', 'string', 'max:2000']]);
        $item->comentariosAgenda()->create(['user_id' => $u->id, 'texto' => $data['texto']]);

        return response()->json(['ok' => true, 'mensaje' => 'Comentario agregado.']);
    }

    public function agregarPaso(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);
        abort_unless($this->puedeEditar($item, $u), 403);
        $data = $request->validate(['texto' => ['required', 'string', 'max:255']]);
        $item->checklistAgenda()->create(['texto' => $data['texto'], 'orden' => (int) $item->checklistAgenda()->max('orden') + 1]);

        return response()->json(['ok' => true]);
    }

    public function marcarPaso(Request $request, AgendaChecklist $paso): JsonResponse
    {
        $u = $request->user();
        $item = $paso->item_type === 'seguimiento' ? ClienteSeguimiento::find($paso->item_id) : AgendaActividad::find($paso->item_id);
        abort_unless($item && $this->nivel($item, $u) === 'completo' && $this->puedeEditar($item, $u, true), 404);

        $paso->update(['hecho' => $request->boolean('hecho'), 'hecho_por' => $request->boolean('hecho') ? $u->id : null]);

        return response()->json(['ok' => true]);
    }

    public function posponer(Request $request, string $key): JsonResponse
    {
        $u = $request->user();
        $item = $this->resolver($key, $u);
        $data = $request->validate(['minutos' => ['required', 'integer', Rule::in([10, 30, 60, 180, 1440])]]);
        $this->recordatorios->posponer($item, $u, (int) $data['minutos']);

        return response()->json(['ok' => true, 'mensaje' => 'Te lo recordamos de nuevo más tarde. La fecha no cambió.']);
    }

    public function preferencias(Request $request): JsonResponse
    {
        $data = $request->validate([
            'correo' => ['nullable', 'boolean'],
            'resumen' => ['nullable', 'boolean'],
            'recordatorios' => ['nullable', 'array'],
            'recordatorios.*' => ['integer', Rule::in(array_keys(config('agenda.recordatorios')))],
        ]);
        $u = $request->user();
        $u->forceFill(['agenda_preferencias' => [
            'correo' => (bool) ($data['correo'] ?? false),
            'resumen' => (bool) ($data['resumen'] ?? false),
            'recordatorios' => array_values(array_map('intval', $data['recordatorios'] ?? [])),
        ]])->save();

        return response()->json(['ok' => true, 'mensaje' => 'Preferencias guardadas.']);
    }

    // ===================== Equipos de trabajo =====================

    public function equipos(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('agenda.equipos'), 403);

        return response()->json(['equipos' => AgendaEquipo::with('miembros:id,name')->orderBy('nombre')->get()->map(fn ($e) => [
            'id' => $e->id, 'nombre' => $e->nombre, 'descripcion' => $e->descripcion,
            'miembros' => $e->miembros->map(fn ($m) => ['id' => $m->id, 'nombre' => $m->name, 'lider' => (bool) $m->pivot->es_lider]),
        ])]);
    }

    public function guardarEquipo(Request $request, ?AgendaEquipo $equipo = null): JsonResponse
    {
        abort_unless($request->user()->can('agenda.equipos'), 403);
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('agenda_equipos', 'nombre')->ignore($equipo?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'miembros' => ['nullable', 'array'],
            'miembros.*' => ['integer', 'exists:users,id'],
            'lideres' => ['nullable', 'array'],
            'lideres.*' => ['integer', 'exists:users,id'],
        ], ['nombre.unique' => 'Ya existe un equipo con ese nombre.']);

        DB::transaction(function () use (&$equipo, $data, $request) {
            $equipo ??= new AgendaEquipo(['created_by' => $request->user()->id]);
            $equipo->fill(['nombre' => $data['nombre'], 'descripcion' => $data['descripcion'] ?? null])->save();
            $lideres = array_map('intval', $data['lideres'] ?? []);
            $sync = [];
            foreach (array_unique(array_merge(array_map('intval', $data['miembros'] ?? []), $lideres)) as $id) {
                $sync[$id] = ['es_lider' => in_array($id, $lideres, true)];
            }
            $equipo->miembros()->sync($sync);
        });

        return response()->json(['ok' => true, 'mensaje' => 'Equipo guardado.', 'id' => $equipo->id]);
    }

    public function eliminarEquipo(Request $request, AgendaEquipo $equipo): JsonResponse
    {
        abort_unless($request->user()->can('agenda.equipos'), 403);
        abort_if(DB::table('agenda_actividad_equipo')->where('equipo_id', $equipo->id)->exists(), 422, 'Este equipo tiene eventos dirigidos; no se puede eliminar.');
        $equipo->delete();

        return response()->json(['ok' => true, 'mensaje' => 'Equipo eliminado.']);
    }

    // ===================== Apoyo =====================

    /** 's-12' → seguimiento, 'a-34' → actividad. Si no le toca verlo: 404. */
    private function resolver(string $key, User $u): Model
    {
        abort_unless(preg_match('/^([sa])-(\d+)$/', $key, $m), 404);

        $item = $m[1] === 's'
            ? ClienteSeguimiento::with(['customer', 'responsable:id,name'])->find((int) $m[2])
            : AgendaActividad::with(['responsable:id,name', 'participantes:id,name', 'roles:id,name,label', 'equipos:id,nombre', 'customer'])->find((int) $m[2]);

        abort_unless($item && $this->nivel($item, $u) === 'completo', 404);

        return $item;
    }

    private function nivel(Model $item, User $u): ?string
    {
        return $item instanceof ClienteSeguimiento ? $this->acceso->nivelSeguimiento($item, $u) : $this->acceso->nivelActividad($item, $u);
    }

    private function puedeEditar(Model $item, User $u, bool $oParticipa = false): bool
    {
        if ($item instanceof ClienteSeguimiento) {
            return $this->acceso->puedeEditarSeguimiento($item, $u);
        }

        return $this->acceso->puedeEditarActividad($item, $u) || (int) $item->responsable_id === $u->id
            || ($oParticipa && $this->acceso->esParticipante($item, $u));
    }

    private function filtros(Request $request): array
    {
        return $request->validate([
            'ambito' => ['nullable', Rule::in(['mia', 'empresa', 'todas'])],
            'usuario' => ['nullable', 'integer'],
            'departamento' => ['nullable', 'integer'],
            'tipo' => ['nullable', 'string', 'max:40'],
            'estado' => ['nullable', 'string', 'max:20'],
            'prioridad' => ['nullable', Rule::in(['baja', 'normal', 'alta'])],
            'cliente' => ['nullable', 'integer'],
            'origen' => ['nullable', Rule::in(['cliente', 'manual'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
    }

    /** @return array{0:string,1:string} */
    private function rangoValido(Request $request): array
    {
        $request->validate(['desde' => ['required', 'date'], 'hasta' => ['required', 'date', 'after_or_equal:desde']]);
        $desde = CarbonImmutable::parse($request->input('desde'))->toDateString();
        $hasta = CarbonImmutable::parse($request->input('hasta'))->toDateString();
        abort_if(CarbonImmutable::parse($desde)->diffInDays(CarbonImmutable::parse($hasta)) > 62, 422, 'El calendario se carga por mes, semana o día.');

        return [$desde, $hasta];
    }

    private function revisarConflictos(array $d, User $u, array $ignorar, bool $confirmado): ?JsonResponse
    {
        if ($confirmado || empty($d['ocupa_horario'])) {
            return null;
        }

        $choques = $this->actividades->conflictos($d + ['fecha_fin' => $d['fecha_inicio'], 'participantes' => []], $u, $ignorar);

        return $choques ? response()->json([
            'conflictos' => $choques,
            'message' => 'Hay choques de horario. Revisa y confirma si quieres guardarla de todos modos.',
        ], 409) : null;
    }

    private function datosDe(AgendaActividad $a): array
    {
        return [
            'titulo' => $a->titulo, 'descripcion' => $a->descripcion, 'tipo' => $a->tipo,
            'fecha_inicio' => $a->fecha_inicio->toDateString(), 'fecha_fin' => $a->fecha_fin->toDateString(),
            'hora_inicio' => $a->hora_inicio ? substr($a->hora_inicio, 0, 5) : null, 'hora_fin' => $a->hora_fin ? substr($a->hora_fin, 0, 5) : null,
            'todo_el_dia' => $a->todo_el_dia, 'responsable_id' => $a->responsable_id,
            'participantes' => $a->participantes()->pluck('users.id')->all(),
            'roles' => $a->roles()->pluck('roles.id')->all(), 'equipos' => $a->equipos()->pluck('agenda_equipos.id')->all(),
            'customer_id' => $a->customer_id, 'ubicacion' => $a->ubicacion, 'enlace' => $a->enlace,
            'prioridad' => $a->prioridad, 'visibilidad' => $a->visibilidad, 'ocupa_horario' => $a->ocupa_horario,
        ] + ($a->recordatorios !== null ? ['recordatorios' => $a->recordatorios] : []);
    }

    /** Todo lo que muestra el panel lateral. */
    private function detalle(Model $item, User $u): array
    {
        $base = $item instanceof ClienteSeguimiento ? $this->calendario->deSeguimiento($item, $u) : $this->calendario->deActividad($item, $u);

        $historial = $item->historialAgenda()->with('usuario:id,name')->limit(50)->get()->map(fn ($h) => [
            'accion' => $h->accionLabel(),
            'usuario' => $h->usuario?->name ?? 'Sistema',
            'cuando' => $h->created_at?->setTimezone(config('agenda.timezone'))->format('d/m/Y H:i'),
            'antes' => $h->antes, 'despues' => $h->despues, 'motivo' => $h->motivo,
        ]);

        $extra = [
            'historial' => $historial,
            'comentarios' => $item->comentariosAgenda()->with('usuario:id,name')->get()->map(fn ($c) => [
                'usuario' => $c->usuario?->name ?? '—', 'texto' => $c->texto,
                'cuando' => $c->created_at?->setTimezone(config('agenda.timezone'))->format('d/m/Y H:i'),
            ]),
            'checklist' => $item->checklistAgenda()->get(['id', 'texto', 'hecho']),
            'recordatorios_elegidos' => $item->recordatorios,
            'mis_recordatorios' => AgendaRecordatorio::where('item_type', $item->getMorphClass())->where('item_id', $item->getKey())
                ->where('user_id', $u->id)->whereIn('estado', ['pendiente', 'enviado'])->orderBy('enviar_en')->get()
                ->map(fn ($r) => ['tipo' => $r->tipo, 'cuando' => $r->enviar_en->setTimezone(config('agenda.timezone'))->format('d/m/Y H:i'), 'estado' => $r->estado]),
        ];

        if ($item instanceof ClienteSeguimiento) {
            $c = $item->customer;
            $extra += [
                'descripcion' => $item->nota,
                'resultado' => $item->resultado,
                'motivo_cancelacion' => $item->motivo_cancelacion,
                'creador' => $item->creador?->name,
                'cliente_detalle' => $c ? [
                    'telefono' => $c->telefono, 'correo' => $c->gmail, 'etapa' => $c->etapaLabel(),
                    'direccion' => $c->direccion, 'asesor' => $c->asesor?->name,
                ] : null,
                'anterior' => $item->anterior ? ['fecha' => $item->anterior->fecha?->format('d/m/Y'), 'resultado' => $item->anterior->resultado] : null,
            ];
        } else {
            $extra += [
                'descripcion' => $item->descripcion,
                'enlace' => $item->enlace,
                'resultado' => $item->resultado,
                'motivo_cancelacion' => $item->motivo_cancelacion,
                'creador' => $item->creador?->name,
                'recurrencia' => $item->recurrencia,
                'participantes' => $item->participantes->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->name, 'respuesta' => $p->pivot->respuesta, 'comentario' => $p->pivot->comentario]),
                'roles' => $item->roles->map(fn ($r) => ['id' => $r->id, 'nombre' => $r->label ?: ucfirst($r->name)]),
                'equipos' => $item->equipos->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre]),
                'customer_id' => $item->customer_id,
            ];
        }

        return $base + $extra;
    }
}
