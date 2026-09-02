<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Venta;
use App\Services\CalendarioPagos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre lo reportado: si el cliente abona más de lo que le tocaba a una
 * parcialidad, o da un abono suelto (sin elegir parcialidad), ese dinero
 * debe quedar REASIGNADO a lo que sigue pendiente — nunca "borrado" del
 * monto de otra parcialidad, porque eso la deja en $0 sin haber recibido
 * en realidad ningún pago (bug reportado: "los marca en cero").
 */
class AbsorberExcedenteTest extends TestCase
{
    use RefreshDatabase;

    private function ventaConTresPagos(): Venta
    {
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'Prueba']);
        $venta = Venta::create([
            'folio' => 'VEN-EXC-'.uniqid(),
            'customer_id' => $customer->id,
            'modalidad' => 'financiamiento',
            'subtotal' => 3000,
            'total' => 3000,
            'total_contrato' => 3000,
            'num_meses' => 2,
        ]);

        $venta->pagos()->create(['nombre' => 'Pago inicial', 'fecha' => now(), 'monto' => 1000, 'porcentaje' => 33.33, 'orden' => 0]);
        $venta->pagos()->create(['nombre' => 'Primer pago', 'fecha' => now()->addMonth(), 'monto' => 1000, 'porcentaje' => 33.33, 'orden' => 1]);
        $venta->pagos()->create(['nombre' => 'Segundo pago', 'fecha' => now()->addMonths(2), 'monto' => 1000, 'porcentaje' => 33.34, 'orden' => 2]);

        return $venta;
    }

    public function test_un_abono_mayor_a_la_parcialidad_deja_un_excedente_detectable(): void
    {
        $venta = $this->ventaConTresPagos();
        $inicial = $venta->pagos()->orderBy('orden')->first();

        $inicial->cobros()->create([
            'venta_id' => $venta->id,
            'folio' => 'COB-EXC-1',
            'fecha' => now(),
            'monto' => 1500, // le tocaban 1000, abonó 500 de más
            'metodo' => 'efectivo',
            'registrado_por' => User::factory()->create()->id,
        ]);

        $venta->refresh()->load('pagos.cobros');

        $excedente = app(CalendarioPagos::class)->excedentePendiente($venta);
        $this->assertEqualsWithDelta(500.0, $excedente, 0.01);

        // El saldo total de la venta ya es correcto en agregado...
        $this->assertEqualsWithDelta(1500.0, $venta->saldo(), 0.01);

        // ...pero el "Primer pago" todavía pide su monto completo, sin
        // enterarse de que ya le tocan 500 del abono de más.
        $primer = $venta->pagos()->orderBy('orden')->skip(1)->first();
        $this->assertEqualsWithDelta(1000.0, (float) $primer->monto, 0.01);
        $this->assertEqualsWithDelta(0.0, $primer->cobrado(), 0.01);
    }

    public function test_absorber_excedente_reasigna_el_sobrante_a_la_siguiente_parcialidad_sin_tocar_montos(): void
    {
        $venta = $this->ventaConTresPagos();
        $inicial = $venta->pagos()->orderBy('orden')->first();

        $inicial->cobros()->create([
            'venta_id' => $venta->id,
            'folio' => 'COB-EXC-2',
            'fecha' => now(),
            'monto' => 1500,
            'metodo' => 'efectivo',
            'registrado_por' => User::factory()->create()->id,
        ]);

        $r = app(CalendarioPagos::class)->absorberExcedente($venta->refresh());

        $this->assertEqualsWithDelta(500.0, $r['excedente'], 0.01);
        $this->assertSame(1, $r['ajustadas']);

        $venta->refresh()->load('pagos.cobros');
        $pagos = $venta->pagos()->orderBy('orden')->get();

        // Los montos originales NUNCA cambian: lo que cambia es a cuál
        // parcialidad queda ligado cada cobro.
        $this->assertEqualsWithDelta(1000.0, (float) $pagos[0]->monto, 0.01);
        $this->assertEqualsWithDelta(1000.0, (float) $pagos[1]->monto, 0.01);
        $this->assertEqualsWithDelta(1000.0, (float) $pagos[2]->monto, 0.01);

        // El "Pago inicial" queda exactamente en lo que le tocaba...
        $this->assertEqualsWithDelta(1000.0, $pagos[0]->cobrado(), 0.01);
        $this->assertSame('pagado', $pagos[0]->estado());

        // ...y el excedente de 500 quedó ligado al "Primer pago", que por
        // eso queda con saldo pendiente de 500, NO en $0.
        $this->assertEqualsWithDelta(500.0, $pagos[1]->cobrado(), 0.01);
        $this->assertEqualsWithDelta(500.0, $pagos[1]->saldo(), 0.01);
        $this->assertSame('parcial', $pagos[1]->estado());

        $this->assertEqualsWithDelta(1500.0, $venta->saldo(), 0.01, 'El saldo total no cambia, solo cómo se reparte.');
    }

    public function test_un_abono_suelto_sin_parcialidad_se_reasigna_a_la_primera_pendiente(): void
    {
        $venta = $this->ventaConTresPagos();

        // Abono suelto: no se liga a ninguna parcialidad (venta_pago_id null).
        $venta->cobros()->create([
            'folio' => 'COB-SUELTO-1',
            'fecha' => now(),
            'monto' => 400,
            'metodo' => 'efectivo',
            'registrado_por' => User::factory()->create()->id,
        ]);

        $venta->refresh()->load(['pagos.cobros', 'cobros']);

        $excedente = app(CalendarioPagos::class)->excedentePendiente($venta);
        $this->assertEqualsWithDelta(400.0, $excedente, 0.01);

        app(CalendarioPagos::class)->absorberExcedente($venta);

        $venta->refresh()->load(['pagos.cobros', 'cobros']);
        $pagos = $venta->pagos()->orderBy('orden')->get();

        // El monto original de cada parcialidad no cambia...
        $this->assertEqualsWithDelta(1000.0, (float) $pagos[0]->monto, 0.01);

        // ...pero el "Pago inicial" (la más próxima) ya quedó con $400
        // abonados de verdad, no con su monto reducido a $600.
        $this->assertEqualsWithDelta(400.0, $pagos[0]->cobrado(), 0.01);
        $this->assertEqualsWithDelta(600.0, $pagos[0]->saldo(), 0.01);
        $this->assertSame('parcial', $pagos[0]->estado());

        $this->assertEqualsWithDelta(2600.0, $venta->saldo(), 0.01);
    }

    public function test_un_abono_suelto_que_cubre_una_parcialidad_completa_la_marca_pagada_no_en_cero(): void
    {
        // Este es el bug reportado: pagar exactamente lo que la parcialidad
        // pide, mediante un abono suelto, la dejaba en monto=0 en vez de
        // marcarla como pagada.
        $venta = $this->ventaConTresPagos();
        $inicial = $venta->pagos()->orderBy('orden')->first();

        // Primero se abona de más al inicial (exceso de 500)...
        $inicial->cobros()->create([
            'venta_id' => $venta->id, 'folio' => 'COB-A', 'fecha' => now(),
            'monto' => 1500, 'metodo' => 'efectivo', 'registrado_por' => User::factory()->create()->id,
        ]);

        // ...y luego se registra un abono SUELTO por exactamente lo que
        // ahora le falta al "Primer pago" ($500).
        $venta->cobros()->create([
            'folio' => 'COB-B', 'fecha' => now(),
            'monto' => 500, 'metodo' => 'efectivo', 'registrado_por' => User::factory()->create()->id,
        ]);

        app(CalendarioPagos::class)->absorberExcedente($venta->refresh());

        $venta->refresh()->load(['pagos.cobros', 'cobros']);
        $pagos = $venta->pagos()->orderBy('orden')->get();

        $this->assertEqualsWithDelta(1000.0, (float) $pagos[1]->monto, 0.01, 'El monto NUNCA debe quedar en 0.');
        $this->assertEqualsWithDelta(1000.0, $pagos[1]->cobrado(), 0.01, 'Debe reflejar que sí se pagó completo.');
        $this->assertSame('pagado', $pagos[1]->estado());

        // El tercer pago sigue intacto, sin que nada lo haya tocado.
        $this->assertEqualsWithDelta(1000.0, (float) $pagos[2]->monto, 0.01);
        $this->assertEqualsWithDelta(0.0, $pagos[2]->cobrado(), 0.01);
        $this->assertSame('pendiente', $pagos[2]->estado());
    }

    public function test_registrar_un_cobro_por_la_ruta_real_reasigna_el_excedente_solo(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'status' => User::STATUS_APPROVED, 'approved_at' => now()]);
        $venta = $this->ventaConTresPagos();
        $pagos = $venta->pagos()->orderBy('orden')->get();

        // Se registra el cobro por la ruta real (como lo haría el usuario
        // desde Cobranza), abonando de más a la primera parcialidad.
        $this->actingAs($user)->post(route('commercial.ventas.cobros.store', $venta), [
            'venta_pago_id' => $pagos[0]->id,
            'fecha' => now()->format('Y-m-d'),
            'monto' => 1500,
            'metodo' => 'efectivo',
        ])->assertRedirect(route('commercial.ventas.cobros.index', $venta));

        $venta->refresh()->load('pagos.cobros');
        $pagos = $venta->pagos()->orderBy('orden')->get();

        // No hizo falta darle a "Descontar de lo pendiente": ya quedó solo,
        // y ninguna parcialidad quedó en monto 0.
        $this->assertEqualsWithDelta(1000.0, (float) $pagos[0]->monto, 0.01);
        $this->assertEqualsWithDelta(1000.0, (float) $pagos[1]->monto, 0.01);
        $this->assertEqualsWithDelta(500.0, $pagos[1]->cobrado(), 0.01);
    }

    public function test_absorber_excedente_no_baja_una_parcialidad_por_debajo_de_lo_ya_cobrado(): void
    {
        $venta = $this->ventaConTresPagos();
        $pagos = $venta->pagos()->orderBy('orden')->get();

        // Se abona de más en el inicial (500 de exceso) y también algo en
        // el segundo pago (300), que no debe quedar por debajo de eso.
        $pagos[0]->cobros()->create([
            'venta_id' => $venta->id, 'folio' => 'COB-EXC-3', 'fecha' => now(),
            'monto' => 1500, 'metodo' => 'efectivo', 'registrado_por' => User::factory()->create()->id,
        ]);
        $pagos[1]->cobros()->create([
            'venta_id' => $venta->id, 'folio' => 'COB-EXC-4', 'fecha' => now(),
            'monto' => 300, 'metodo' => 'efectivo', 'registrado_por' => User::factory()->create()->id,
        ]);

        app(CalendarioPagos::class)->absorberExcedente($venta->refresh());

        $venta->refresh()->load('pagos.cobros');
        $segundo = $venta->pagos()->orderBy('orden')->skip(1)->first();

        $this->assertGreaterThanOrEqual(300.0, $segundo->cobrado(), 'No puede perder lo que ya tenía cobrado.');
        $this->assertEqualsWithDelta(1000.0, (float) $segundo->monto, 0.01, 'El monto no se toca.');
    }
}
