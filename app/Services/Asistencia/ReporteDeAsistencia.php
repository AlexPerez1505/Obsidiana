<?php

namespace App\Services\Asistencia;

use App\Models\AttendancePunch;
use App\Models\EmployeeAttendance;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Números para el panel de RH: el día de hoy y los indicadores del mes. */
class ReporteDeAsistencia
{
    public function __construct(private ServicioDeAsistencia $asistencia)
    {
    }

    /** Foto de un día: quién está, quién llegó tarde, quién falta por checar. */
    public function dia(Carbon $dia): array
    {
        $dia = $dia->copy()->startOfDay();
        $empleados = $this->asistencia->empleados();
        $filas = EmployeeAttendance::whereDate('attendance_date', $dia)->get()->keyBy('user_id');

        $r = ['esperados' => 0, 'presentes' => 0, 'retardos' => 0, 'comision' => 0, 'home_office' => 0,
            'campo' => 0, 'ausencia_justificada' => 0, 'faltas' => 0, 'sin_checar' => 0, 'incompletas' => 0, 'descanso' => 0];
        $lista = [];

        foreach ($empleados as $u) {
            if (! $this->asistencia->esperado($u, $dia)) {
                $r['descanso']++;

                continue;
            }

            $r['esperados']++;
            $f = $filas->get($u->id);
            $mod = $this->asistencia->modalidadDe($u, $dia);
            $estado = 'sin_checar';

            if ($f && $f->punches_count > 0) {
                $estado = $f->status;                      // puntual | retardo
                $f->status === EmployeeAttendance::RETARDO ? $r['retardos']++ : $r['presentes']++;
                if ($mod && $mod['type'] === 'comision') {
                    $r['comision']++;
                }
                if ($f->incomplete) {
                    $r['incompletas']++;
                }
            } elseif ($f && $f->status === EmployeeAttendance::FALTA) {
                $estado = 'falta';
                $r['faltas']++;
            } elseif ($mod) {
                $estado = $mod['type'];
                match ($mod['type']) {
                    'comision', 'evento' => $r['comision']++,
                    'home_office' => $r['home_office']++,
                    'campo' => $r['campo']++,
                    default => $r['ausencia_justificada']++,
                };
            } else {
                $r['sin_checar']++;
            }

            $lista[] = ['user' => $u, 'fila' => $f, 'estado' => $estado, 'modalidad' => $mod];
        }

        return ['resumen' => $r, 'empleados' => $lista];
    }

    /**
     * Un empleado en un mes: cada día con lo que se esperaba, su resumen y
     * sus checadas crudas (incluidas las anuladas), más los totales del mes.
     *
     * @return array{dias: list<array>, resumen: array}
     */
    public function mesDeEmpleado(User $user, Carbon $mes): array
    {
        $mes = $mes->copy()->startOfMonth();
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

        return [
            'dias' => $dias,
            'resumen' => [
                'puntuales' => $filas->where('status', EmployeeAttendance::PUNTUAL)->count(),
                'retardos' => $filas->where('status', EmployeeAttendance::RETARDO)->count(),
                'faltas' => $filas->where('status', EmployeeAttendance::FALTA)->count(),
                'justificadas' => $filas->where('status', EmployeeAttendance::JUSTIFICADA)->count(),
                'horas' => round($filas->sum('worked_minutes') / 60, 1),
            ],
        ];
    }

    public function ultimasChecadas(int $limite = 12)
    {
        return AttendancePunch::validas()->with(['user', 'device'])
            ->whereDate('work_date', ServicioDeAsistencia::diaLocal(now()))
            ->latest('punched_at')->limit($limite)->get();
    }

