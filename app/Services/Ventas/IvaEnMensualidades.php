<?php

namespace App\Services\Ventas;

use App\Models\User;
use App\Models\Venta;
use App\Models\VentaBitacora;
use App\Models\VentaPago;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Agregar IVA a las mensualidades de una venta que salió sin IVA.
 *
 * Pasa cuando el cliente, ya con el plan corriendo, pide factura. El IVA
 * (16 %) se carga solo sobre lo que falta por pagar de cada mensualidad
 * elegida: lo ya cobrado no se toca y los recibos que tiene el cliente
 * siguen cuadrando. El total de la venta sube lo mismo.
 *
 * Se guarda en cada mensualidad cuánto IVA se le agregó (venta_pagos.iva_monto)
 * para enseñarlo y para poder quitarlo mientras no se haya cobrado.
 */
class IvaEnMensualidades
{
    public const TASA = 0.16;

    /** Mensualidades con algo pendiente que todavía no traen IVA. */
    public function candidatas(Venta $venta): Collection
    {
        if ($venta->aplica_iva || $venta->cancelada()) {
            return collect();
        }

        $venta->loadMissing('pagos.cobros');

        return $venta->pagos
            ->filter(fn (VentaPago $p) => $p->saldo() > 0.009 && (float) $p->iva_monto <= 0)
            ->map(fn (VentaPago $p) => [
                'pago' => $p,
                'saldo' => $p->saldo(),
                'iva' => round($p->saldo() * self::TASA, 2),
            ])
            ->values();
    }

    /**
     * @param  array<int>|null  $pagoIds  null = todas las candidatas
     * @return string mensaje para el usuario
     */
    public function agregar(Venta $venta, ?array $pagoIds, User $user): string
    {
        if ($venta->aplica_iva) {
            throw ValidationException::withMessages(['iva' => 'Esta venta ya se hizo con IVA.']);
        }
        if ($venta->cancelada()) {
            throw ValidationException::withMessages(['iva' => 'La venta está cancelada.']);
        }

        return DB::transaction(function () use ($venta, $pagoIds, $user) {
            $venta = Venta::whereKey($venta->id)->lockForUpdate()->firstOrFail();
            $elegidas = $this->candidatas($venta)
                ->when($pagoIds !== null, fn ($c) => $c->filter(fn ($x) => in_array($x['pago']->id, array_map('intval', $pagoIds), true)));

            if ($elegidas->isEmpty()) {
                throw ValidationException::withMessages(['iva' => 'Elige al menos una mensualidad pendiente sin IVA.']);
            }

            // Pesos cerrados: las mensualidades quedan sin centavos y lo que
            // sobra se junta en la última elegida (el IVA total no cambia).
            $elegidas = $elegidas->values();
            $nuevos = \App\Services\CalculadoraCotizacion::cerrarCentavos(
                $elegidas->map(fn ($x) => round((float) $x['pago']->monto + $x['iva'], 2))->all()
            );
            $ivas = $elegidas->map(fn ($x, $i) => round($nuevos[$i] - (float) $x['pago']->monto, 2));
            if ($ivas->contains(fn ($v) => $v < 0)) {
                // Caso raro (IVA de centavos en una mensualidad con centavos): sin cerrar.
                $ivas = $elegidas->map(fn ($x) => $x['iva']);
            }

            $total = 0.0;
            $detalle = [];
            foreach ($elegidas as $i => $x) {
                /** @var VentaPago $p */
                $p = $x['pago'];
                $iva = (float) $ivas[$i];
                $p->update(['monto' => round((float) $p->monto + $iva, 2), 'iva_monto' => $iva]);
                $total += $iva;
                $detalle[] = "«{$p->nombre}» +$".number_format($iva, 2);
            }
            $total = round($total, 2);

            $venta->forceFill([
                'iva_monto' => round((float) $venta->iva_monto + $total, 2),
                'total' => round((float) $venta->total + $total, 2),
                'total_contrato' => round((float) $venta->total_contrato + $total, 2),
            ])->save();

            VentaBitacora::registrar($venta, 'iva_agregado',
                $user->name.' agregó IVA (16 %) a '.count($detalle).' mensualidad(es): '.implode(', ', $detalle).'. Total +$'.number_format($total, 2),
                ['pagos' => $elegidas->map(fn ($x) => ['id' => $x['pago']->id, 'iva' => $x['iva']])->all(), 'total' => $total]);

            return 'Se agregó $'.number_format($total, 2).' de IVA a '.count($detalle).' mensualidad(es). El total de la venta ahora es $'.number_format((float) $venta->total, 2).'.';
        });
    }

    /**
     * Quita el IVA agregado de las mensualidades donde todavía no se ha
     * cobrado: si ya se pagó parte del IVA, esa mensualidad se queda como está.
     */
    public function quitar(Venta $venta, User $user): string
    {
        return DB::transaction(function () use ($venta, $user) {
            $venta = Venta::whereKey($venta->id)->lockForUpdate()->firstOrFail();
            $venta->load('pagos.cobros');

            $quitado = 0.0;
            $n = 0;
            $quedan = 0;
            foreach ($venta->pagos->filter(fn ($p) => (float) $p->iva_monto > 0) as $p) {
                $iva = (float) $p->iva_monto;
                if ($p->saldo() + 0.009 < $iva) {
                    $quedan++;

                    continue;
                }
                $p->update(['monto' => round((float) $p->monto - $iva, 2), 'iva_monto' => 0]);
                $quitado += $iva;
                $n++;
            }

            if ($n === 0) {
                throw ValidationException::withMessages(['iva' => $quedan
                    ? 'No se puede quitar: ya se cobró parte del IVA en esas mensualidades.'
                    : 'Esta venta no tiene IVA agregado en sus mensualidades.']);
            }

            $quitado = round($quitado, 2);
            $venta->forceFill([
                'iva_monto' => max(0, round((float) $venta->iva_monto - $quitado, 2)),
                'total' => round((float) $venta->total - $quitado, 2),
                'total_contrato' => round((float) $venta->total_contrato - $quitado, 2),
            ])->save();

            VentaBitacora::registrar($venta, 'iva_quitado',
                $user->name.' quitó el IVA agregado de '.$n.' mensualidad(es): -$'.number_format($quitado, 2), ['total' => $quitado]);

            return 'Se quitó $'.number_format($quitado, 2).' de IVA de '.$n.' mensualidad(es).'
                .($quedan ? " {$quedan} se quedaron con IVA porque ya se cobró parte de él." : '');
        });
    }
}
