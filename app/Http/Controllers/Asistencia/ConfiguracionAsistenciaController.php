<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use App\Models\AttendanceModality;
use App\Models\CompanyEvent;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\User;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Horarios, festivos e incidencias del día (campo, home office, comisión…). */
class ConfiguracionAsistenciaController extends Controller
{
    public function __construct(private ServicioDeAsistencia $asistencia)
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
        $request->validate([
            'checador_id' => ['nullable', 'string', 'max:100', 'unique:users,checador_id,'.$user->id],
        ]);

        EmployeeSchedule::updateOrCreate(['user_id' => $user->id], $this->camposHorario($request));

        if ($request->has('checador_id')) {
            $user->forceFill(['checador_id' => $request->input('checador_id') ?: null])->save();
        }

        return back()->with('status', "Horario de {$user->name} guardado.");
    }

    /**
     * Aplica el mismo horario (con sus días y horas) a varias personas:
     * a todos los que toman asistencia o solo a los marcados.
     */
    public function guardarHorarioMasivo(Request $request): RedirectResponse
    {
        $request->validate([
            'alcance' => ['required', 'in:todos,seleccion'],
            'user_ids' => ['required_if:alcance,seleccion', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ], ['user_ids.required_if' => 'Marca al menos una persona.']);

        $campos = $this->camposHorario($request);

        $usuarios = User::where('status', User::STATUS_APPROVED)
            ->when($request->input('alcance') === 'seleccion', fn ($q) => $q->whereIn('id', $request->input('user_ids', [])))
            ->get();

        foreach ($usuarios as $u) {
            EmployeeSchedule::updateOrCreate(['user_id' => $u->id], $campos);
        }

        return back()->with('status', "Horario aplicado a {$usuarios->count()} persona(s).");
    }

    /**
     * Lee del formulario el horario por día: days[1..7][on|in|out].
     * El horario "general" (time_in/time_out) queda como el del primer día
     * marcado; los días con horas distintas se guardan en day_hours.
     */
    private function camposHorario(Request $request): array
    {
        $request->validate([
            'days' => ['required', 'array'],
            'tolerance_minutes' => ['required', 'integer', 'between:0,60'],
            'active' => ['nullable', 'boolean'],
        ]);

        $weekdays = [];
        $horas = [];
        $errores = [];

        foreach (EmployeeSchedule::DIAS as $n => $nombre) {
            $d = $request->input("days.$n", []);

            if (empty($d['on'])) {
                continue;
            }

            $in = $d['in'] ?? null;
            $out = $d['out'] ?? null;

            if (! preg_match('/^\d{2}:\d{2}$/', (string) $in) || ! preg_match('/^\d{2}:\d{2}$/', (string) $out)) {
                $errores["days.$n"] = "Falta la hora de entrada o salida del {$nombre}.";
            } elseif ($out <= $in) {
                $errores["days.$n"] = "En {$nombre} la salida debe ser después de la entrada.";
            }

            $weekdays[] = $n;
            $horas[$n] = ['in' => $in, 'out' => $out];
        }

        if (! $weekdays) {
            $errores['days'] = 'Marca al menos un día laboral.';
        }

        if ($errores) {
            throw \Illuminate\Validation\ValidationException::withMessages($errores);
        }

        $primero = $horas[$weekdays[0]];

        // Solo se guardan por día los que difieren del horario general
        $propios = array_filter($horas, fn ($h) => $h['in'] !== $primero['in'] || $h['out'] !== $primero['out']);

        return [
            'weekdays' => $weekdays,
            'day_hours' => $propios ?: null,
            'time_in' => $primero['in'],
            'time_out' => $primero['out'],
            'tolerance_minutes' => (int) $request->input('tolerance_minutes'),
            'active' => (bool) $request->boolean('active'),
        ];
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:'.implode(',', array_keys(CompanyEvent::TIPOS))],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'scope' => ['required', 'in:todos,seleccion'],
            'user_ids' => ['required_if:scope,seleccion', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'time_in' => ['required_if:type,horario_especial', 'nullable', 'date_format:H:i'],
            'time_out' => ['required_if:type,horario_especial', 'nullable', 'date_format:H:i', 'after:time_in'],
            'tolerance_minutes' => ['nullable', 'integer', 'between:0,60'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'user_ids.required_if' => 'Marca a quién aplica el evento.',
            'time_in.required_if' => 'Indica la hora de entrada del horario especial.',
            'time_out.required_if' => 'Indica la hora de salida del horario especial.',
            'time_out.after' => 'La salida debe ser después de la entrada.',
            'date_to.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        $esEspecial = $data['type'] === 'horario_especial';

        $evento = CompanyEvent::create([
            'name' => $data['name'], 'type' => $data['type'],
            'date_from' => $data['date_from'], 'date_to' => $data['date_to'],
            'scope' => $data['scope'],
            'user_ids' => $data['scope'] === 'seleccion' ? array_map('intval', $data['user_ids']) : null,
            'time_in' => $esEspecial ? $data['time_in'] : null,
            'time_out' => $esEspecial ? $data['time_out'] : null,
            'tolerance_minutes' => $esEspecial ? ($data['tolerance_minutes'] ?? null) : null,
            'note' => $data['note'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $this->recalcularEvento($evento);

        return back()->with('status', "Evento «{$evento->name}» registrado.");
    }

    public function eliminarEvento(CompanyEvent $evento): RedirectResponse
    {
        $evento->delete();
        $this->recalcularEvento($evento);

        return back()->with('status', 'Evento eliminado y días recalculados.');
    }

    /** Los días del evento que ya pasaron se vuelven a evaluar (una falta puede quedar justificada, o al revés). */
    private function recalcularEvento(CompanyEvent $evento): void
    {
        $this->asistencia->olvidarEventos();

        $hasta = $evento->date_to->copy();
        $hoy = ServicioDeAsistencia::diaLocal(now());
        if ($hasta->greaterThan($hoy)) {
            $hasta = $hoy;
        }

        if ($evento->date_from->greaterThan($hasta)) {
            return;   // todo el evento es a futuro: no hay nada que corregir
        }

        $usuarios = $evento->scope === 'todos'
            ? $this->asistencia->empleados()
            : User::with('horario')->whereIn('id', $evento->user_ids ?? [])->get();

        foreach ($usuarios as $u) {
            $this->asistencia->recalcularRango($u, $evento->date_from->copy(), $hasta);
        }
    }

    /** Da de alta el horario base a todos los que aún no tienen uno. */
    public function aplicarBase(): RedirectResponse
    {
        $b = config('asistencia.horario_base');
        $n = 0;

        foreach (User::where('status', User::STATUS_APPROVED)->whereDoesntHave('horario')->get() as $u) {
            EmployeeSchedule::create([
                'user_id' => $u->id, 'weekdays' => $b['weekdays'], 'time_in' => $b['time_in'],
                'time_out' => $b['time_out'], 'tolerance_minutes' => $b['tolerancia_min'], 'active' => true,
            ]);
            $n++;
        }

        return back()->with('status', $n ? "Horario base aplicado a {$n} persona(s)." : 'Todos ya tienen horario.');
    }

    public function guardarFestivo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'unique:holidays,date'],
            'name' => ['required', 'string', 'max:120'],
        ], ['date.unique' => 'Ese día ya está como festivo.']);

        $h = Holiday::create($data);
        $this->asistencia->corregirDiaPorFestivo($h->date->copy());

        return back()->with('status', 'Festivo agregado.');
    }

    /**
     * Días de descanso obligatorio de la Ley Federal del Trabajo (art. 74):
     * 1 ene, 1.er lunes de feb, 3.er lunes de mar, 1 may, 16 sep,
     * 3.er lunes de nov, 25 dic y 1 oct cuando cambia el Ejecutivo federal
     * (cada 6 años: 2024, 2030…). Solo agrega los que faltan.
     */
    public function cargarFestivosOficiales(Request $request): RedirectResponse
    {
        $anio = (int) $request->validate(['anio' => ['required', 'integer', 'between:2020,2100']])['anio'];

        $nthLunes = fn (int $mes, int $n) => Carbon::create($anio, $mes, 1)->nthOfMonth($n, Carbon::MONDAY)->toDateString();

        $dias = [
            ["$anio-01-01", 'Año Nuevo'],
            [$nthLunes(2, 1), 'Día de la Constitución (1.er lunes de febrero)'],
            [$nthLunes(3, 3), 'Natalicio de Benito Juárez (3.er lunes de marzo)'],
            ["$anio-05-01", 'Día del Trabajo'],
            ["$anio-09-16", 'Día de la Independencia'],
            [$nthLunes(11, 3), 'Día de la Revolución (3.er lunes de noviembre)'],
            ["$anio-12-25", 'Navidad'],
        ];

        if (($anio - 2024) % 6 === 0 && $anio >= 2024) {
            $dias[] = ["$anio-10-01", 'Transmisión del Poder Ejecutivo Federal'];
        }

        $nuevos = 0;
        foreach ($dias as [$fecha, $nombre]) {
            $h = Holiday::firstOrCreate(["date" => $fecha], ["name" => $nombre]);
            if ($h->wasRecentlyCreated) {
                $nuevos++;
                $this->asistencia->corregirDiaPorFestivo($h->date->copy());
            }
        }

        return back()->with('status', $nuevos
            ? "Se agregaron {$nuevos} festivo(s) oficiales de {$anio}. Ya aparecen en la Agenda."
            : "Los festivos oficiales de {$anio} ya estaban cargados.");
    }

    public function eliminarFestivo(Holiday $festivo): RedirectResponse
    {
        $fecha = $festivo->date->copy();
        $festivo->delete();
        $this->asistencia->corregirDiaPorFestivo($fecha);

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
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(AttendanceModality::TIPOS))],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'requires_fingerprint' => ['nullable', 'boolean'],
            'time_start' => ['nullable', 'date_format:H:i'],
            'time_end' => ['nullable', 'date_format:H:i'],
            'destination' => ['nullable', 'string', 'max:150'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ], ['date_to.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.']);

        $m = AttendanceModality::create([
            'user_id' => $data['user_id'], 'type' => $data['type'],
            'date_from' => $data['date_from'], 'date_to' => $data['date_to'],
            'requires_fingerprint' => (bool) ($data['requires_fingerprint'] ?? false),
            'time_start' => $data['time_start'] ?? null, 'time_end' => $data['time_end'] ?? null,
            'destination' => $data['destination'] ?? null, 'reason' => $data['reason'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        // Los días ya pasados que estaban como falta se corrigen solos
        $this->asistencia->recalcularRango($m->user, $m->date_from->copy(), $m->date_to->copy());

        return back()->with('status', 'Incidencia registrada para '.$m->user->name.'.');
    }

    public function eliminarModalidad(AttendanceModality $modalidad): RedirectResponse
    {
        $user = $modalidad->user;
        $desde = $modalidad->date_from->copy();
        $hasta = $modalidad->date_to->copy();
        $modalidad->delete();

        // Sin la justificación, los días vuelven a evaluarse (pueden quedar como falta)
        $this->asistencia->recalcularRango($user, $desde, $hasta);

        return back()->with('status', 'Incidencia eliminada y días recalculados.');
    }
}
