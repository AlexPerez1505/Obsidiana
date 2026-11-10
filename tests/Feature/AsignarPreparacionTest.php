<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OrdenSalida;
use App\Models\User;
use App\Models\Venta;
use App\Notifications\OrdenSalidaAsignada;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Elegir quién prepara el equipo vendido; a esa persona le llega el aviso
 * en la campana del sistema y por correo.
 */
class AsignarPreparacionTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private User $jefe;

    private User $almacenista;

    private OrdenSalida $orden;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutDefer();   // el aviso sale al momento en las pruebas
        $this->jefe = $this->usuarioApi(['salidas.ver', 'salidas.asignar', 'ventas.ver', 'ventas.ver_todas']);
        $this->almacenista = $this->usuarioApi(['salidas.ver', 'salidas.preparar']);

        $cliente = Customer::create(['nombre' => 'Hospital', 'apellido' => 'Salidas', 'direccion' => 'Av. Siempre Viva 742']);
        $venta = Venta::forceCreate([
            'folio' => 'VEN-T-'.uniqid(), 'customer_id' => $cliente->id, 'seller_id' => $this->jefe->id, 'modalidad' => 'contado',
            'aplica_iva' => false, 'subtotal' => 1000, 'descuento_valor' => 0, 'descuento_monto' => 0, 'envio' => 0,
            'iva_monto' => 0, 'valor_a_cuenta' => 0, 'total' => 1000, 'total_contrato' => 1000,
            'num_meses' => 0, 'garantia_meses' => 6, 'estado' => 'borrador',
        ]);
        $this->orden = OrdenSalida::create(['folio' => 'OS-T-'.uniqid(), 'venta_id' => $venta->id, 'estado' => OrdenSalida::PENDIENTE]);
        $this->orden->items()->create(['nombre' => 'GASTROSCOPIO OLYMPUS', 'cantidad' => 1, 'orden' => 0, 'requiere_emplayado' => true]);
    }

    private function asignar(User $quien, $userId)
    {
        return $this->actingAs($quien)->post(route('inventory.salidas.asignar', $this->orden), ['user_id' => $userId]);
    }

    public function test_asigna_y_avisa_por_correo_y_en_el_sistema(): void
    {
        Notification::fake();

        $this->asignar($this->jefe, $this->almacenista->id)->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->orden->refresh();
        $this->assertSame($this->almacenista->id, $this->orden->asignada_a);
        $this->assertSame($this->jefe->id, $this->orden->asignada_por);
        $this->assertNotNull($this->orden->asignada_en);

        Notification::assertSentTo($this->almacenista, OrdenSalidaAsignada::class,
            fn ($n, $canales) => in_array('mail', $canales, true) && in_array('database', $canales, true));
        Notification::assertNotSentTo($this->jefe, OrdenSalidaAsignada::class);

        // El aviso trae la orden, la venta y la liga.
        $n = new OrdenSalidaAsignada($this->orden->fresh(['venta.customer', 'items', 'asignadaPor']));
        $datos = $n->toDatabase($this->almacenista);
        $this->assertStringContainsString($this->orden->folio, $datos['titulo']);
        $this->assertSame(route('inventory.salidas.show', $this->orden), $datos['url']);

        $mail = $n->toMail($this->almacenista);
        $html = $mail->render();
        $this->assertStringContainsString('GASTROSCOPIO OLYMPUS', $html);
        $this->assertStringContainsString('Av. Siempre Viva 742', $html);
        $this->assertStringContainsString($this->orden->folio, $mail->envelope()->subject);
    }

    public function test_solo_quien_tiene_permiso_asigna(): void
    {
        $this->asignar($this->almacenista, $this->almacenista->id)->assertForbidden();
        $this->assertNull($this->orden->fresh()->asignada_a);
    }

    public function test_no_se_asigna_a_quien_no_puede_preparar_ni_a_cuentas_inactivas(): void
    {
        Notification::fake();
        $vendedor = $this->usuarioApi(['ventas.ver']);
        $baneado = $this->usuarioApi(['salidas.preparar']);
        $baneado->forceFill(['status' => 'banned'])->save();

        $this->asignar($this->jefe, $vendedor->id)->assertSessionHasErrors('user_id');
        $this->asignar($this->jefe, $baneado->id)->assertSessionHasErrors('user_id');
        $this->assertNull($this->orden->fresh()->asignada_a);
        Notification::assertNothingSent();
    }

    public function test_cambiar_avisa_al_nuevo_y_quitar_no_avisa(): void
    {
        Notification::fake();
        $otro = $this->usuarioApi(['salidas.preparar']);

        $this->asignar($this->jefe, $this->almacenista->id);
        $this->asignar($this->jefe, $this->almacenista->id); // repetir no vuelve a avisar
        $this->asignar($this->jefe, $otro->id);
        Notification::assertSentToTimes($this->almacenista, OrdenSalidaAsignada::class, 1);
        Notification::assertSentToTimes($otro, OrdenSalidaAsignada::class, 1);

        $this->asignar($this->jefe, '')->assertSessionHasNoErrors();
        $this->assertNull($this->orden->fresh()->asignada_a);
        Notification::assertSentToTimes($otro, OrdenSalidaAsignada::class, 1);
    }

    public function test_orden_entregada_ya_no_se_asigna(): void
    {
        $this->orden->update(['estado' => OrdenSalida::ENTREGADA]);
        $this->asignar($this->jefe, $this->almacenista->id)->assertSessionHasErrors('user_id');
    }

    public function test_pantallas(): void
    {
        Notification::fake();

        $this->actingAs($this->jefe)->get(route('inventory.salidas.show', $this->orden))->assertOk()
            ->assertSee('Nadie asignado todavía')->assertSee($this->almacenista->name)->assertSee('Asignar');

        $this->asignar($this->jefe, $this->almacenista->id);

        $this->actingAs($this->almacenista)->get(route('inventory.salidas.show', $this->orden))->assertOk()
            ->assertSee($this->almacenista->name)->assertDontSee('— Sin asignar —');
        $this->actingAs($this->almacenista)->get(route('inventory.salidas.index', ['mias' => 1]))->assertOk()
            ->assertSee('Asignadas a mí')->assertSee($this->orden->folio);
        $this->actingAs($this->jefe)->get(route('inventory.salidas.index', ['mias' => 1]))->assertOk()
            ->assertDontSee($this->orden->folio);
        $this->actingAs($this->jefe)->get(route('commercial.ventas.show', $this->orden->venta_id))->assertOk()
            ->assertSee('Prepara')->assertSee($this->almacenista->name);
    }
}
