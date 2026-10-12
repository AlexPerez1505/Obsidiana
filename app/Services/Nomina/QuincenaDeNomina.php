<?php

namespace App\Services\Nomina;

use App\Models\PayrollConcept;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Una quincena tal como la ve RH: en vivo mientras está abierta y congelada
 * en cuanto se cierra. Junta lo que antes vivía en la pantalla de nómina
 * (filas, totales, cierre, pago, conceptos de una sola vez, exportación)
 * para que la web y la API den exactamente los mismos números.
 */
class QuincenaDeNomina
{
    public function __construct(private MotorDeNomina $motor)
    {
    }

    /**
     * Quincena que contiene la fecha pedida; sin fecha (o si no se entiende)
     * la de hoy en hora local.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function periodo(mixed $fecha): array
    {
        // Lo mismo que Request::filled(): un texto vacío o solo espacios no cuenta
        $lleno = is_bool($fecha) || is_array($fecha) || trim((string) $fecha) !== '';

        try {
            $base = $lleno ? Carbon::parse($fecha) : ServicioDeAsistencia::diaLocal(now());
        } catch (\Throwable) {
            $base = ServicioDeAsistencia::diaLocal(now());
        }

        return MotorDeNomina::quincenaDe($base);
    }

    public function corrida(Carbon $desde, Carbon $hasta): ?PayrollRun
    {
        return $this->motor->corrida($desde, $hasta);
    }

    /**
     * Filas, totales y estado de la quincena. Cerrada: lo congelado, sin
     * recalcular. Abierta: se calcula con la asistencia y las reglas de hoy.
     */
    public function resumen(Carbon $desde, Carbon $hasta): array
    {
        $run = $this->motor->corrida($desde, $hasta);
        $hoy = ServicioDeAsistencia::diaLocal(now());

        if ($run) {
            $filas = $run->lines->map(fn ($l) => [
                'user_id' => $l->user_id, 'nombre' => $l->user_name, 'numero' => $l->employee_number,
                'sueldo' => $l->sueldo, 'bonos' => $l->bonos, 'descuentos' => $l->descuentos, 'neto' => $l->neto,
                'metricas' => $l->snapshot['metricas'] ?? [], 'lineas' => $l->snapshot['lineas'] ?? [],
                'en_juego' => $l->snapshot['en_juego'] ?? [], 'avisos' => $l->snapshot['avisos'] ?? [],
                'sin_sueldo' => false, 'congelada' => true,
            ]);
            $totales = ['sueldos' => $run->total_sueldos, 'bonos' => $run->total_bonos, 'descuentos' => $run->total_descuentos, 'neto' => $run->total_neto, 'empleados' => $run->employees];
        } else {
            $calc = $this->motor->calcular($desde, $hasta);
            $filas = $calc->map(fn ($f) => [
                'user_id' => $f['user']->id, 'nombre' => $f['user']->name, 'numero' => $f['user']->checador_id ?: $f['user']->payroll_number,
                'sueldo' => $f['sueldo'], 'bonos' => $f['bonos'], 'descuentos' => $f['descuentos'], 'neto' => $f['neto'],
                'metricas' => $f['metricas'], 'lineas' => $f['lineas'], 'en_juego' => $f['en_juego'], 'avisos' => $f['avisos'],
                'sin_sueldo' => $f['sin_sueldo'], 'congelada' => false,
            ]);
            $totales = $this->motor->totales($calc);
        }

        return [
            'run' => $run,
            'filas' => $filas,
            'totales' => $totales,
            'concluida' => $hasta->lessThan($hoy),
            'en_curso' => $desde->lessThanOrEqualTo($hoy) && $hasta->greaterThanOrEqualTo($hoy),
            'anterior' => MotorDeNomina::quincenaDe($desde->copy()->subDay()),
            'siguiente' => MotorDeNomina::quincenaDe($hasta->copy()->addDay()),
            'sin_sueldo' => $filas->where('sin_sueldo', true)->count(),
            'pendientes' => $filas->sum(fn ($f) => $f['metricas']['pendientes'] ?? 0),
        ];
    }

    /**
     * Congela la quincena. Solo cuando ya terminó: antes, los días que faltan
     * todavía pueden cambiar faltas y retardos.
     *
     * @throws \DomainException si no ha terminado o ya estaba cerrada
     */
    public function cerrar(Carbon $desde, Carbon $hasta, int $userId): PayrollRun
    {
        if ($hasta->greaterThanOrEqualTo(ServicioDeAsistencia::diaLocal(now()))) {
            throw new \DomainException('La quincena todavía no termina: espera al último día para cerrarla.');
        }

        return $this->motor->cerrar($desde, $hasta, $userId);
    }

