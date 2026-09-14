<?php

namespace App\Http\Controllers;

use App\Models\Cobro;
use App\Models\ComisionPago;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Control de comisiones.
 *
 * Responde, mes por mes: quién vendió más, cuánto se le cobró a sus
 * clientes, cuánto le toca de comisión según su porcentaje, y cuánto de
 * eso ya se le pagó.
 *
 * La comisión se puede calcular sobre lo vendido o sobre lo cobrado en el
 * mes; lo normal es sobre lo cobrado (no se paga comisión de dinero que
 * todavía no entra), pero se deja elegir.
 */
class ComisionController extends Controller
{
    public const BASES = [
        'cobrado' => 'Sobre lo cobrado',
        'vendido' => 'Sobre lo vendido',
    ];

    public function index(Request $request): View
    {
        $periodo = $this->periodo($request->get('periodo'));
        $base = array_key_exists($request->get('base'), self::BASES) ? $request->get('base') : 'cobrado';

        $inicio = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();
        $fin = (clone $inicio)->endOfMonth();

        $filas = $this->filasDelPeriodo($inicio, $fin, $periodo, $base);

        $pagos = ComisionPago::with(['asesor', 'registradoPor'])
            ->where('periodo', $periodo)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return view('structure.commercial_management.comisiones.index', [
            'periodo' => $periodo,
            'periodoTexto' => ucfirst($inicio->locale('es')->isoFormat('MMMM YYYY')),
            'anterior' => (clone $inicio)->subMonth()->format('Y-m'),
            'siguiente' => (clone $inicio)->addMonth()->format('Y-m'),
            'esMesActual' => $periodo === now()->format('Y-m'),
            'base' => $base,
            'bases' => self::BASES,
            'filas' => $filas,
            'resumen' => [
                'vendido' => (float) $filas->sum('vendido'),
                'cobrado' => (float) $filas->sum('cobrado'),
                'comision' => (float) $filas->sum('comision'),
                'pagado' => (float) $filas->sum('pagado'),
                'pendiente' => (float) $filas->sum('pendiente'),
                'ventas' => (int) $filas->sum('ventas'),
            ],
            'maximo' => (float) ($filas->max('vendido') ?: 0),
            'pagos' => $pagos,
            'puedeGestionar' => $request->user()->can('comisiones.gestionar'),
        ]);
    }

    /** Define (o cambia) el porcentaje de comisión de un asesor. */
    public function porcentaje(Request $request, User $asesor): RedirectResponse
    {
        $data = $request->validate([
            'porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $asesor->porcentaje_comision = $data['porcentaje'] === null || $data['porcentaje'] === '' ? null : $data['porcentaje'];
        $asesor->save();

        return back()->with('status', "Porcentaje de {$asesor->name} actualizado.");
    }

    /** Registra un pago de comisión a un asesor por un mes. */
    public function pagar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'periodo' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'base' => ['required', Rule::in(array_keys(self::BASES))],
            'nota' => ['nullable', 'string', 'max:255'],
        ], [
            'monto.min' => 'El monto del pago debe ser mayor a cero.',
        ]);

        $data['registrado_por'] = $request->user()->id;

        $pago = ComisionPago::create($data);

        return redirect()
            ->route('commercial.comisiones.index', ['periodo' => $data['periodo'], 'base' => $data['base']])
            ->with('status', 'Pago de $'.number_format((float) $pago->monto, 2).' registrado a '.$pago->asesor->name.'.');
    }

    /** Quita un pago capturado por error. */
    public function eliminarPago(ComisionPago $pago): RedirectResponse
    {
        $pago->delete();

        return back()->with('status', 'Pago de comisión eliminado.');
    }

    /**
     * Una fila por asesor con todo lo del mes: ventas, vendido, cobrado,
     * su porcentaje, la comisión que resulta y lo que ya se le pagó.
     */
    private function filasDelPeriodo(Carbon $inicio, Carbon $fin, string $periodo, string $base)
    {
        // Lo vendido en el mes, sin las canceladas.
        $vendido = Venta::where('estado', '!=', 'cancelada')
            ->whereBetween('created_at', [$inicio, $fin])
            ->selectRaw('seller_id, COUNT(*) as ventas, SUM(total) as monto')
            ->groupBy('seller_id')
            ->get()
            ->keyBy('seller_id');

        // Lo que entró en el mes de las ventas de cada asesor, sin importar
        // en qué mes se vendió: la comisión sobre cobrado se paga cuando
        // el dinero llega.
        $cobrado = Cobro::join('ventas', 'cobros.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', '!=', 'cancelada')
            ->whereBetween('cobros.fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->selectRaw('ventas.seller_id, SUM(cobros.monto) as monto')
            ->groupBy('ventas.seller_id')
            ->pluck('monto', 'seller_id');

        $pagado = ComisionPago::where('periodo', $periodo)
            ->selectRaw('user_id, SUM(monto) as monto')
            ->groupBy('user_id')
            ->pluck('monto', 'user_id');

        // Aparece todo asesor con movimiento en el mes, más los que ya tienen
        // porcentaje definido aunque este mes no hayan vendido.
        $ids = $vendido->keys()
            ->merge($cobrado->keys())
            ->merge($pagado->keys())
            ->merge(User::whereNotNull('porcentaje_comision')->pluck('id'))
            ->filter()
            ->unique();

        $usuarios = User::whereIn('id', $ids)->get()->keyBy('id');

        return $usuarios->map(function (User $u) use ($vendido, $cobrado, $pagado, $base) {
            $montoVendido = (float) ($vendido[$u->id]->monto ?? 0);
            $montoCobrado = (float) ($cobrado[$u->id] ?? 0);
            $pct = $u->porcentaje_comision !== null ? (float) $u->porcentaje_comision : null;
            $baseMonto = $base === 'vendido' ? $montoVendido : $montoCobrado;
            $comision = $pct !== null ? round($baseMonto * $pct / 100, 2) : 0.0;
            $yaPagado = (float) ($pagado[$u->id] ?? 0);

            return [
                'asesor' => $u,
                'ventas' => (int) ($vendido[$u->id]->ventas ?? 0),
                'vendido' => $montoVendido,
                'cobrado' => $montoCobrado,
                'porcentaje' => $pct,
                'base_monto' => $baseMonto,
                'comision' => $comision,
                'pagado' => $yaPagado,
                'pendiente' => round($comision - $yaPagado, 2),
            ];
        })
            ->sortByDesc(fn ($f) => [$f['vendido'], $f['cobrado']])
            ->values();
    }

    /** "2026-09" válido, o el mes en curso si vino algo raro. */
    private function periodo(?string $valor): string
    {
        return $valor && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $valor) ? $valor : now()->format('Y-m');
    }
}
