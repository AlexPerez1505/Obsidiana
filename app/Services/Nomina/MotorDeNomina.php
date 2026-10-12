<?php

namespace App\Services\Nomina;

use App\Models\EmployeeAttendance;
use App\Models\PayrollConcept;
use App\Models\PayrollRule;
use App\Models\PayrollRun;
use App\Models\PayrollRunLine;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calcula la nómina de una quincena a partir de la asistencia y de lo que RH
 * configuró en pantalla: el sueldo de cada persona, las reglas (bonos y
 * descuentos automáticos) y sus conceptos fijos. Nada de montos ni reglas
 * vive aquí: este motor solo los aplica y explica cada renglón.
 */
class MotorDeNomina
{
    /** @var Collection<int, PayrollRule>|null */
    private ?Collection $reglas = null;

    public function __construct(private ServicioDeAsistencia $asistencia)
    {
    }

    /** Quincena que contiene una fecha: 1–15 o 16–fin de mes. */
    public static function quincenaDe(Carbon $fecha): array
    {
        $fecha = $fecha->copy()->startOfDay();

        return $fecha->day <= 15
            ? [$fecha->copy()->startOfMonth(), $fecha->copy()->startOfMonth()->addDays(14)]
            : [$fecha->copy()->startOfMonth()->addDays(15), $fecha->copy()->endOfMonth()->startOfDay()];
    }

    public function reglas(): Collection
    {
        return $this->reglas ??= PayrollRule::where('active', true)->orderBy('sort')->orderBy('id')->get();
    }

    public function olvidarReglas(): void
    {
        $this->reglas = null;
    }

    // ------------------------------------------------------------------
    // Métricas de asistencia
    // ------------------------------------------------------------------

    /** Todo lo que se puede medir de una persona en un rango de días. */
    public function metricas(User $u, Carbon $desde, Carbon $hasta): array
    {
        $filas = EmployeeAttendance::where('user_id', $u->id)
            ->whereBetween('attendance_date', [$desde->toDateString(), $hasta->toDateString()])
            ->get()->keyBy(fn ($f) => $f->attendance_date->toDateString());

        $r = [
            'laborales' => 0, 'dias_trabajados' => 0, 'faltas' => 0, 'retardos' => 0, 'minutos_retardo' => 0,
            'vacaciones' => 0, 'comisiones' => 0, 'campo' => 0, 'home_office' => 0, 'eventos' => 0,
            'incapacidades' => 0, 'permisos' => 0, 'pendientes' => 0, 'salidas_sin_checar' => 0,
            'puntuales' => 0, 'minutos_trabajados' => 0,
        ];

        for ($d = $desde->copy()->startOfDay(); $d->lessThanOrEqualTo($hasta); $d->addDay()) {
            if (! $this->asistencia->esperado($u, $d)) {
                continue;
            }

            $r['laborales']++;
            $f = $filas->get($d->toDateString());

            if (! $f) {
                $r['pendientes']++;   // día sin cerrar o por venir

                continue;
            }

            $r['minutos_trabajados'] += (int) $f->worked_minutes;
            if ($f->incomplete) {
                $r['salidas_sin_checar']++;
            }

            switch ($f->status) {
                case EmployeeAttendance::PUNTUAL:
                case EmployeeAttendance::RETARDO:
                    $r['dias_trabajados']++;
                    if ($f->status === EmployeeAttendance::RETARDO) {
                        $r['retardos']++;
                        $r['minutos_retardo'] += (int) $f->late_minutes;
                    } else {
                        $r['puntuales']++;
                    }
                    if ($f->modality === 'comision') {
                        $r['comisiones']++;
                    }
                    break;
                case EmployeeAttendance::FALTA:
                    $r['faltas']++;
                    break;
                case EmployeeAttendance::JUSTIFICADA:
                    match ($f->modality) {
                        'comision' => [$r['dias_trabajados']++, $r['comisiones']++],
                        'campo' => [$r['dias_trabajados']++, $r['campo']++],
                        'home_office' => [$r['dias_trabajados']++, $r['home_office']++],
                        'evento' => [$r['dias_trabajados']++, $r['eventos']++],
                        'descanso_equipo', 'vacaciones' => $r['vacaciones']++,
                        'incapacidad' => $r['incapacidades']++,
                        default => $r['permisos']++,
                    };
                    break;
            }
        }

        $cerrados = $r['laborales'] - $r['pendientes'];
        $conChecada = $r['puntuales'] + $r['retardos'];

        $r['horas_trabajadas'] = round($r['minutos_trabajados'] / 60, 1);
        $r['asistencia_pct'] = $cerrados > 0 ? round(($cerrados - $r['faltas']) * 100 / $cerrados, 1) : null;
        $r['puntualidad_pct'] = $conChecada > 0 ? round($r['puntuales'] * 100 / $conChecada, 1) : null;

        return $r;
    }