    /** Vuelve a calcularse en vivo. Devuelve si había algo que reabrir. */
    public function reabrir(Carbon $desde, Carbon $hasta): bool
    {
        if ($run = $this->motor->corrida($desde, $hasta)) {
            $this->motor->reabrir($run);

            return true;
        }

        return false;
    }

    /**
     * Pagada ↔ pendiente de pago.
     *
     * @throws \DomainException si la quincena no está cerrada
     */
    public function alternarPagada(Carbon $desde, Carbon $hasta): PayrollRun
    {
        $run = $this->motor->corrida($desde, $hasta);

        if (! $run) {
            throw new \DomainException('Primero cierra la quincena.');
        }

        $run->update($run->status === 'pagada' ? ['status' => 'cerrada', 'paid_at' => null] : ['status' => 'pagada', 'paid_at' => now()]);

        return $run;
    }

    // ---------------- Bono o descuento de una sola vez ----------------

    public static function reglasConceptoUnico(): array
    {
        return [
            'kind' => ['required', 'in:bono,descuento'],
            'name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function mensajesConceptoUnico(): array
    {
        return ['name.required' => 'Ponle un nombre al concepto.', 'amount.required' => 'Escribe el monto.'];
    }

    /**
     * Bono o descuento solo para esta quincena, desde el desglose de una persona.
     *
     * @param  array  $data  ya validado con reglasConceptoUnico()
     *
     * @throws \DomainException si la quincena está cerrada
     */
    public function agregarConceptoUnico(User $user, Carbon $desde, Carbon $hasta, array $data, int $creadoPor): PayrollConcept
    {
        if ($this->motor->corrida($desde, $hasta)) {
            throw new \DomainException('La quincena está cerrada: reábrela para agregar conceptos.');
        }

        return PayrollConcept::create($data + [
            'user_id' => $user->id, 'recurrence' => 'unica', 'period_start' => $desde->toDateString(), 'created_by' => $creadoPor,
        ]);
    }

    // ---------------- Exportación ----------------

    /** El CSV de la quincena (lo congelado si está cerrada), listo para Excel. */
    public function descargarCsv(Carbon $desde, Carbon $hasta): StreamedResponse
    {
        $run = $this->motor->corrida($desde, $hasta);

        $filas = $run
            ? $run->lines->map(fn ($l) => ['nombre' => $l->user_name, 'numero' => $l->employee_number, 'sueldo' => $l->sueldo, 'bonos' => $l->bonos, 'descuentos' => $l->descuentos, 'neto' => $l->neto, 'm' => $l->snapshot['metricas'] ?? [], 'lineas' => $l->snapshot['lineas'] ?? []])
            : $this->motor->calcular($desde, $hasta)->map(fn ($f) => ['nombre' => $f['user']->name, 'numero' => $f['user']->checador_id ?: $f['user']->payroll_number, 'sueldo' => $f['sueldo'], 'bonos' => $f['bonos'], 'descuentos' => $f['descuentos'], 'neto' => $f['neto'], 'm' => $f['metricas'], 'lineas' => $f['lineas']]);

        return response()->streamDownload(function () use ($filas) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");   // Excel lee bien los acentos
            fputcsv($f, ['Número', 'Empleado', 'Días laborales', 'Días trabajados', 'Faltas', 'Retardos', 'Sueldo quincenal', 'Bonos', 'Descuentos', 'Neto a pagar', 'Detalle']);
            foreach ($filas as $r) {
                $detalle = collect($r['lineas'])->map(fn ($l) => ($l['tipo'] === 'bono' ? '+' : '-').number_format($l['monto'], 2, '.', '').' '.$l['nombre'])->implode(' | ');
                fputcsv($f, [
                    $r['numero'], $r['nombre'], $r['m']['laborales'] ?? '', $r['m']['dias_trabajados'] ?? '', $r['m']['faltas'] ?? '', $r['m']['retardos'] ?? '',
                    number_format($r['sueldo'], 2, '.', ''), number_format($r['bonos'], 2, '.', ''), number_format($r['descuentos'], 2, '.', ''),
                    number_format($r['neto'], 2, '.', ''), $detalle,
                ]);
            }
            fclose($f);
        }, 'nomina_'.$desde->format('Ymd').'_'.$hasta->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
