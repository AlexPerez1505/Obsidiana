<?php

namespace Tests\Feature\Api\V1\Ventas;

use App\Models\Cobro;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Ventas\Concerns\DatosDeVentas;
use Tests\TestCase;

/**
 * Las pantallas de ventas, cobranza, comisiones, facturas y venta rápida
 * pasaron a usar los mismos servicios que la API. Esto confirma que en la
 * web todo sigue respondiendo igual: mismos redirects, mismos mensajes y
 * los errores en el mismo campo.
 */
class WebSigueIgualTest extends TestCase
{
    use DatosDeVentas, RefreshDatabase, UsaApi;

    private function admin(): User
    {
        return $this->usuarioApi([], admin: true);
    }

    public function test_cobros_en_la_web(): void
    {
        $admin = $this->admin();
        $venta = $this->ventaFinanciada($admin);
        $pantalla = route('commercial.ventas.cobros.index', $venta);

        $this->actingAs($admin)->from($pantalla)->post(route('commercial.ventas.cobros.store', $venta), [
            'fecha' => now()->toDateString(), 'monto' => 1500, 'metodo' => 'efectivo',
        ])->assertRedirect($pantalla)->assertSessionHas('status', 'Cobro registrado correctamente. Se aplicó a 2 pagos: Pago inicial $1,000.00 (liquidado), Primer pago $500.00 (restan $500.00).');

        $this->actingAs($admin)->from($pantalla)->post(route('commercial.ventas.cobros.store', $venta), [
            'fecha' => now()->toDateString(), 'monto' => 99999, 'metodo' => 'efectivo',
        ])->assertRedirect($pantalla)->assertSessionHasErrors(['monto' => 'El monto supera el saldo pendiente ($1,500.00).']);

        $this->actingAs($admin)->get($pantalla)->assertOk();

        $primer = $venta->pagos()->get()[1];
        $this->actingAs($admin)->from($pantalla)
            ->put(route('commercial.ventas.cobros.parcialidad.actualizar', [$venta, $primer]), ['fecha' => now()->toDateString(), 'monto' => 10])
            ->assertRedirect($pantalla)->assertSessionHasErrors(['monto' => 'No puede quedar por debajo de lo ya cobrado ($500.00).']);

        $this->actingAs($admin)->from($pantalla)
            ->delete(route('commercial.ventas.cobros.parcialidad.eliminar', [$venta, $primer]))
            ->assertSessionHasErrors(['plan' => 'Esa parcialidad ya tiene cobros; cancela primero sus cobros.']);

        $this->actingAs($admin)->from($pantalla)
            ->post(route('commercial.ventas.cobros.absorber-excedente', $venta))
            ->assertSessionHas('status', 'No hay excedente por repartir.');

        $cobro = Cobro::first();
        $this->actingAs($admin)->get(route('commercial.ventas.cobros.recibo', [$venta, $cobro]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($admin)->from($pantalla)->delete(route('commercial.ventas.cobros.destroy', [$venta, $cobro]))
            ->assertRedirect($pantalla)->assertSessionHas('status', 'Cobro cancelado.');

        $venta->update(['estado' => 'cancelada']);
        $this->actingAs($admin)->from($pantalla)->post(route('commercial.ventas.cobros.store', $venta), [])
            ->assertSessionHasErrors(['monto' => 'Esta venta está cancelada: no se le pueden registrar cobros.']);
    }

    public function test_ventas_en_la_web(): void
    {
        $admin = $this->admin();
        $venta = $this->ventaFinanciada($admin);
        Cobro::create(['venta_id' => $venta->id, 'folio' => Cobro::siguienteFolio(), 'fecha' => now()->toDateString(), 'monto' => 2000, 'metodo' => 'efectivo']);
        $detalle = route('commercial.ventas.show', $venta);

        $this->actingAs($admin)->from(route('commercial.ventas.edit', $venta))->put(route('commercial.ventas.update', $venta), [
            'customer_id' => $venta->customer_id, 'modalidad' => 'financiamiento', 'items' => [$this->renglonEquipo(1000)],
        ])->assertSessionHasErrors('items')->assertRedirect(route('commercial.ventas.edit', $venta));

        $this->actingAs($admin)->delete(route('commercial.ventas.destroy', $venta))
            ->assertRedirect($detalle)
            ->assertSessionHasErrors(['venta' => 'Esta venta ya tiene cobros registrados: no se puede eliminar, solo cancelar.']);

        $this->actingAs($admin)->from($detalle)->post(route('commercial.ventas.cancelar', $venta), ['motivo' => 'x', 'password' => 'mala'])
            ->assertRedirect($detalle)->assertSessionHasErrors(['password' => 'PIN o contraseña incorrecta.']);

        $this->actingAs($admin)->from($detalle)->post(route('commercial.ventas.cancelar', $venta), ['motivo' => 'Cambio de equipo', 'password' => 'password'])
            ->assertRedirect($detalle)
            ->assertSessionHas('status', "Venta {$venta->folio} cancelada. El equipo volvió al inventario. El cliente tenía pagados \$2,000.00; los cobros se conservan como historial y queda pendiente su devolución.");

        $this->actingAs($admin)->put(route('commercial.ventas.update', $venta), [])
            ->assertRedirect($detalle)->assertSessionHasErrors(['venta' => 'Una venta cancelada no se edita.']);

        $this->actingAs($admin)->get(route('commercial.ventas.pdf', $venta))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($admin)->get(route('commercial.cobranza.index'))->assertOk();
    }

    public function test_comisiones_facturas_y_venta_rapida_en_la_web(): void
    {
        $admin = $this->admin();
        $asesor = $this->usuarioApi();

        $this->actingAs($admin)->get(route('commercial.comisiones.index', ['periodo' => now()->format('Y-m'), 'base' => 'vendido']))->assertOk();

        $this->actingAs($admin)->post(route('commercial.comisiones.pagar'), [
            'user_id' => $asesor->id, 'periodo' => '2026-09', 'monto' => 100, 'fecha' => now()->toDateString(), 'base' => 'cobrado',
        ])->assertRedirect(route('commercial.comisiones.index', ['periodo' => '2026-09', 'base' => 'cobrado']))
            ->assertSessionHas('status', "Pago de \$100.00 registrado a {$asesor->name}.");

        $this->actingAs($admin)->from('/x')->put(route('commercial.comisiones.porcentaje', $asesor), ['porcentaje' => 4])
            ->assertSessionHas('status', "Porcentaje de {$asesor->name} actualizado.");
        $this->assertSame(4.0, (float) $asesor->refresh()->porcentaje_comision);

        // Formulario de factura con los catálogos de siempre.
        $venta = $this->ventaDe($admin, ['customer_id' => $this->cliente()->id, 'items' => [$this->renglonEquipo(100)]]);
        $this->actingAs($admin)->get(route('commercial.facturas.create', ['venta' => $venta->id]))
            ->assertOk()
            ->assertSee('<option value="G01">G01 · Adquisición de mercancías</option>', false)
            ->assertSee('<option value="28">28 · Tarjeta de débito</option>', false)
            ->assertSee('<option value="PPD">PPD · Pago en parcialidades o diferido</option>', false);

        $this->actingAs($admin)->post(route('commercial.facturas.store'), ['venta_id' => $venta->id, 'customer_id' => $venta->customer_id])
            ->assertRedirect(route('commercial.facturas.show', Factura::first()));
        $this->assertSame('facturada', $venta->refresh()->estado);

        // Venta rápida: mismas respuestas JSON para el celular.
        $producto = $this->productoConUnidades(1, precio: 300);
        $pieza = $producto->seriales()->first();

        $this->actingAs($admin)->postJson(route('commercial.ventas.rapida.pieza'), ['codigo' => $pieza->codigo])
            ->assertOk()->assertJsonPath('encontrado', true)->assertJsonPath('precio', 300);
        $this->actingAs($admin)->postJson(route('commercial.ventas.rapida.pieza'), ['codigo' => 'NADA'])
            ->assertOk()->assertJsonPath('encontrado', false);

        $this->actingAs($admin)->postJson(route('commercial.ventas.rapida.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 5, 'precio_unitario' => 300]], 'metodo' => 'efectivo',
        ])->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('mensaje', 'Solo hay 1 disponible(s) de '.$producto->nombreVisible().' y se quieren vender 5.');

        $this->actingAs($admin)->postJson(route('commercial.ventas.rapida.store'), [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 300]], 'metodo' => 'efectivo',
        ])->assertOk()->assertJsonPath('ok', true)->assertJsonPath('cliente', 'Público en general')->assertJsonPath('total', 300);

        $this->actingAs($admin)->get(route('commercial.ventas.rapida.index'))->assertOk();
    }
}
