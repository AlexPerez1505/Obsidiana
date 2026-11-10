<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Venta que salió sin IVA: agregar el 16 % solo a lo que falta de las
 * mensualidades elegidas, sin tocar lo cobrado, y poder quitarlo.
 */
class IvaMensualidadesTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private User $admin;

    private Venta $venta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->usuarioApi([], true);
        $cliente = Customer::create(['nombre' => 'Clínica', 'apellido' => 'Sin IVA']);
        $this->venta = Venta::forceCreate([
            'folio' => 'VEN-T-'.uniqid(), 'customer_id' => $cliente->id, 'seller_id' => $this->admin->id, 'modalidad' => 'financiamiento',
            'aplica_iva' => false, 'subtotal' => 3000, 'descuento_valor' => 0, 'descuento_monto' => 0, 'envio' => 0,
            'iva_monto' => 0, 'valor_a_cuenta' => 0, 'total' => 3000, 'total_contrato' => 3000,
            'num_meses' => 3, 'garantia_meses' => 6, 'estado' => 'borrador',
        ]);
        foreach (['Primer pago', 'Segundo pago', 'Tercer pago'] as $i => $n) {
            $this->venta->pagos()->create(['nombre' => $n, 'fecha' => now()->addMonths($i), 'monto' => 1000, 'orden' => $i, 'porcentaje' => 0]);
        }
        // Se cobra el primero completo y la mitad del segundo.
        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.store', $this->venta),
            ['monto' => 1500, 'fecha' => now()->toDateString(), 'metodo' => 'efectivo'])->assertSessionHasNoErrors();
    }

    private function pagos()
    {
        return $this->venta->pagos()->orderBy('orden')->get();
    }

    public function test_agrega_iva_solo_a_lo_que_falta_de_las_elegidas(): void
    {
        [$p1, $p2, $p3] = $this->pagos();

        // El primero ya está pagado: no es candidato.
        $this->actingAs($this->admin)->get(route('commercial.ventas.cobros.index', $this->venta))->assertOk()
            ->assertSee('Agregar IVA')->assertDontSee('value="'.$p1->id.'" data-iva', false);

        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.iva.agregar', $this->venta), ['pagos' => [$p2->id, $p3->id]])
            ->assertSessionHasNoErrors();

        [$p1, $p2, $p3] = $this->pagos();
        $this->assertEquals(1000, (float) $p1->monto);
        $this->assertEquals(1080, (float) $p2->monto);     // 16 % de los 500 que faltaban
        $this->assertEquals(80, (float) $p2->iva_monto);
        $this->assertEquals(1160, (float) $p3->monto);

        $v = $this->venta->fresh();
        $this->assertEquals(3240, (float) $v->total);
        $this->assertEquals(3240, (float) $v->total_contrato);
        $this->assertEquals(240, (float) $v->iva_monto);
        $this->assertEquals(1740, $v->saldo());
        $this->assertFalse((bool) $v->aplica_iva);

        $this->actingAs($this->admin)->get(route('commercial.ventas.cobros.index', $this->venta))->assertOk()
            ->assertSee('+IVA $80.00')->assertSee('Quitar IVA')->assertDontSee('El plan suma');
        $this->actingAs($this->admin)->get(route('commercial.ventas.show', $this->venta))->assertOk()
            ->assertSee('IVA agregado a mensualidades');
    }

    public function test_no_se_agrega_dos_veces_ni_a_ventas_con_iva(): void
    {
        [, $p2] = $this->pagos();
        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.iva.agregar', $this->venta), ['pagos' => [$p2->id]]);
        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.iva.agregar', $this->venta), ['pagos' => [$p2->id]])
            ->assertSessionHasErrors('iva');
        $this->assertEquals(80, (float) $p2->fresh()->iva_monto);

        $this->venta->forceFill(['aplica_iva' => true])->save();
        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.iva.agregar', $this->venta), ['pagos' => [$this->pagos()[2]->id]])
            ->assertSessionHasErrors('iva');
    }

    public function test_quitar_iva_regresa_todo_si_no_se_ha_cobrado(): void
    {
        [, $p2, $p3] = $this->pagos();
        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.iva.agregar', $this->venta), ['pagos' => [$p2->id, $p3->id]]);
        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.iva.quitar', $this->venta))->assertSessionHasNoErrors();

        [, $p2, $p3] = $this->pagos();
        $this->assertEquals(1000, (float) $p2->monto);
        $this->assertEquals(0, (float) $p2->iva_monto);
        $this->assertEquals(1000, (float) $p3->monto);
        $this->assertEquals(3000, (float) $this->venta->fresh()->total);
        $this->assertEquals(0, (float) $this->venta->fresh()->iva_monto);
    }

    public function test_no_se_quita_el_iva_que_ya_se_cobro(): void
    {
        [, $p2, $p3] = $this->pagos();
        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.iva.agregar', $this->venta), ['pagos' => [$p2->id, $p3->id]]);
        // Se cobra el resto del segundo con su IVA (580).
        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.store', $this->venta),
            ['monto' => 580, 'fecha' => now()->toDateString(), 'metodo' => 'efectivo', 'venta_pago_id' => $p2->id])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.iva.quitar', $this->venta))->assertSessionHasNoErrors();

        [, $p2, $p3] = $this->pagos();
        $this->assertEquals(80, (float) $p2->iva_monto);     // ya cobrado: se queda
        $this->assertEquals(0, (float) $p3->iva_monto);
        $this->assertEquals(3080, (float) $this->venta->fresh()->total);
    }

    public function test_con_iva_agregado_se_usan_las_cuentas_de_con_iva(): void
    {
        config([
            'medibuy.bancos.con_iva' => [['beneficiario' => 'EMPRESA CON IVA SA', 'nombre' => 'BBVA', 'cuenta' => '1111111111', 'clabe' => '', 'tarjeta' => '', 'sucursal' => '']],
            'medibuy.bancos.sin_iva' => [['beneficiario' => 'PERSONA SIN IVA', 'nombre' => 'SANTANDER', 'cuenta' => '2222222222', 'clabe' => '', 'tarjeta' => '', 'sucursal' => '']],
        ]);
        $render = fn (string $vista) => view("structure.commercial_management.ventas.{$vista}",
            ['venta' => $this->venta->fresh()->load(['customer', 'seller', 'items', 'pagos', 'fichas'])])->render();

        // Antes: sin IVA → cuentas de sin IVA.
        $this->assertFalse($this->venta->fresh()->cobraConIva());
        $this->assertStringContainsString('2222222222', $render('pdf'));

        [, $p2, $p3] = $this->pagos();
        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.iva.agregar', $this->venta), ['pagos' => [$p2->id, $p3->id]]);

        $v = $this->venta->fresh();
        $this->assertTrue($v->cobraConIva());
        $this->assertTrue($v->ivaAgregadoDespues());
        $pdf = $render('pdf');
        $this->assertStringContainsString('1111111111', $pdf);
        $this->assertStringNotContainsString('2222222222', $pdf);
        $this->assertStringContainsString('se les agregó el IVA', $pdf);
        $this->assertStringContainsString('IVA agregado a mensualidades', $pdf);

        $contrato = $render('contrato');
        $this->assertStringContainsString('EMPRESA CON IVA SA', $contrato);
        $this->assertStringContainsString('de Impuesto al Valor Agregado agregado a las mensualidades', $contrato);

        // Al quitarlo, regresan las de sin IVA.
        $this->actingAs($this->admin)->post(route('commercial.ventas.cobros.iva.quitar', $this->venta));
        $this->assertFalse($this->venta->fresh()->cobraConIva());
        $this->assertStringContainsString('2222222222', $render('pdf'));
    }

    public function test_solo_quien_ajusta_cobranza(): void
    {
        $vendedor = $this->usuarioApi(['ventas.ver', 'cobranza.ver']);
        $this->actingAs($vendedor)->post(route('commercial.ventas.cobros.iva.agregar', $this->venta), ['pagos' => [$this->pagos()[2]->id]])
            ->assertForbidden();
    }
}
