<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use App\Models\AttendancePunch;
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
        $datos = $this->reporte->mesDeEmpleado($user, $mes);

        return view('asistencia.empleado', [
            'empleado' => $user->load(['horario', 'huellas']),
            'mes' => $mes,
            'dias' => $datos['dias'],
            'resumen' => $datos['resumen'],
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
        $data = $request->validate(ServicioDeAsistencia::reglasChecadaManual(), ServicioDeAsistencia::mensajesChecadaManual());

        $user = User::findOrFail($data['user_id']);

        // Una hora en el futuro regresa al formulario con el error en "hora"
        $this->asistencia->registrarManual($user, $data['fecha'], $data['hora'], $data['motivo'], $request->user()->id);

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
