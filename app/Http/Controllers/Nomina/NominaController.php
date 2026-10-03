<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\PayrollConcept;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\Asistencia\ServicioDeAsistencia;
use App\Services\Nomina\MotorDeNomina;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Nómina por quincena: se calcula sola con la asistencia y las reglas de RH. */
class NominaController extends Controller
{
    public function __construct(private MotorDeNomina $motor)
    {
    }

    public function index(Request $request): View
    {
        [$desde, $hasta] = $this->periodo($request);
        $run = $this->motor->corrida($desde, $hasta);
        $hoy = ServicioDeAsistencia::diaLocal(now());

        if ($run) {
            // Quincena cerrada: se muestra lo congelado, no se recalcula
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

        return view('nomina.quincena', [
            'desde' => $desde, 'hasta' => $hasta, 'run' => $run,
            'filas' => $filas, 'totales' => $totales,
            'concluida' => $hasta->lessThan($hoy),
            'enCurso' => $desde->lessThanOrEqualTo($hoy) && $hasta->greaterThanOrEqualTo($hoy),
            'anterior' => MotorDeNomina::quincenaDe($desde->copy()->subDay()),
            'siguiente' => MotorDeNomina::quincenaDe($hasta->copy()->addDay()),
            'sinSueldo' => $filas->where('sin_sueldo', true)->count(),
            'pendientes' => $filas->sum(fn ($f) => $f['metricas']['pendientes'] ?? 0),
        ]);
    }

    public function cerrar(Request $request): RedirectResponse
    {
        [$desde, $hasta] = $this->periodo($request);

        if ($hasta->greaterThanOrEqualTo(ServicioDeAsistencia::diaLocal(now()))) {
            return back()->withErrors(['periodo' => 'La quincena todavía no termina: espera al último día para cerrarla.']);
        }

        try {
            $run = $this->motor->cerrar($desde, $hasta, $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['periodo' => $e->getMessage()]);
        }

        return back()->with('status', 'Quincena cerrada: '.$run->employees.' empleados, neto $'.number_format($run->total_neto, 2).'. Los montos quedaron congelados.');
    }

    public function reabrir(Request $request): RedirectResponse
    {
        [$desde, $hasta] = $this->periodo($request);

        if ($run = $this->motor->corrida($desde, $hasta)) {
            $this->motor->reabrir($run);
        }

        return back()->with('status', 'Quincena reabierta: vuelve a calcularse con la asistencia y las reglas actuales.');
    }

    public function pagada(Request $request): RedirectResponse
    {
        [$desde, $hasta] = $this->periodo($request);
        $run = $this->motor->corrida($desde, $hasta);

        if (! $run) {
            return back()->withErrors(['periodo' => 'Primero cierra la quincena.']);
        }

        $run->update($run->status === 'pagada' ? ['status' => 'cerrada', 'paid_at' => null] : ['status' => 'pagada', 'paid_at' => now()]);

        return back()->with('status', $run->status === 'pagada' ? 'Quincena marcada como pagada.' : 'Quincena marcada como pendiente de pago.');
    }

    /** Bono o descuento solo para esta quincena, desde el desglose de una persona. */
    public function conceptoUnico(Request $request, User $user): RedirectResponse
    {
        [$desde, $hasta] = $this->periodo($request);

        if ($this->motor->corrida($desde, $hasta)) {
            return back()->withErrors(['periodo' => 'La quincena está cerrada: reábrela para agregar conceptos.']);
        }

        $data = $request->validate([
            'kind' => ['required', 'in:bono,descuento'],
            'name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['name.required' => 'Ponle un nombre al concepto.', 'amount.required' => 'Escribe el monto.']);

        PayrollConcept::create($data + [
            'user_id' => $user->id, 'recurrence' => 'unica', 'period_start' => $desde->toDateString(), 'created_by' => $request->user()->id,
        ]);

        return back()->with('status', ($data['kind'] === 'bono' ? 'Bono' : 'Descuento')." agregado a {$user->name} solo para esta quincena.")
            ->with('abrir', 'u'.$user->id);
    }

    public function historial(): View
    {
        return view('nomina.historial', [
            'corridas' => PayrollRun::with('cerro')->orderByDesc('period_start')->limit(48)->get(),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        [$desde, $hasta] = $this->periodo($request);
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

    private function periodo(Request $request): array
    {
        try {
            $base = $request->filled('desde') ? Carbon::parse($request->get('desde')) : ServicioDeAsistencia::diaLocal(now());
        } catch (\Throwable) {
            $base = ServicioDeAsistencia::diaLocal(now());
        }

        return MotorDeNomina::quincenaDe($base);
    }
}
