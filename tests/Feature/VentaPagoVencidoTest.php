<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el bug reportado: una parcialidad con fecha de hoy se marcaba como
 * "vencida" desde el primer instante, porque isPast() compara contra la
 * hora exacta, no contra el día completo.
 */
class VentaPagoVencidoTest extends TestCase
{
    use RefreshDatabase;

    private function ventaConParcialidad(string $fecha): VentaPago
    {
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'Prueba']);
        $venta = Venta::create([
            'folio' => 'VEN-VENC-'.uniqid(),
            'customer_id' => $customer->id,
            'modalidad' => 'financiamiento',
            'subtotal' => 1000,
            'total' => 1000,
            'total_contrato' => 1000,
            'num_meses' => 1,
        ]);

        return $venta->pagos()->create([
            'nombre' => 'Primer pago',
            'fecha' => $fecha,
            'monto' => 500,
            'porcentaje' => 50,
            'bloqueado' => false,
            'orden' => 0,
        ]);
    }

    public function test_una_parcialidad_que_vence_hoy_no_esta_vencida(): void
    {
        $pago = $this->ventaConParcialidad(now()->format('Y-m-d'));

        $this->assertFalse($pago->vencido());
        $this->assertSame('pendiente', $pago->estado());
    }

    public function test_una_parcialidad_de_ayer_sin_cobrar_si_esta_vencida(): void
    {
        $pago = $this->ventaConParcialidad(now()->subDay()->format('Y-m-d'));

        $this->assertTrue($pago->vencido());
        $this->assertSame('vencido', $pago->estado());
    }

    public function test_un_abono_reduce_el_saldo_pendiente_de_la_parcialidad(): void
    {
        $pago = $this->ventaConParcialidad(now()->format('Y-m-d'));

        $pago->cobros()->create([
            'venta_id' => $pago->venta_id,
            'folio' => 'COB-TEST-1',
            'fecha' => now(),
            'monto' => 200,
            'metodo' => 'efectivo',
            'registrado_por' => User::factory()->create()->id,
        ]);

        $pago->refresh();

        $this->assertEqualsWithDelta(200.0, $pago->cobrado(), 0.01);
        $this->assertEqualsWithDelta(300.0, $pago->saldo(), 0.01, 'El saldo debe bajar con el abono, no seguir en el monto original.');
        $this->assertSame('parcial', $pago->estado());
    }
}