    // ------------------------------------------------------------------
    // Cálculo de una persona
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>  sueldo, métricas, renglones (bonos/descuentos con su explicación), neto…
     */
    public function calcularUsuario(User $u, Carbon $desde, Carbon $hasta): array
    {
        $hoy = ServicioDeAsistencia::diaLocal(now());
        $cierraMes = $hasta->isSameDay($hasta->copy()->endOfMonth());
        $mesCerrado = $cierraMes && $hasta->lessThan($hoy);

        $periodo = $this->metricas($u, $desde, $hasta);
        $mes = null;   // se calcula solo si alguna regla lo necesita

        $sueldo = (float) ($u->sueldo_quincenal ?? 0);
        $diaSueldo = $sueldo / PayrollSetting::diasSueldo();

        $lineas = [];
        $enJuego = [];

        foreach ($this->reglas() as $regla) {
            if (! $regla->aplicaA($u->id)) {
                continue;
            }

            $esDeMes = $regla->timing === 'cierre_mes' || $regla->usaMes();

            if ($esDeMes && $mes === null) {
                $mes = $this->metricas($u, $desde->copy()->startOfMonth(), $desde->copy()->endOfMonth());
            }

            $valor = fn (string $m, string $scope) => ($scope === 'mes' ? $mes : $periodo)[$m] ?? null;

            // Todas las condiciones deben cumplirse
            $cumple = true;
            foreach ($regla->conditions ?? [] as $c) {
                $v = $valor($c['metric'], $c['scope'] ?? 'periodo');
                $cumple = $cumple && $v !== null && $this->comparar((float) $v, $c['op'], (float) $c['value']);
            }

            // Cuántas veces se aplica
            $veces = 1;
            if ($regla->repeat_mode === 'por_cada' && $regla->repeat_every) {
                $base = $valor((string) $regla->repeat_metric, $esDeMes ? 'mes' : 'periodo') ?? 0;
                $veces = (int) floor($base / $regla->repeat_every);
                if ($regla->repeat_max) {
                    $veces = min($veces, $regla->repeat_max);
                }
            }

            $monto = $this->monto($regla, $sueldo, $diaSueldo, $veces);

            // Reglas del mes: solo se pagan en la quincena que lo cierra (y ya terminó)
            if ($regla->timing === 'cierre_mes' && ! $mesCerrado) {
                {
                    $enJuego[] = [
                        'regla_id' => $regla->id, 'tipo' => $regla->kind, 'nombre' => $regla->name,
                        'cumple_ahora' => $cumple && $veces > 0,
                        'monto' => $cumple && $veces > 0 ? $monto : 0.0,
                        'detalle' => $cierraMes ? 'Se decide cuando termine la quincena.' : 'Se evalúa al cerrar el mes (segunda quincena).',
                    ];
                }

                continue;
            }

            if (! $cumple || $veces < 1 || $monto <= 0) {
                continue;
            }

            $lineas[] = [
                'tipo' => $regla->kind, 'origen' => 'regla', 'regla_id' => $regla->id, 'nombre' => $regla->name,
                'detalle' => $this->detalle($regla, $veces, $sueldo),
                'monto' => $monto,
            ];
        }

        // Conceptos propios de la persona
        foreach (PayrollConcept::where('user_id', $u->id)->get() as $c) {
            if (! $c->aplicaEn($desde, $hasta)) {
                continue;
            }

            $lineas[] = [
                'tipo' => $c->kind, 'origen' => 'concepto', 'concepto_id' => $c->id, 'nombre' => $c->name,
                'detalle' => $c->vigenciaTexto().($c->note ? ' · '.$c->note : ''),
                'monto' => round($c->amount, 2),
            ];
        }

        $bonos = round(collect($lineas)->where('tipo', 'bono')->sum('monto'), 2);
        $descuentos = round(collect($lineas)->where('tipo', 'descuento')->sum('monto'), 2);
        $neto = round($sueldo + $bonos - $descuentos, 2);

        $avisos = [];
        if ($u->sueldo_quincenal === null) {
            $avisos[] = 'Sin sueldo capturado: captúralo en Empleados y sueldos.';
        }
        if ($periodo['pendientes'] > 0 && $desde->lessThanOrEqualTo($hoy)) {
            $avisos[] = $periodo['pendientes'].' día(s) sin cerrar: pueden cambiar faltas y retardos.';
        }
        if ($periodo['salidas_sin_checar'] > 0) {
            $avisos[] = $periodo['salidas_sin_checar'].' día(s) sin checada de salida.';
        }
        if ($neto < 0) {
            $avisos[] = 'Los descuentos superan el sueldo de la quincena.';
        }

        return [
            'user' => $u, 'sueldo' => $sueldo, 'sin_sueldo' => $u->sueldo_quincenal === null,
            'metricas' => $periodo, 'lineas' => $lineas, 'en_juego' => $enJuego,
            'bonos' => $bonos, 'descuentos' => $descuentos, 'neto' => $neto, 'avisos' => $avisos,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function calcular(Carbon $desde, Carbon $hasta): Collection
    {
        return $this->asistencia->empleados()->map(fn (User $u) => $this->calcularUsuario($u, $desde, $hasta));
    }

    public function totales(Collection $filas): array
    {
        return [
            'sueldos' => round($filas->sum('sueldo'), 2),
            'bonos' => round($filas->sum('bonos'), 2),
            'descuentos' => round($filas->sum('descuentos'), 2),
            'neto' => round($filas->sum('neto'), 2),
            'empleados' => $filas->count(),
        ];
    }

    // ------------------------------------------------------------------
    // Cierre de quincena (congela el desglose)
    // ------------------------------------------------------------------

    public function corrida(Carbon $desde, Carbon $hasta): ?PayrollRun
    {
        return PayrollRun::with('lines')->whereDate('period_start', $desde)->whereDate('period_end', $hasta)->first();
    }

    public function cerrar(Carbon $desde, Carbon $hasta, int $userId): PayrollRun
    {
        if ($this->corrida($desde, $hasta)) {
            throw new \DomainException('Esta quincena ya está cerrada.');
        }

        $filas = $this->calcular($desde, $hasta);
        $t = $this->totales($filas);

        return DB::transaction(function () use ($desde, $hasta, $userId, $filas, $t) {
            $run = PayrollRun::create([
                'period_start' => $desde->toDateString(), 'period_end' => $hasta->toDateString(), 'status' => 'cerrada',
                'total_sueldos' => $t['sueldos'], 'total_bonos' => $t['bonos'], 'total_descuentos' => $t['descuentos'],
                'total_neto' => $t['neto'], 'employees' => $t['empleados'], 'closed_at' => now(), 'closed_by' => $userId,
            ]);

            foreach ($filas as $f) {
                PayrollRunLine::create([
                    'payroll_run_id' => $run->id, 'user_id' => $f['user']->id, 'user_name' => $f['user']->name,
                    'employee_number' => $f['user']->checador_id ?: $f['user']->payroll_number,
                    'sueldo' => $f['sueldo'], 'bonos' => $f['bonos'], 'descuentos' => $f['descuentos'], 'neto' => $f['neto'],
                    'snapshot' => [
                        'metricas' => $f['metricas'], 'lineas' => $f['lineas'], 'en_juego' => $f['en_juego'], 'avisos' => $f['avisos'],
                    ],
                ]);
            }

            return $run;
        });
    }

    public function reabrir(PayrollRun $run): void
    {
        $run->delete();   // las líneas se van en cascada; la quincena vuelve a calcularse en vivo
    }

    // ------------------------------------------------------------------

    private function comparar(float $a, string $op, float $b): bool
    {
        return match ($op) {
            '=' => abs($a - $b) < 0.0001,
            '>=' => $a >= $b,
            '>' => $a > $b,
            '<=' => $a <= $b,
            '<' => $a < $b,
            default => false,
        };
    }

    private function monto(PayrollRule $r, float $sueldo, float $diaSueldo, int $veces): float
    {
        $unitario = match ($r->calc) {
            'dias_sueldo' => $diaSueldo * $r->amount,
            'pct_sueldo' => $sueldo * $r->amount / 100,
            default => $r->amount,
        };

        return round($unitario * max(0, $veces), 2);
    }

    private function detalle(PayrollRule $r, int $veces, float $sueldo): string
    {
        $partes = [];

        if ($r->repeat_mode === 'por_cada' && $r->repeat_every) {
            $partes[] = $veces.' × ('.$r->textoPorCada().')';
        }

        $v = rtrim(rtrim(number_format($r->amount, 2, '.', ''), '0'), '.');
        $partes[] = match ($r->calc) {
            'dias_sueldo' => $v.' '.($r->amount == 1 ? 'día' : 'días').' de sueldo ($'.number_format($sueldo / PayrollSetting::diasSueldo(), 2).' c/u)',
            'pct_sueldo' => $v.'% del sueldo',
            default => '$'.number_format($r->amount, 2).' fijo',
        };

        return implode(' · ', $partes);
    }
}