    /** Indicadores de un mes. */
    public function mes(Carbon $mes): array
    {
        $ini = $mes->copy()->startOfMonth();
        $fin = $mes->copy()->endOfMonth();
        $filas = EmployeeAttendance::with('employee:id,name')->whereBetween('attendance_date', [$ini->toDateString(), $fin->toDateString()])->get();

        $conChecada = $filas->filter(fn ($f) => $f->punches_count > 0 && in_array($f->status, [EmployeeAttendance::PUNTUAL, EmployeeAttendance::RETARDO], true));
        $puntuales = $conChecada->where('status', EmployeeAttendance::PUNTUAL)->count();

        $minutos = fn ($f) => (int) substr($f->check_in, 0, 2) * 60 + (int) substr($f->check_in, 3, 2);
        $hhmm = fn (float $m) => sprintf('%02d:%02d', intdiv((int) round($m), 60), ((int) round($m)) % 60);

        $porEmpleado = $conChecada->groupBy('user_id')->map(fn ($g) => [
            'nombre' => $g->first()->employee?->name ?? '—',
            'dias' => $g->count(),
            'promedio' => $g->avg($minutos),
            'retardos' => $g->where('status', EmployeeAttendance::RETARDO)->count(),
        ]);
        $faltas = $filas->where('status', EmployeeAttendance::FALTA)->groupBy('user_id')
            ->map(fn ($g) => ['nombre' => $g->first()->employee?->name ?? '—', 'n' => $g->count()])->sortByDesc('n');

        $masPuntual = $porEmpleado->where('dias', '>=', 3)->sortBy('promedio')->first();
        $masRetardos = $porEmpleado->where('retardos', '>', 0)->sortByDesc('retardos')->first();
        $masFaltas = $faltas->first();

        return [
            'puntualidad' => $conChecada->count() ? round($puntuales * 100 / $conChecada->count(), 1) : null,
            'promedio_llegada' => $conChecada->count() ? $hhmm($conChecada->avg($minutos)) : null,
            'mas_puntual' => $masPuntual ? ['nombre' => $masPuntual['nombre'], 'promedio' => $hhmm($masPuntual['promedio'])] : null,
            'mas_retardos' => $masRetardos ? ['nombre' => $masRetardos['nombre'], 'n' => $masRetardos['retardos']] : null,
            'mas_faltas' => $masFaltas,
            'retardos' => $filas->where('status', EmployeeAttendance::RETARDO)->count(),
            'faltas' => $filas->where('status', EmployeeAttendance::FALTA)->count(),
            'home_office' => $filas->where('modality', 'home_office')->count(),
            'oficina' => $conChecada->whereNull('modality')->count(),
            'comisiones' => $filas->where('modality', 'comision')->count(),
        ];
    }

    /** Serie de los últimos N meses para las gráficas. */
    public function serieMensual(int $meses = 6): array
    {
        $serie = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $m = now()->copy()->startOfMonth()->subMonths($i);
            $k = $this->mes($m);
            $serie[] = ['mes' => $m->translatedFormat('M y'), 'puntualidad' => $k['puntualidad'], 'retardos' => $k['retardos'], 'faltas' => $k['faltas']];
        }

        return $serie;
    }

    /** Hora promedio de entrada de los últimos N días con checadas. */
    public function horaPromedioPorDia(int $dias = 14): array
    {
        $desde = ServicioDeAsistencia::diaLocal(now())->subDays($dias - 1);
        $filas = EmployeeAttendance::where('attendance_date', '>=', $desde->toDateString())
            ->where('punches_count', '>', 0)->get()->groupBy(fn ($f) => $f->attendance_date->toDateString());

        $r = [];
        foreach ($filas as $fecha => $g) {
            $m = $g->avg(fn ($f) => (int) substr($f->check_in, 0, 2) * 60 + (int) substr($f->check_in, 3, 2));
            $r[] = ['fecha' => Carbon::parse($fecha)->format('d/m'), 'minutos' => round($m), 'hhmm' => sprintf('%02d:%02d', intdiv((int) round($m), 60), ((int) round($m)) % 60)];
        }

        return collect($r)->sortBy('fecha')->values()->all();
    }

    /** Asistencia del mes por puesto (el sistema no maneja departamentos: se usa el puesto/cargo). */
    public function porPuesto(Carbon $mes): array
    {
        $ini = $mes->copy()->startOfMonth()->toDateString();
        $fin = $mes->copy()->endOfMonth()->toDateString();

        return EmployeeAttendance::with('employee:id,position,cargo')->whereBetween('attendance_date', [$ini, $fin])->get()
            ->groupBy(fn ($f) => $f->employee?->position ?: $f->employee?->cargo ?: 'Sin puesto')
            ->map(function ($g, $puesto) {
                $con = $g->where('punches_count', '>', 0);

                return [
                    'puesto' => $puesto,
                    'puntualidad' => $con->count() ? round($con->where('status', EmployeeAttendance::PUNTUAL)->count() * 100 / $con->count()) : null,
                    'faltas' => $g->where('status', EmployeeAttendance::FALTA)->count(),
                    'dias' => $g->count(),
                ];
            })->sortByDesc('dias')->values()->all();
    }
}
