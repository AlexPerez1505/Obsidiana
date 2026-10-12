<?php

namespace App\Services\Ventas;

use App\Models\Cobro;
use App\Models\ComisionPago;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

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
 *
 * Vivía en ComisionController; lo usan la pantalla y la API.
 */
class Comisiones
{
    public const BASES = [
        'cobrado' => 'Sobre lo cobrado',
        'vendido' => 'Sobre lo vendido',
    ];

    public const PATRON_PERIODO = '/^\d{4}-(0[1-9]|1[0-2])$/';

    public const REGLAS_PORCENTAJE = [
        'porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
    ];

    public const MENSAJES_PAGO = [
        'monto.min' => 'El monto del pago debe ser mayor a cero.',
    ];

    public static function reglasPago(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'periodo' => ['required', 'regex:'.self::PATRON_PERIODO],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'base' => ['required', Rule::in(array_keys(self::BASES))],
            'nota' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** "2026-09" válido, o el mes en curso si vino algo raro. */
    public static function periodo(?string $valor): string
    {
        return $valor && preg_match(self::PATRON_PERIODO, $valor) ? $valor : now()->format('Y-m');
    }

    /** La base pedida, o "cobrado" si vino algo raro. */
    public static function base(?string $valor): string
    {
        return array_key_exists((string) $valor, self::BASES) ? $valor : 'cobrado';
    }

    /** Primer y último momento del mes "2026-09". */
    public static function limites(string $periodo): array
    {
        $inicio = Carbon::createFromFormat('Y-m', $periodo)->startOfMonth();

        return [$inicio, (clone $inicio)->endOfMonth()];
    }

    /**
     * Una fila por asesor con todo lo del mes: ventas, vendido, cobrado,
     * su porcentaje, la comisión que resulta y lo que ya se le pagó.
     */
    public function filasDelPeriodo(Carbon $inicio, Carbon $fin, string $periodo, string $base): Collection
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

    /** Los totales del mes (la fila de abajo de la tabla). */
    public function resumen(Collection $filas): array
    {
        return [
            'vendido' => (float) $filas->sum('vendido'),
            'cobrado' => (float) $filas->sum('cobrado'),
            'comision' => (float) $filas->sum('comision'),
            'pagado' => (float) $filas->sum('pagado'),
            'pendiente' => (float) $filas->sum('pendiente'),
            'ventas' => (int) $filas->sum('ventas'),
        ];
    }

    /** Pagos de comisión registrados para ese mes, los más recientes primero. */
    public function pagosDelPeriodo(string $periodo): Collection
    {
        return ComisionPago::with(['asesor', 'registradoPor'])
            ->where('periodo', $periodo)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }

    /** Define (o quita, con null o vacío) el porcentaje de comisión de un asesor. */
    public function definirPorcentaje(User $asesor, $porcentaje): void
    {
        $asesor->porcentaje_comision = $porcentaje === null || $porcentaje === '' ? null : $porcentaje;
        $asesor->save();
    }

    /** Registra un pago de comisión a un asesor por un mes. */
    public function registrarPago(array $data, User $registra): ComisionPago
    {
        $data['registrado_por'] = $registra->id;

        return ComisionPago::create($data);
    }
}
