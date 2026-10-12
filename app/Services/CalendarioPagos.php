<?php

namespace App\Services;

use App\Models\Cobro;
use App\Models\Venta;
use App\Models\VentaBitacora;
use App\Models\VentaPago;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ajustes al calendario de pagos de una venta.
 *
 * El problema que resuelve: una cotización se hace hoy y la venta se
 * aprueba dos semanas después. Las fechas del plan ya no corresponden, pero
 * los intervalos entre pagos sí siguen siendo válidos.
 *
 * Regla que nunca se rompe: una parcialidad con dinero cobrado encima no se
 * mueve ni se reduce por debajo de lo cobrado. El recibo que ya tiene el
 * cliente debe seguir cuadrando.
 */
class CalendarioPagos
{
    /**
     * Recorre el calendario para que el primer pago pendiente caiga en la
     * fecha nueva, conservando los intervalos entre parcialidades.
     *
     * @return int Cuántas parcialidades se movieron.
     */
    public function recorrer(Venta $venta, Carbon $nuevaFecha): int
    {
        $pagos = $venta->pagos()->orderBy('orden')->orderBy('id')->get();

        // El ancla es el primer pago que todavía se puede mover.
        $ancla = $pagos->first(fn (VentaPago $p) => ! $p->tieneCobros() && $p->fecha);

        if (! $ancla) {
            return 0;
        }

        $desplazamiento = $ancla->fecha->diffInDays($nuevaFecha, false);

        if ($desplazamiento === 0) {
            return 0;
        }

        $antes = [];
        $movidos = 0;

        DB::transaction(function () use ($pagos, $desplazamiento, &$antes, &$movidos) {
            foreach ($pagos as $pago) {
                // Lo ya cobrado se queda donde está.
                if ($pago->tieneCobros() || ! $pago->fecha) {
                    continue;
                }

                $antes[] = [
                    'pago' => $pago->nombre,
                    'de' => $pago->fecha->format('Y-m-d'),
                    'a' => $pago->fecha->copy()->addDays($desplazamiento)->format('Y-m-d'),
                ];

                $pago->fecha = $pago->fecha->copy()->addDays($desplazamiento);
                $pago->save();

                $movidos++;
            }
        });

        if ($movidos > 0) {
            VentaBitacora::registrar(
                $venta,
                'fechas_recorridas',
                "Se recorrieron {$movidos} fecha(s) " . ($desplazamiento > 0 ? "{$desplazamiento} día(s) adelante" : abs($desplazamiento) . ' día(s) atrás'),
                ['desplazamiento_dias' => $desplazamiento, 'cambios' => $antes]
            );
        }

        return $movidos;
    }

    /**
     * Reparte una diferencia de monto entre las parcialidades que todavía
     * no se cobran. Se usa cuando se agrega equipo o sube una cantidad:
     * lo ya pagado no se toca y el ajuste cae en lo que falta.
     */
    public function rebalancear(Venta $venta): array
    {
        $pagos = $venta->pagos()->orderBy('orden')->orderBy('id')->get();

        if ($pagos->isEmpty()) {
            return ['ajustadas' => 0, 'diferencia' => 0.0];
        }

        $exigible = $venta->montoExigible();
        $planeado = (float) $pagos->sum('monto');
        $diferencia = round($exigible - $planeado, 2);

        if (abs($diferencia) < 0.01) {
            return ['ajustadas' => 0, 'diferencia' => 0.0];
        }

        // Solo se reparte entre las que no tienen cobros: las demás ya están
        // respaldadas por un recibo.
        $libres = $pagos->filter(fn (VentaPago $p) => ! $p->tieneCobros())->values();

        if ($libres->isEmpty()) {
            return ['ajustadas' => 0, 'diferencia' => $diferencia, 'sin_donde' => true];
        }

        $porCada = round($diferencia / $libres->count(), 2);

        DB::transaction(function () use ($libres, $porCada, $diferencia) {
            $acumulado = 0.0;

            foreach ($libres as $i => $pago) {
                // La última absorbe el redondeo, para que la suma cuadre exacta.
                $ajuste = $i === $libres->count() - 1
                    ? round($diferencia - $acumulado, 2)
                    : $porCada;

                $pago->monto = max(0, round((float) $pago->monto + $ajuste, 2));
                $pago->save();

                $acumulado = round($acumulado + $ajuste, 2);
            }
        });

        VentaBitacora::registrar(
            $venta,
            'plan_rebalanceado',
            ($diferencia > 0 ? 'Se repartieron $' : 'Se descontaron $') .
            number_format(abs($diferencia), 2) . ' entre ' . $libres->count() . ' parcialidad(es) sin cobrar',
            ['diferencia' => $diferencia, 'parcialidades' => $libres->count()]
        );

        return ['ajustadas' => $libres->count(), 'diferencia' => $diferencia];
    }

    /**
     * Dinero que ya entró pero que ningún pago del calendario ha recibido:
     * lo que falta aplicar de cada cobro. Es 0 mientras el calendario tenga
     * dónde recibirlo; solo queda algo si no había pagos pendientes (o en
     * cobros viejos registrados "sueltos").
     */
    public function excedentePendiente(Venta $venta): float
    {
        $venta->loadMissing('cobros.aplicaciones');

        return round((float) $venta->cobros->sum(fn (Cobro $c) => $c->sinAplicar()), 2);
    }

