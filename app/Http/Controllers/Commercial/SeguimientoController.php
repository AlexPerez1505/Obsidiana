<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Models\ClienteSeguimiento;
use App\Models\Customer;
use App\Services\Clientes\SeguimientosCliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Seguimientos de un cliente o prospecto: programarlos, marcarlos hechos,
 * moverlos de fecha y convertir al prospecto en cliente.
 *
 * Lo que pasa en cada caso vive en SeguimientosCliente, que también usa
 * la API; aquí solo queda validar y regresar a la ficha.
 */
class SeguimientoController extends Controller
{
    public function __construct(private readonly SeguimientosCliente $seguimientos)
    {
    }

    public function store(Request $request, Customer $cliente): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);

        $data = $request->validate($this->seguimientos->reglasProgramar(), $this->seguimientos->mensajesProgramar());

        $this->seguimientos->programar($cliente, $data, $request->user());

        return $this->volver($cliente, 'Seguimiento programado. Se avisará el día que toque.');
    }

    public function hecho(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);
        abort_if($seguimiento->customer_id !== $cliente->id, 404);

        $data = $request->validate($this->seguimientos->reglasHecho(), ['siguiente_fecha.after_or_equal' => 'El siguiente contacto no puede ser antes de hoy.']);

        $siguiente = $this->seguimientos->marcarHecho($seguimiento, $data['resultado'] ?? null, $request->user(), $data);

        return $this->volver($cliente, $siguiente ? 'Seguimiento hecho y siguiente contacto programado.' : 'Seguimiento marcado como hecho.');
    }

    public function reprogramar(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);
        abort_if($seguimiento->customer_id !== $cliente->id, 404);

        $data = $request->validate($this->seguimientos->reglasReprogramar(), $this->seguimientos->mensajesReprogramar());

        // Sin el campo de hora en el formulario, la hora se conserva.
        $hora = $request->has('hora') ? ($data['hora'] ?? null) : false;
        $this->seguimientos->reprogramar($seguimiento, $data['fecha'], $request->user(), $hora, $data['motivo'] ?? null);

        return $this->volver($cliente, 'Seguimiento movido al '.\Carbon\Carbon::parse($data['fecha'])->format('d/m/Y').'. También cambió en la agenda.');
    }

    /** Pasa el seguimiento a otra persona (le aparece en su agenda). */
    public function reasignar(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);
        abort_if($seguimiento->customer_id !== $cliente->id, 404);
        abort_unless(app(\App\Services\Agenda\Acceso::class)->puedeReasignarSeguimiento($seguimiento, $request->user()), 403);

        $data = $request->validate(['user_id' => ['required', 'exists:users,id'], 'motivo' => ['nullable', 'string', 'max:255']]);
        $this->seguimientos->reasignar($seguimiento, (int) $data['user_id'], $request->user(), $data['motivo'] ?? null);

        return $this->volver($cliente, 'Seguimiento reasignado.');
    }

    public function destroy(Request $request, Customer $cliente, ClienteSeguimiento $seguimiento): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);
        abort_if($seguimiento->customer_id !== $cliente->id, 404);

        // Se cancela (no se borra): el historial del cliente lo conserva.
        $this->seguimientos->cancelar($seguimiento, $request->user(), $request->input('motivo') ?: 'Cancelado desde la ficha del cliente');

        return $this->volver($cliente, 'Seguimiento cancelado. Queda en el historial.');
    }

    /**
     * Todo el seguimiento del cliente para el modal de la lista: lo
     * pendiente, lo hecho y lo cancelado, con cada cambio (reprogramaciones,
     * reasignaciones, resultados) tal como quedó en el historial.
     */
    public function historial(Request $request, Customer $cliente): \Illuminate\Http\JsonResponse
    {
        $u = $request->user();
        abort_unless($cliente->visiblePara($u), 403, 'Este cliente lo registró otro asesor.');

        $tz = config('agenda.timezone');
        $usuarios = \App\Models\User::pluck('name', 'id');
        $acceso = app(\App\Services\Agenda\Acceso::class);
        $nombres = ['fecha' => 'Fecha', 'hora' => 'Hora', 'user_id' => 'Responsable', 'tipo' => 'Tipo', 'prioridad' => 'Prioridad', 'estado' => 'Estado', 'nota' => 'Motivo'];
        $valor = function (string $campo, $v) use ($usuarios) {
            if ($v === null || $v === '') {
                return '—';
            }

            return match ($campo) {
                'user_id' => $usuarios[$v] ?? '—',
                'tipo' => ClienteSeguimiento::TIPOS[$v] ?? $v,
                'estado' => ClienteSeguimiento::ESTADOS[$v] ?? $v,
                'prioridad' => ClienteSeguimiento::PRIORIDADES[$v] ?? $v,
                'fecha' => \Carbon\Carbon::parse($v)->format('d/m/Y'),
                default => (string) $v,
            };
        };

        $seguimientos = $cliente->seguimientos()
            ->with(['responsable:id,name', 'hechoPor:id,name', 'creador:id,name', 'historialAgenda.usuario:id,name'])
            ->reorder()->orderByDesc('fecha')->orderByDesc('id')->get();

        $lista = $seguimientos->map(fn (ClienteSeguimiento $s) => [
            'id' => $s->id,
            'tipo' => $s->tipoLabel(),
            'fecha' => $s->fecha?->format('d/m/Y'),
            'fecha_iso' => $s->fecha?->toDateString(),
            'hora' => $s->horaCorta(),
            'estado' => $s->estado,
            'estado_label' => $s->estadoLabel(),
            'abierto' => $s->abierto(),
            'vencido' => $s->vencido(),
            'cuando' => $s->cuando(),
            'prioridad' => $s->prioridad,
            'nota' => $s->nota,
            'resultado' => $s->resultado,
            'motivo_cancelacion' => $s->motivo_cancelacion,
            'responsable' => $s->responsable?->name,
            'hecho_por' => $s->hechoPor?->name,
            'hecho_en' => $s->hecho_en?->setTimezone($tz)->format('d/m/Y H:i'),
            'cancelado_en' => $s->cancelado_en?->setTimezone($tz)->format('d/m/Y H:i'),
            'creado_por' => $s->creador?->name,
            'creado_en' => $s->created_at?->setTimezone($tz)->format('d/m/Y H:i'),
            'origen' => $s->origen,
            'anterior_id' => $s->anterior_id,
            'agenda' => $u->can('agenda.ver') && $acceso->nivelSeguimiento($s, $u) === 'completo'
                ? route('agenda.index', ['item' => $s->llaveAgenda()]) : null,
            'cambios' => $s->historialAgenda->map(function ($h) use ($nombres, $valor, $tz) {
                // Solo lo que de verdad cambió (al cancelar o completar se guarda la foto completa).
                $detalle = collect($h->despues ?? [])->filter(fn ($v, $k) => isset($nombres[$k]) && $h->antes !== null && json_encode($h->antes[$k] ?? null) !== json_encode($v))
                    ->map(fn ($v, $k) => $nombres[$k].': '.$valor($k, $h->antes[$k] ?? null).' → '.$valor($k, $v))->values();

                return [
                    'accion' => $h->accionLabel(),
                    'usuario' => $h->usuario?->name ?? 'Sistema',
                    'cuando' => $h->created_at?->setTimezone($tz)->format('d/m/Y H:i'),
                    'detalle' => $detalle,
                    'motivo' => $h->motivo,
                ];
            })->values(),
        ]);

        return response()->json([
            'cliente' => [
                'nombre' => trim($cliente->nombre.' '.$cliente->apellido),
                'etapa' => $cliente->etapaLabel(),
                'asesor' => $cliente->asesor?->name,
                'telefono' => $cliente->telefono,
                'ficha' => route('commercial.clientes.show', $cliente).'#seguimientos',
            ],
            'resumen' => [
                'pendientes' => $seguimientos->filter->abierto()->count(),
                'vencidos' => $seguimientos->filter->vencido()->count(),
                'completados' => $seguimientos->where('estado', 'completado')->count(),
                'cancelados' => $seguimientos->where('estado', 'cancelado')->count(),
            ],
            'pendientes' => $lista->where('abierto', true)->sortBy(fn ($s) => $s['fecha_iso'].' '.($s['hora'] ?? '00:00'))->values(),
            'historial' => $lista->where('abierto', false)->values(),
            'puede_programar' => $u->can('clientes.editar'),
        ]);
    }

    /** El prospecto ya compró (o ya se decidió): pasa a cliente. */
    public function convertir(Request $request, Customer $cliente): RedirectResponse
    {
        abort_unless($cliente->visiblePara($request->user()), 403);

        $this->seguimientos->convertir($cliente);

        return $this->volver($cliente, trim($cliente->nombre.' '.$cliente->apellido).' ahora es cliente.');
    }

    private function volver(Customer $cliente, string $mensaje): RedirectResponse
    {
        return redirect()->to(route('commercial.clientes.show', $cliente).'#seguimientos')->with('status', $mensaje);
    }
}
