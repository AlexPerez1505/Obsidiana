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
     * Dinero que ya entró pero que ninguna parcialidad tiene ligado
     * todavía: cobros sueltos (sin parcialidad asignada) más la parte de
     * un cobro que se pasó de lo que le tocaba a la parcialidad a la que
     * se ligó. Es justo lo que falta por repartir.
     */
    public function excedentePendiente(Venta $venta): float
    {
        $porParcialidad = round($venta->pagos->sum(function (VentaPago $pago) {
            $acumulado = 0.0;
            $exceso = 0.0;

            foreach ($pago->cobros->sortBy('id') as $cobro) {
                $disponible = round((float) $pago->monto - $acumulado, 2);
                $exceso += max(0, round((float) $cobro->monto - max(0, $disponible), 2));
                $acumulado += (float) $cobro->monto;
            }

            return $exceso;
        }), 2);

        $sueltos = round((float) $venta->cobros->whereNull('venta_pago_id')->sum('monto'), 2);

        return round($porParcialidad + $sueltos, 2);
    }

    /**
     * Dinero cobrado que ninguna parcialidad refleja todavía —un abono
     * suelto, o la parte de un cobro que se pasó de lo que le tocaba a su
     * parcialidad— no se "borra" de las parcialidades siguientes: se
     * REASIGNA a ellas, empezando por la más próxima que siga pendiente.
     *
     * Es la diferencia importante: reducir el monto de una parcialidad que
     * nunca recibió ese dinero la deja en $0 sin cobrar (parece que no
     * debe nada, cuando en realidad nadie le abonó). Reasignar el cobro
     * hace que esa parcialidad quede correctamente marcada como pagada.
     */
    public function absorberExcedente(Venta $venta): array
    {
        $resultado = DB::transaction(function () use ($venta) {
            $repartido = 0.0;
            $tocadas = 0;

            // 1) Cobros ligados a una parcialidad que, ellos solos, superan
            //    lo que le tocaba: se recortan a lo justo y el resto se
            //    desliga, para repartirse en el paso 2 como un suelto más.
            $pagos = $venta->pagos()->orderBy('orden')->orderBy('id')->get();

            foreach ($pagos as $pago) {
                $acumulado = 0.0;

                foreach ($pago->cobros()->orderBy('id')->get() as $cobro) {
                    $disponible = round((float) $pago->monto - $acumulado, 2);

                    if ($disponible <= 0.009) {
                        // La parcialidad ya quedó cubierta por cobros
                        // anteriores: este cobro completo sobra.
                        $cobro->venta_pago_id = null;
                        $cobro->save();

                        continue;
                    }

                    if ((float) $cobro->monto > $disponible + 0.009) {
                        $exceso = round((float) $cobro->monto - $disponible, 2);

                        Cobro::create([
                            'venta_id' => $venta->id,
                            'venta_pago_id' => null,
                            'folio' => $cobro->folio.'-B',
                            'fecha' => $cobro->fecha,
                            'monto' => $exceso,
                            'metodo' => $cobro->metodo,
                            'referencia' => $cobro->referencia,
                            'nota' => trim(($cobro->nota ? $cobro->nota.' · ' : '').'Excedente de '.$cobro->folio),
                            'registrado_por' => $cobro->registrado_por,
                        ]);

                        $cobro->monto = $disponible;
                        $cobro->save();
                    }

                    $acumulado = round($acumulado + (float) $cobro->monto, 2);
                }
            }

            // 2) Cobros sueltos (sin parcialidad, incluyendo los que se
            //    acaban de desligar arriba): se asignan a la parcialidad
            //    pendiente más próxima, completos o partidos, en orden.
            $pagos = $venta->pagos()->orderBy('orden')->orderBy('id')->get();
            $sueltos = $venta->cobros()->whereNull('venta_pago_id')->orderBy('fecha')->orderBy('id')->get();

            foreach ($sueltos as $suelto) {
                $restante = (float) $suelto->monto;
                $sufijo = 1;

                foreach ($pagos as $pago) {
                    if ($restante <= 0.009) {
                        break;
                    }

                    $saldoPago = $pago->saldo();

                    if ($saldoPago <= 0.009) {
                        continue;
                    }

                    if ($restante <= $saldoPago + 0.009) {
                        // Cabe completo: se liga sin partirlo.
                        $suelto->venta_pago_id = $pago->id;
                        $suelto->save();

                        $repartido = round($repartido + $restante, 2);
                        $tocadas++;
                        $restante = 0.0;

                        break;
                    }

                    // No cabe completo: la porción que sí cabe se liga
                    // aparte, y el resto sigue probando con la siguiente
                    // parcialidad pendiente.
                    Cobro::create([
                        'venta_id' => $venta->id,
                        'venta_pago_id' => $pago->id,
                        'folio' => $suelto->folio.'-'.$sufijo++,
                        'fecha' => $suelto->fecha,
                        'monto' => $saldoPago,
                        'metodo' => $suelto->metodo,
                        'referencia' => $suelto->referencia,
                        'nota' => $suelto->nota,
                        'registrado_por' => $suelto->registrado_por,
                    ]);

                    $repartido = round($repartido + $saldoPago, 2);
                    $tocadas++;
                    $restante = round($restante - $saldoPago, 2);
                    $suelto->monto = $restante;
                    $suelto->save();
                }
            }

            return ['excedente' => round($repartido, 2), 'ajustadas' => $tocadas];
        });

        if ($resultado['excedente'] > 0.009) {
            VentaBitacora::registrar(
                $venta,
                'excedente_absorbido',
                'Dinero cobrado que no estaba ligado a ninguna parcialidad: se asignaron $'
                    .number_format($resultado['excedente'], 2).' a '.$resultado['ajustadas'].' parcialidad(es)',
                $resultado
            );
        }

        return $resultado;
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
