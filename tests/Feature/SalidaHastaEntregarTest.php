<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\InventoryMovement;
use App\Models\OrdenSalida;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El stock se descuenta al vender (para que nadie venda dos veces la misma
 * pieza), pero el equipo puede tardar en salir del almacén. Así que el
 * movimiento nace como "Vendido" y solo pasa a "Salida" cuando se firma su
 * orden de salida, que es cuando de verdad se lo llevan.
 */
class SalidaHastaEntregarTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    private function firmaValida(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    private function productoConUnidades(int $cantidad, array $series = []): Producto
    {
        $tipo = EquipmentType::create(['name' => 'Torre '.uniqid()]);

        $producto = Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'marca' => 'Olympus',
            'modelo' => 'HD-900',
            'precio' => 500,
            'stock' => 0,
        ]);

        $producto->agregarUnidades($cantidad, $series);

        return $producto->refresh();
    }

    /** Vende y regresa [venta, producto, salida]. */
    private function vender(User $user, int $cantidad = 1): array
    {
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);
        $producto = $this->productoConUnidades(2, ['SN-A', 'SN-B']);

        $this->actingAs($user)->post(route('commercial.ventas.store'), [
            'customer_id' => $customer->id,
            'modalidad' => 'contado',
            'items' => [[
                'tipo_item' => 'producto',
                'producto_id' => $producto->id,
                'nombre' => 'Olympus HD-900',
                'cantidad' => $cantidad,
                'precio_unitario' => 500,
            ]],
        ])->assertRedirect();

        return [
            Venta::firstOrFail(),
            $producto->refresh(),
            InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)->firstOrFail(),
        ];
    }

    /**
     * Marca el checklist y cierra la preparación: así queda lista para que
     * alguien firme la salida.
     */
    private function prepararOrden(User $user, OrdenSalida $orden): void
    {
        foreach ($orden->items as $item) {
            $this->actingAs($user)->post(route('inventory.salidas.item', [$orden, $item]), [
                'requiere_emplayado' => 0,
                'preparado' => 1,
            ]);
        }

        $this->actingAs($user)->post(route('inventory.salidas.preparada', $orden->fresh()));
    }

    public function test_al_vender_el_stock_baja_pero_el_movimiento_queda_como_vendido(): void
    {
        $user = $this->usuario();
        [, $producto, $salida] = $this->vender($user);

        // El stock ya bajó: la pieza no se puede volver a vender.
        $this->assertSame(1, $producto->stock);

        // Pero el equipo todavía no sale del almacén.
        $this->assertNull($salida->entregado_en);
        $this->assertTrue($salida->ventaPendienteDeEntrega());
        $this->assertFalse($salida->entregada());
        $this->assertSame('vendido', $salida->tipoVista());
        $this->assertSame('Vendido', $salida->tipoVistaLabel());
    }

    public function test_el_listado_lo_muestra_como_vendido_pendiente_de_entrega(): void
    {
        $user = $this->usuario();
        $this->vender($user);

        $pagina = $this->actingAs($user)->get(route('inventory.movimientos.index'));

        $pagina->assertOk();
        $pagina->assertSee('pendiente de entrega');
        $pagina->assertSee('Vendidas sin entregar');
    }

    public function test_firmar_la_orden_de_salida_lo_convierte_en_salida(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();
        [$venta, $producto, $salida] = $this->vender($user);

        $orden = OrdenSalida::where('venta_id', $venta->id)->firstOrFail();
        $this->prepararOrden($user, $orden);

        $this->actingAs($user)->post(route('inventory.salidas.entregar', $orden), [
            'recibe_nombre' => 'Chofer de Prueba',
            'firma_entrega' => $this->firmaValida(),
            'firma_recibe' => $this->firmaValida(),
        ])->assertRedirect(route('inventory.salidas.show', $orden));

        $salida->refresh();

        $this->assertNotNull($salida->entregado_en, 'Al firmar la orden el movimiento debe quedar entregado.');
        $this->assertTrue($salida->entregada());
        $this->assertSame('salida', $salida->tipoVista());
        $this->assertSame('Salida', $salida->tipoVistaLabel());

        // El stock no se mueve otra vez: ya se había descontado al vender.
        $this->assertSame(1, $producto->refresh()->stock);
    }

    public function test_el_detalle_dice_si_ya_salio_o_sigue_pendiente(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();
        [$venta, , $salida] = $this->vender($user);

        $this->actingAs($user)->get(route('inventory.movimientos.show', $salida))
            ->assertSee('el equipo sigue en el almacén', false);

        $orden = OrdenSalida::where('venta_id', $venta->id)->firstOrFail();
        $this->prepararOrden($user, $orden);

        $this->actingAs($user)->post(route('inventory.salidas.entregar', $orden), [
            'recibe_nombre' => 'Chofer de Prueba',
            'firma_entrega' => $this->firmaValida(),
            'firma_recibe' => $this->firmaValida(),
        ]);

        $this->actingAs($user)->get(route('inventory.movimientos.show', $salida->fresh()))
            ->assertSee('Salió del almacén el', false);
    }

    public function test_mientras_no_se_entregue_la_venta_se_puede_cancelar(): void
    {
        $user = $this->usuario();
        [$venta, $producto] = $this->vender($user);

        $this->actingAs($user)->post(route('commercial.ventas.cancelar', $venta), [
            'motivo' => 'El cliente se arrepintió',
            'password' => 'password',
        ])->assertSessionHasNoErrors();

        // Las piezas regresan al stock, como antes.
        $this->assertSame(2, $producto->refresh()->stock);
    }

    public function test_una_venta_con_equipo_ya_entregado_no_se_cancela_ni_se_borra(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();
        [$venta, $producto] = $this->vender($user);

        $orden = OrdenSalida::where('venta_id', $venta->id)->firstOrFail();
        $this->prepararOrden($user, $orden);
        $this->actingAs($user)->post(route('inventory.salidas.entregar', $orden), [
            'recibe_nombre' => 'Chofer de Prueba',
            'firma_entrega' => $this->firmaValida(),
            'firma_recibe' => $this->firmaValida(),
        ]);

        // Cancelar regresaría al stock una pieza que el cliente ya tiene.
        $this->actingAs($user)->post(route('commercial.ventas.cancelar', $venta), [
            'motivo' => 'Prueba',
            'password' => 'password',
        ])->assertSessionHasErrors('venta');

        $this->actingAs($user)->delete(route('commercial.ventas.destroy', $venta))
            ->assertSessionHasErrors('venta');

        // Nada se movió: sigue vendida y la salida sigue en el historial.
        $this->assertSame(1, $producto->refresh()->stock);
        $this->assertFalse($venta->fresh()->cancelada());
        $this->assertSame(1, InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)->count());
    }
}
