<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\Paquete;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** El equipo que el cliente entrega a cuenta: tipo, subtipo, marca, modelo y serie. */
class EquiposACuentaTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private User $admin;

    private array $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->usuarioApi([], true);
        $cliente = Customer::create(['nombre' => 'Hospital', 'apellido' => 'A Cuenta']);
        $kit = Paquete::create(['nombre' => 'Kit', 'precio' => 50000, 'activo' => true]);
        $this->base = [
            'customer_id' => $cliente->id, 'modalidad' => 'financiamiento', 'garantia_meses' => 6, 'num_meses' => 2,
            'valor_a_cuenta' => 15000,
            'items' => [['tipo_item' => 'paquete', 'paquete_id' => $kit->id, 'nombre' => 'Kit', 'cantidad' => 1, 'precio_unitario' => 50000]],
            'pagos' => [
                ['nombre' => 'Primer pago', 'fecha' => now()->addMonth()->toDateString(), 'porcentaje' => 50, 'monto' => 17500],
                ['nombre' => 'Segundo pago', 'fecha' => now()->addMonths(2)->toDateString(), 'porcentaje' => 50, 'monto' => 17500],
            ],
            'equipos_a_cuenta' => [
                ['tipo' => ' laparoscopia ', 'subtipo' => 'torre', 'marca' => 'Storz', 'modelo' => 'Image1 S', 'no_serie' => 'ab-123'],
                ['tipo' => '', 'subtipo' => '', 'marca' => '', 'modelo' => '', 'no_serie' => ''],   // renglón vacío: se ignora
            ],
        ];
    }

    public function test_la_cotizacion_guarda_y_muestra_el_equipo_a_cuenta(): void
    {
        $this->actingAs($this->admin)->post(route('commercial.cotizaciones.store'), $this->base)->assertSessionHasNoErrors();
        $cot = Cotizacion::latest('id')->firstOrFail();

        $this->assertEquals([['tipo' => 'LAPAROSCOPIA', 'subtipo' => 'TORRE', 'marca' => 'STORZ', 'modelo' => 'IMAGE1 S', 'no_serie' => 'AB-123']], $cot->equipos_a_cuenta);

        $this->actingAs($this->admin)->get(route('commercial.cotizaciones.show', $cot))->assertOk()
            ->assertSee('Equipo a cuenta')->assertSee('LAPAROSCOPIA · TORRE')->assertSee('STORZ IMAGE1 S')->assertSee('Serie AB-123');

        // El PDF (su vista) trae la tabla con tipo, subtipo, marca y modelo.
        $html = view('structure.commercial_management.cotizaciones.pdf', ['cotizacion' => $cot->load(['customer', 'seller', 'items', 'pagos', 'fichas'])])->render();
        foreach (['Equipo a cuenta', 'Subtipo', 'LAPAROSCOPIA', 'TORRE', 'STORZ', 'IMAGE1 S', 'AB-123'] as $t) {
            $this->assertStringContainsString($t, $html);
        }

        // Al editar viene prellenado.
        $this->actingAs($this->admin)->get(route('commercial.cotizaciones.edit', $cot))->assertOk()->assertSee('IMAGE1 S');
    }

    public function test_pasa_de_la_cotizacion_a_la_venta_y_sale_en_el_contrato(): void
    {
        $this->actingAs($this->admin)->post(route('commercial.cotizaciones.store'), $this->base);
        $cot = Cotizacion::latest('id')->firstOrFail();

        // La venta desde la cotización llega con el equipo a cuenta.
        $this->actingAs($this->admin)->get(route('commercial.ventas.create', ['cotizacion' => $cot->id]))->assertOk()->assertSee('IMAGE1 S');

        $this->actingAs($this->admin)->post(route('commercial.ventas.store', ['cotizacion' => $cot->id]), $this->base)->assertSessionHasNoErrors();
        $venta = Venta::latest('id')->firstOrFail();
        $this->assertSame('STORZ', $venta->equipos_a_cuenta[0]['marca']);

        $this->actingAs($this->admin)->get(route('commercial.ventas.show', $venta))->assertOk()->assertSee('LAPAROSCOPIA · TORRE');
        $contrato = view('structure.commercial_management.ventas.contrato', ['venta' => $venta->load(['customer', 'seller', 'items', 'pagos'])])->render();
        $contrato = preg_replace('/\s+/u', ' ', $contrato);
        $this->assertStringContainsString('correspondientes al equipo entregado a cuenta (LAPAROSCOPIA · TORRE · STORZ IMAGE1 S · Serie AB-123)', $contrato);
    }

    public function test_se_puede_quitar_al_editar(): void
    {
        $this->actingAs($this->admin)->post(route('commercial.cotizaciones.store'), $this->base);
        $cot = Cotizacion::latest('id')->firstOrFail();

        $this->actingAs($this->admin)->put(route('commercial.cotizaciones.update', $cot), ['equipos_a_cuenta' => ''] + $this->base)->assertSessionHasNoErrors();
        $this->assertNull($cot->fresh()->equipos_a_cuenta);
        $this->actingAs($this->admin)->get(route('commercial.cotizaciones.show', $cot))->assertOk()->assertDontSee('Equipo a cuenta</div>', false);
    }
}
