<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use App\Models\AttendancePunch;
use App\Models\EmployeeAttendance;
use App\Models\User;
use App\Services\Asistencia\ReporteDeAsistencia;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Panel de RH: asistencia del día, indicadores, checadas y detalle por empleado. */
class AsistenciaController extends Controller
{
    public function __construct(private ServicioDeAsistencia $asistencia, private ReporteDeAsistencia $reporte)
    {
    }

    public function panel(Request $request): View
    {
        // Respaldo por si el cron no corrió: cierra los días pendientes de la semana
        $this->asistencia->cerrarPendientes();

        $dia = $this->fecha($request->get('fecha'));
        $hoy = ServicioDeAsistencia::diaLocal(now());
        $mes = $dia->copy()->startOfMonth();

        return view('asistencia.panel', [
            'dia' => $dia,
            'esHoy' => $dia->isSameDay($hoy),
            'datos' => $this->reporte->dia($dia),
            'ultimas' => $this->reporte->ultimasChecadas(),
            'kpis' => $this->reporte->mes($mes),
            'serie' => $this->reporte->serieMensual(6),
            'horas' => $this->reporte->horaPromedioPorDia(14),
            'puestos' => $this->reporte->porPuesto($mes),
            'sinHorarios' => User::where('status', User::STATUS_APPROVED)->whereDoesntHave('horario')->count(),
            'puedeGestionar' => $request->user()->can('asistencia.gestionar'),
        ]);
    }

    /** Detalle de un empleado en un mes: resumen por día y checadas crudas. */
    public function empleado(Request $request, User $user): View
    {
        $mes = $this->fecha($request->get('mes') ? $request->get('mes').'-01' : null)->startOfMonth();
        $filas = EmployeeAttendance::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$mes->toDateString(), $mes->copy()->endOfMonth()->toDateString()])
            ->get()->keyBy(fn ($f) => $f->attendance_date->toDateString());

        $checadas = AttendancePunch::with('device')->where('user_id', $user->id)
            ->whereBetween('work_date', [$mes->toDateString(), $mes->copy()->endOfMonth()->toDateString()])
            ->orderBy('punched_at')->get()->groupBy(fn ($p) => $p->work_date->toDateString());

        $dias = [];
        for ($d = $mes->copy(); $d->month === $mes->month; $d->addDay()) {
            $k = $d->toDateString();
            $dias[] = [
                'fecha' => $d->copy(),
                'esperado' => $this->asistencia->esperado($user, $d),
                'festivo' => $this->asistencia->esFestivo($d),
                'fila' => $filas->get($k),
                'checadas' => $checadas->get($k, collect()),
                'modalidad' => $this->asistencia->modalidadDe($user, $d),
            ];
        }

        return view('asistencia.empleado', [
            'empleado' => $user->load(['horario', 'huellas']),
            'mes' => $mes,
            'dias' => $dias,
            'resumen' => [
                'puntuales' => $filas->where('status', EmployeeAttendance::PUNTUAL)->count(),
                'retardos' => $filas->where('status', EmployeeAttendance::RETARDO)->count(),
                'faltas' => $filas->where('status', EmployeeAttendance::FALTA)->count(),
                'justificadas' => $filas->where('status', EmployeeAttendance::JUSTIFICADA)->count(),
                'horas' => round($filas->sum('worked_minutes') / 60, 1),
            ],
            'puedeGestionar' => $request->user()->can('asistencia.gestionar'),
        ]);
    }

    /** Checadas crudas con filtros. */
    public function checadas(Request $request): View
    {
        $dia = $this->fecha($request->get('fecha'));

        $checadas = AttendancePunch::with(['user', 'device', 'anuladaPor'])
            ->whereDate('work_date', $dia)
            ->when($request->filled('empleado'), fn ($q) => $q->where('user_id', $request->get('empleado')))
            ->orderByDesc('punched_at')->get();

        return view('asistencia.checadas', [
            'dia' => $dia,
            'checadas' => $checadas,
            'empleados' => $this->asistencia->empleados(),
            'filtroEmpleado' => $request->get('empleado'),
            'puedeGestionar' => $request->user()->can('asistencia.gestionar'),
        ]);
    }

    /** Checada manual (olvidó checar, falló el lector): queda marcada como manual con su motivo. */
    public function checadaManual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'motivo' => ['required', 'string', 'max:255'],
        ], ['motivo.required' => 'Escribe el motivo de la checada manual: queda como evidencia.']);

        $cuando = Carbon::parse($data['fecha'].' '.$data['hora'], ServicioDeAsistencia::tz())->utc();

        if ($cuando->isFuture()) {
            return back()->withInput()->withErrors(['hora' => 'No se puede registrar una checada en el futuro.']);
        }

        $user = User::findOrFail($data['user_id']);
        $this->asistencia->registrarChecada($user, null, [
            'punched_at' => $cuando->toDateTimeString(),
            'method' => 'manual',
            'note' => $data['motivo'],
            'created_by' => $request->user()->id,
        ]);

        // Una checada hecha a mano fuera de orden cambia entrada/salida del día
        $this->asistencia->renumerar($user->id, Carbon::parse($data['fecha']));
        $this->asistencia->recalcularDia($user, Carbon::parse($data['fecha']));

        return back()->with('status', 'Checada manual registrada para '.$user->name.'.');
    }

    public function anular(Request $request, AttendancePunch $punch): RedirectResponse
    {
        $data = $request->validate(['motivo' => ['required', 'string', 'max:255']], ['motivo.required' => 'Escribe por qué se anula la checada.']);

        $this->asistencia->anular($punch, $request->user()->id, $data['motivo']);

        return back()->with('status', 'Checada anulada. Queda en el historial con su motivo.');
    }

    /** Cierra un día (genera faltas automáticas) o lo recalcula completo. */
    public function cerrar(Request $request): RedirectResponse
    {
        $data = $request->validate(['fecha' => ['required', 'date', 'before:today']]);
        $r = $this->asistencia->cerrarDia(Carbon::parse($data['fecha']));

        return back()->with('status', "Día cerrado: {$r['faltas']} falta(s), {$r['justificadas']} justificada(s), {$r['revisados']} empleado(s) revisados.");
    }

    private function fecha(?string $f): Carbon
    {
        try {
            return $f ? Carbon::parse($f)->startOfDay() : ServicioDeAsistencia::diaLocal(now());
        } catch (\Throwable) {
            return ServicioDeAsistencia::diaLocal(now());
        }
    }
}
