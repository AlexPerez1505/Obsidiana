<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el flujo que ya se rompió una vez en producción: vender debe bajar
 * el stock real (por unidad/serial), dejar registrada la salida en la
 * bitácora, y devolver todo si la venta se edita o se cancela.
 */
class VentaStockTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioAprobado(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    private function productoConUnidades(int $cantidad, array $series = []): Producto
    {
        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia '.uniqid()]);

        $producto = Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'marca' => 'MarcaTest',
            'modelo' => 'ModeloTest',
            'precio' => 500,
            'stock' => 0,
        ]);

        $producto->agregarUnidades($cantidad, $series);

        return $producto->refresh();
    }

    private function itemsParaVenta(Producto $producto, int $cantidad): array
    {
        return [
            [
                'tipo_item' => 'producto',
                'producto_id' => $producto->id,
                'nombre' => trim($producto->marca.' '.$producto->modelo),
                'cantidad' => $cantidad,
                'precio_unitario' => (float) $producto->precio,
            ],
        ];
    }

    public function test_vender_descuenta_el_stock_y_registra_la_salida(): void
    {
        $user = $this->usuarioAprobado();
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);
        $producto = $this->productoConUnidades(3, ['SN001', 'SN002', 'SN003']);

        $response = $this->actingAs($user)->post(route('commercial.ventas.store'), [
            'customer_id' => $customer->id,
            'modalidad' => 'contado',
            'items' => $this->itemsParaVenta($producto, 2),
        ]);

        $response->assertRedirect();

        $producto->refresh();
        $this->assertSame(1, $producto->stock);

        $venta = Venta::first();
        $this->assertNotNull($venta);

        $salida = InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)->first();
        $this->assertNotNull($salida, 'Debe crearse una salida automática al vender.');
        $this->assertSame(2, $salida->quantity);
        $this->assertSame($venta->folio, $salida->reference);
        $this->assertSame(3, $salida->stock_before);
        $this->assertSame(1, $salida->stock_after);

        // Los dos seriales más antiguos (FIFO) deben quedar vendidos.
        $vendidos = $producto->seriales()->where('vendido', true)->pluck('no_serie')->sort()->values();
        $this->assertSame(['SN001', 'SN002'], $vendidos->all());
    }

    public function test_editar_la_venta_libera_las_unidades_y_borra_la_salida(): void
    {
        $user = $this->usuarioAprobado();
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);
        $producto = $this->productoConUnidades(2, ['SN010', 'SN011']);

        $this->actingAs($user)->post(route('commercial.ventas.store'), [
            'customer_id' => $customer->id,
            'modalidad' => 'contado',
            'items' => $this->itemsParaVenta($producto, 2),
        ]);

        $producto->refresh();
        $this->assertSame(0, $producto->stock);

        $venta = Venta::first();

        // Se edita la venta y se quita el producto por completo del pedido.
        $otroProducto = $this->productoConUnidades(1, ['SNOTRO']);

        $this->actingAs($user)->put(route('commercial.ventas.update', $venta), [
            'customer_id' => $customer->id,
            'modalidad' => 'contado',
            'items' => $this->itemsParaVenta($otroProducto, 1),
        ])->assertRedirect();

        $producto->refresh();
        $this->assertSame(2, $producto->stock, 'Las unidades originales deben volver a estar disponibles.');

        // La salida original quedó soft-deleted (ya no cuenta); la única
        // salida "viva" es la del producto que se dejó en la venta editada.
        $this->assertSame(1, InventoryMovement::count());
        $salidaRestante = InventoryMovement::first();
        $this->assertSame($otroProducto->id, $salidaRestante->item_id);
    }

    public function test_cancelar_la_venta_libera_las_unidades(): void
    {
        $user = $this->usuarioAprobado();
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);
        $producto = $this->productoConUnidades(1, ['SN099']);

        $this->actingAs($user)->post(route('commercial.ventas.store'), [
            'customer_id' => $customer->id,
            'modalidad' => 'contado',
            'items' => $this->itemsParaVenta($producto, 1),
        ]);

        $producto->refresh();
        $this->assertSame(0, $producto->stock);

        $venta = Venta::first();

        $this->actingAs($user)->delete(route('commercial.ventas.destroy', $venta))->assertRedirect();

        $producto->refresh();
        $this->assertSame(1, $producto->stock);
        $this->assertSame(0, InventoryMovement::count());
    }
}
