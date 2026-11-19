<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Fuente de verdad de los montos de una cotización / venta / factura.
 *
 * Todos los métodos son puros: reciben datos y devuelven números, sin tocar
 * la base de datos. Se reutiliza desde CotizacionController, VentaController y
 * FacturaController, y su lógica se replica en el JS del formulario.
 */
class CalculadoraCotizacion
{
    public const IVA_TASA = 0.16;

    /**
     * Subtotal = Σ (precio_unitario + sobreprecio) × cantidad, excluyendo regalos.
     *
     * @param  array<int, array{precio_unitario: float|int|string, sobreprecio?: float|int|string, cantidad: float|int|string, es_regalo?: bool}>  $items
     */
    public function subtotal(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            if (! empty($item['es_regalo'])) {
                continue;
            }

            $precio = (float) ($item['precio_unitario'] ?? 0);
            $sobre = (float) ($item['sobreprecio'] ?? 0);
            $cant = (int) ($item['cantidad'] ?? 0);

            $total += ($precio + $sobre) * $cant;
        }

        return round($total, 2);
    }

    /**
     * Monto del descuento según tipo (porcentaje|monto).
     */
    public function descuento(float $subtotal, ?string $tipo, float $valor): float
    {
        if ($valor <= 0 || $tipo === null) {
            return 0.0;
        }

        if ($tipo === 'porcentaje') {
            return round($subtotal * min($valor, 100) / 100, 2);
        }

        return round(min($valor, $subtotal), 2);
    }

    /**
     * IVA sobre la base (subtotal - descuento + envío) si aplica.
     */
    public function iva(float $base, bool $aplica): float
    {
        return $aplica ? round($base * self::IVA_TASA, 2) : 0.0;
    }

    /**
     * Devuelve el desglose completo de montos.
     *
     * @return array{subtotal: float, descuento: float, descuento_cupon: float, base: float, iva: float, total: float, total_contrato: float}
     */
    public function desglose(
        array $items,
        ?string $descuentoTipo,
        float $descuentoValor,
        float $envio,
        bool $aplicaIva,
        float $valorACuenta,
        float $descuentoCupon = 0.0
    ): array {
        $subtotal = $this->subtotal($items);
        $descuento = $this->descuento($subtotal, $descuentoTipo, $descuentoValor);
        // El cupón ya viene calculado por App\Services\Promociones\MotorDeCupones
        // (nunca del navegador); aquí solo se resta.
        $descuentoCupon = round(max(0, min($descuentoCupon, $subtotal - $descuento)), 2);
        $base = max(0, $subtotal - $descuento - $descuentoCupon) + $envio;
        $iva = $this->iva($base, $aplicaIva);
        $total = round($base + $iva, 2);
        $totalContrato = round(max(0, $total - $valorACuenta), 2);

        return [
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'descuento_cupon' => $descuentoCupon,
            'base' => round($base, 2),
            'iva' => $iva,
            'total' => $total,
            'total_contrato' => $totalContrato,
        ];
    }

    /**
     * Reparte el total del contrato en un plan de pagos mensuales.
     *
     * Respeta los pagos con `bloqueado = true` (conserva su monto/porcentaje) y
     * distribuye el residuo equitativamente entre los no bloqueados, ajustando el
     * centavo sobrante en el último. Si no se pasan pagos previos, genera
     * `numMeses + 1` pagos ("Pago inicial", "Primer pago", ...) partiendo de hoy.
     *
     * @param  array<int, array{nombre?: string, fecha?: string, porcentaje?: float, monto?: float, bloqueado?: bool}>  $pagosPrevios
     * @return array<int, array{nombre: string, fecha: string, porcentaje: float, monto: float, bloqueado: bool, orden: int}>
     */
    public function planPagos(float $totalContrato, int $numMeses, array $pagosPrevios = [], ?Carbon $inicio = null): array
    {
        $inicio = $inicio ?? Carbon::today();
        $totalContrato = round($totalContrato, 2);

        // Número de pagos: inicial + numMeses, pero si el usuario agregó
        // más parcialidades a mano ("Agregar pago") que las que da el plazo
        // en meses, se respetan: no se truncan las que ya capturó.
        $numPagos = max(1, $numMeses + 1, count($pagosPrevios));

        // Construye la lista base (nombre/fecha/bloqueado) desde previos o generada.
        $pagos = [];
        for ($i = 0; $i < $numPagos; $i++) {
            $previo = $pagosPrevios[$i] ?? null;
            $pagos[] = [
                'nombre' => $previo['nombre'] ?? $this->nombrePago($i),
                'fecha' => $previo['fecha'] ?? $inicio->copy()->addMonths($i)->toDateString(),
                'bloqueado' => (bool) ($previo['bloqueado'] ?? false),
                'monto' => (float) ($previo['monto'] ?? 0),
                'porcentaje' => (float) ($previo['porcentaje'] ?? 0),
                'orden' => $i,
            ];
        }

        // Suma de los bloqueados y cuántos quedan libres.
        $sumaBloqueada = 0.0;
        $libres = [];
        foreach ($pagos as $idx => $p) {
            if ($p['bloqueado']) {
                $sumaBloqueada += round($p['monto'], 2);
            } else {
                $libres[] = $idx;
            }
        }

        $restante = round($totalContrato - $sumaBloqueada, 2);
        $countLibres = count($libres);

        if ($countLibres > 0) {
            // Cuotas en pesos cerrados; el último libre se lleva lo que sobra.
            $cuota = floor($restante / $countLibres);
            foreach ($libres as $pos => $idx) {
                // El último libre absorbe el residuo por redondeo.
                if ($pos === $countLibres - 1) {
                    $asignado = round($restante - $cuota * ($countLibres - 1), 2);
                } else {
                    $asignado = $cuota;
                }
                $pagos[$idx]['monto'] = max(0, $asignado);
            }
        }

        // Pesos cerrados: los centavos de todos (también de los que se
        // capturaron a mano o por porcentaje) se juntan en el último pago.
        $cerrados = self::cerrarCentavos(array_column($pagos, 'monto'));
        foreach ($cerrados as $idx => $monto) {
            $pagos[$idx]['monto'] = $monto;
        }

        // Recalcula porcentajes reales sobre el total del contrato.
        foreach ($pagos as $idx => $p) {
            $pagos[$idx]['porcentaje'] = $totalContrato > 0
                ? round($p['monto'] / $totalContrato * 100, 2)
                : 0.0;
        }

        return $pagos;
    }

    /**
     * Deja todos los montos en pesos cerrados y junta los centavos en el
     * último, sin cambiar la suma: [2000.77, 2000.77, 2000.76] →
     * [2000, 2000, 2002.30]. Se hace en centavos enteros para no arrastrar
     * errores de punto flotante.
     *
     * @param  array<int, float>  $montos
     * @return array<int, float>
     */
    public static function cerrarCentavos(array $montos): array
    {
        $llaves = array_keys($montos);
        if (count($llaves) < 2) {
            return array_map(fn ($m) => round((float) $m, 2), $montos);
        }

        $ultimo = end($llaves);
        $sobrante = 0;
        $resultado = [];
        foreach ($montos as $k => $m) {
            $centavos = (int) round((float) $m * 100);
            if ($k === $ultimo) {
                continue;
            }
            $cerrado = intdiv(max(0, $centavos), 100) * 100;
            $sobrante += $centavos - $cerrado;
            $resultado[$k] = (float) ($cerrado / 100);
        }
        $resultado[$ultimo] = (float) (((int) round((float) $montos[$ultimo] * 100) + $sobrante) / 100);

        // Mismo orden de llaves que se recibió.
        return array_replace(array_fill_keys($llaves, 0.0), $resultado);
    }

    private function nombrePago(int $i): string
    {
        if ($i === 0) {
            return 'Pago inicial';
        }

        $ordinales = [
            1 => 'Primer', 2 => 'Segundo', 3 => 'Tercer', 4 => 'Cuarto', 5 => 'Quinto',
            6 => 'Sexto', 7 => 'Séptimo', 8 => 'Octavo', 9 => 'Noveno', 10 => 'Décimo',
            11 => 'Décimo primer', 12 => 'Décimo segundo',
        ];

        $ordinal = $ordinales[$i] ?? "{$i}º";

        return "{$ordinal} pago";
    }
}