    /**
     * Aplica un cobro a los pagos del calendario SIN partirlo ni cambiar los
     * montos de los pagos: el cobro sigue siendo un solo recibo por lo que el
     * cliente pagó, y se reparte en orden entre los pagos que siguen pendientes.
     *
     * Ejemplo: tres pagos de $533 y un cobro de $1,000 → $533 al pago 1
     * (queda liquidado), $467 al pago 2 (le restan $66) y el pago 3 queda
     * igual. El recibo dice exactamente eso.
     *
     * Si se eligió un pago de partida se empieza por ése, luego los que le
     * siguen y, si aún sobra, los anteriores que sigan pendientes.
     *
     * @return array{aplicado: float, sin_aplicar: float, lineas: array<int, array{pago: VentaPago, monto: float, liquida: bool, resta: float}>}
     */
    public function aplicar(Venta $venta, Cobro $cobro, ?int $desdePagoId = null): array
    {
        $resultado = DB::transaction(function () use ($venta, $cobro, $desdePagoId) {
            $cobro->load('aplicaciones');
            $restante = $cobro->sinAplicar();
            $lineas = [];

            $pagos = $venta->pagos()->orderBy('orden')->orderBy('id')->get();

            if ($desdePagoId && $pagos->contains('id', $desdePagoId)) {
                $ancla = $pagos->firstWhere('id', $desdePagoId);
                $pagos = $pagos->filter(fn (VentaPago $p) => $p->orden > $ancla->orden || ($p->orden === $ancla->orden && $p->id >= $ancla->id))
                    ->concat($pagos->filter(fn (VentaPago $p) => $p->orden < $ancla->orden || ($p->orden === $ancla->orden && $p->id < $ancla->id)))
                    ->values();
            }

            foreach ($pagos as $pago) {
                if ($restante <= 0.009) {
                    break;
                }

                $pago->unsetRelation('cobros');   // saldo fresco: ya incluye lo aplicado en esta misma transacción
                $saldo = $pago->saldo();

                if ($saldo <= 0.009) {
                    continue;
                }

                $toma = round(min($saldo, $restante), 2);

                $cobro->aplicaciones()->create(['venta_pago_id' => $pago->id, 'monto' => $toma]);

                $restante = round($restante - $toma, 2);
                $lineas[] = [
                    'pago' => $pago,
                    'monto' => $toma,
                    'liquida' => round($saldo - $toma, 2) <= 0.009,
                    'resta' => max(0, round($saldo - $toma, 2)),
                ];
            }

            // El cobro "apunta" al primer pago que cubrió (para listados y recibos viejos)
            if ($lineas && $cobro->venta_pago_id !== $lineas[0]['pago']->id) {
                $cobro->venta_pago_id = $lineas[0]['pago']->id;
                $cobro->save();
            }

            return [
                'aplicado' => round(array_sum(array_column($lineas, 'monto')), 2),
                'sin_aplicar' => max(0, $restante),
                'lineas' => $lineas,
            ];
        });

        if ($resultado['lineas']) {
            $detalle = collect($resultado['lineas'])->map(fn ($l) => '«'.$l['pago']->nombre.'» $'.number_format($l['monto'], 2)
                .($l['liquida'] ? ' (liquidado)' : ' (restan $'.number_format($l['resta'], 2).')'))->implode(', ');

            VentaBitacora::registrar(
                $venta,
                'cobro_aplicado',
                "El cobro {$cobro->folio} por \$".number_format((float) $cobro->monto, 2).' se aplicó a '.count($resultado['lineas']).' pago(s): '.$detalle,
                ['cobro_id' => $cobro->id, 'aplicado' => $resultado['aplicado'], 'sin_aplicar' => $resultado['sin_aplicar']]
            );
        }

        return $resultado;
    }

    /**
     * Aplica a los pagos pendientes el dinero cobrado que ningún pago ha
     * recibido (cobros viejos "sueltos"), en orden y sin partir los cobros.
     *
     * @return array{excedente: float, ajustadas: int}
     */
    public function absorberExcedente(Venta $venta): array
    {
        $venta->load(['cobros.aplicaciones']);

        $aplicado = 0.0;
        $tocadas = 0;

        foreach ($venta->cobros->sortBy([['fecha', 'asc'], ['id', 'asc']]) as $cobro) {
            if ($cobro->sinAplicar() <= 0.009) {
                continue;
            }

            $r = $this->aplicar($venta, $cobro);
            $aplicado = round($aplicado + $r['aplicado'], 2);
            $tocadas += count($r['lineas']);
        }

        return ['excedente' => $aplicado, 'ajustadas' => $tocadas];
    }

    /**
     * Al convertir una cotización en venta, arrastra el calendario a partir
     * de hoy conservando los intervalos originales.
     */
    public function reanclarDesdeCotizacion(array $pagos, ?Carbon $desde = null): array
    {
        $desde = $desde ?: now()->startOfDay();

        $fechas = collect($pagos)->pluck('fecha')->filter()->map(fn ($f) => Carbon::parse($f));

        if ($fechas->isEmpty()) {
            return $pagos;
        }

        $primera = $fechas->min();
        $desplazamiento = $primera->diffInDays($desde, false);

        if ($desplazamiento === 0) {
            return $pagos;
        }

        return collect($pagos)->map(function (array $p) use ($desplazamiento) {
            if (! empty($p['fecha'])) {
                $p['fecha'] = Carbon::parse($p['fecha'])->addDays($desplazamiento)->format('Y-m-d');
            }

            return $p;
        })->all();
    }
}
