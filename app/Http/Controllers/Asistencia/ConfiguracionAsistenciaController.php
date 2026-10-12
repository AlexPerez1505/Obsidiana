<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use App\Models\AttendanceModality;
use App\Models\CompanyEvent;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\User;
use App\Services\Asistencia\ConfiguracionDeAsistencia;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Horarios, festivos e incidencias del día (campo, home office, comisión…).
 * Las reglas y los recálculos viven en ConfiguracionDeAsistencia, que
 * comparte con la API.
 */
class ConfiguracionAsistenciaController extends Controller
{
    public function __construct(private ServicioDeAsistencia $asistencia, private ConfiguracionDeAsistencia $config)
    {
    }

    // ---------------- Horarios y festivos ----------------

    public function horarios(): View
    {
        return view('asistencia.horarios', [
            'usuarios' => User::with(['horario', 'huellas'])->where('status', User::STATUS_APPROVED)->orderBy('name')->get(),
            'festivos' => Holiday::whereDate('date', '>=', now()->startOfYear())->orderBy('date')->get(),
            'base' => config('asistencia.horario_base'),
            'dias' => EmployeeSchedule::DIAS,
        ]);
    }

    public function guardarHorario(Request $request, User $user): RedirectResponse
    {
        $this->config->guardarHorario($user, $request->all());

        return back()->with('status', "Horario de {$user->name} guardado.");
    }

    /**
     * Aplica el mismo horario (con sus días y horas) a varias personas:
     * a todos los que toman asistencia o solo a los marcados.
     */
    public function guardarHorarioMasivo(Request $request): RedirectResponse
    {
        $n = $this->config->aplicarHorarioMasivo($request->all());

        return back()->with('status', "Horario aplicado a {$n} persona(s).");
    }

    // ---------------- Eventos de empresa ----------------

    public function eventos(): View
    {
        return view('asistencia.eventos', [
            'proximos' => CompanyEvent::with('creador')->whereDate('date_to', '>=', today())->orderBy('date_from')->get(),
            'pasados' => CompanyEvent::with('creador')->whereDate('date_to', '<', today())->orderByDesc('date_from')->limit(15)->get(),
            'empleados' => $this->asistencia->empleados(),
            'tipos' => CompanyEvent::TIPOS,
            'festivos' => Holiday::whereDate('date', '>=', today())->orderBy('date')->limit(10)->get(),
        ]);
    }

    public function guardarEvento(Request $request): RedirectResponse
    {
        $data = $request->validate(ConfiguracionDeAsistencia::reglasEvento(), ConfiguracionDeAsistencia::mensajesEvento());

        $evento = $this->config->crearEvento($data, $request->user()->id);

        return back()->with('status', "Evento «{$evento->name}» registrado.");
    }

    public function eliminarEvento(CompanyEvent $evento): RedirectResponse
    {
        $this->config->eliminarEvento($evento);

        return back()->with('status', 'Evento eliminado y días recalculados.');
    }

    /** Da de alta el horario base a todos los que aún no tienen uno. */
    public function aplicarBase(): RedirectResponse
    {
        $n = $this->config->aplicarBase();

        return back()->with('status', $n ? "Horario base aplicado a {$n} persona(s)." : 'Todos ya tienen horario.');
    }

    public function guardarFestivo(Request $request): RedirectResponse
    {
        $data = $request->validate(ConfiguracionDeAsistencia::reglasFestivo(), ConfiguracionDeAsistencia::mensajesFestivo());

        $this->config->crearFestivo($data);

        return back()->with('status', 'Festivo agregado.');
    }

    /** Festivos de ley del año: solo agrega los que faltan (ver ConfiguracionDeAsistencia). */
    public function cargarFestivosOficiales(Request $request): RedirectResponse
    {
        $anio = (int) $request->validate(ConfiguracionDeAsistencia::reglasFestivosOficiales())['anio'];

        $nuevos = $this->config->cargarFestivosOficiales($anio);

        return back()->with('status', $nuevos
            ? "Se agregaron {$nuevos} festivo(s) oficiales de {$anio}. Ya aparecen en la Agenda."
            : "Los festivos oficiales de {$anio} ya estaban cargados.");
    }

    public function eliminarFestivo(Holiday $festivo): RedirectResponse
    {
        $this->config->eliminarFestivo($festivo);

        return back()->with('status', 'Festivo eliminado.');
    }

    // ---------------- Incidencias del día ----------------

    public function modalidades(Request $request): View
    {
        $mes = $request->filled('mes') ? Carbon::parse($request->get('mes').'-01') : now();

        $lista = AttendanceModality::with(['user', 'creador'])
            ->where('date_to', '>=', $mes->copy()->startOfMonth())
            ->where('date_from', '<=', $mes->copy()->endOfMonth())
            ->when($request->filled('empleado'), fn ($q) => $q->where('user_id', $request->get('empleado')))
            ->orderByDesc('date_from')->get();

        return view('asistencia.modalidades', [
            'lista' => $lista,
            'mes' => $mes->copy()->startOfMonth(),
            'empleados' => $this->asistencia->empleados(),
            'tipos' => AttendanceModality::TIPOS,
            'filtroEmpleado' => $request->get('empleado'),
        ]);
    }

    public function guardarModalidad(Request $request): RedirectResponse
    {
        $data = $request->validate(ConfiguracionDeAsistencia::reglasIncidencia(), ConfiguracionDeAsistencia::mensajesIncidencia());

        $m = $this->config->crearIncidencia($data, $request->user()->id);

        return back()->with('status', 'Incidencia registrada para '.$m->user->name.'.');
    }

    public function eliminarModalidad(AttendanceModality $modalidad): RedirectResponse
    {
        $this->config->eliminarIncidencia($modalidad);

        return back()->with('status', 'Incidencia eliminada y días recalculados.');
    }
}
