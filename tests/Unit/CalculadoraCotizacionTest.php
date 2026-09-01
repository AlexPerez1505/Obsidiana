<?php

namespace Tests\Unit;

use App\Services\CalculadoraCotizacion;
use Tests\TestCase;

/**
 * Cubre el bug reportado: al editar una cotización/venta y darle
 * "Agregar pago", la parcialidad nueva se perdía porque el plan se
 * reconstruía usando solo num_meses, sin importar cuántas filas se
 * habían capturado.
 */
class CalculadoraCotizacionTest extends TestCase
{
    public function test_conserva_pagos_agregados_a_mano_que_superan_los_meses_del_plazo(): void
    {
        $calc = new CalculadoraCotizacion();

        // 2 meses -> normalmente 3 pagos (inicial + 2), pero el usuario
        // agregó una parcialidad extra a mano con el botón "Agregar pago".
        $previos = [
            ['nombre' => 'Pago inicial', 'monto' => 1000, 'bloqueado' => true],
            ['nombre' => 'Primer pago', 'monto' => 0, 'bloqueado' => false],
            ['nombre' => 'Segundo pago', 'monto' => 0, 'bloqueado' => false],
            ['nombre' => 'Tercer pago', 'monto' => 0, 'bloqueado' => false],
        ];

        $plan = $calc->planPagos(4000, 2, $previos);

        $this->assertCount(4, $plan, 'No debe truncar el pago agregado a mano.');
        $this->assertSame('Tercer pago', $plan[3]['nombre']);

        // El monto libre (3000 restantes tras el inicial bloqueado) se
        // reparte entre las 3 parcialidades no bloqueadas.
        $this->assertEqualsWithDelta(1000.0, $plan[1]['monto'], 0.01);
        $this->assertEqualsWithDelta(1000.0, $plan[2]['monto'], 0.01);
        $this->assertEqualsWithDelta(1000.0, $plan[3]['monto'], 0.01);
    }

    public function test_sin_pagos_extra_sigue_generando_inicial_mas_meses(): void
    {
        $calc = new CalculadoraCotizacion();

        $plan = $calc->planPagos(3000, 2);

        $this->assertCount(3, $plan);
        $this->assertSame('Pago inicial', $plan[0]['nombre']);
        $this->assertSame('Primer pago', $plan[1]['nombre']);
        $this->assertSame('Segundo pago', $plan[2]['nombre']);
    }
}
