<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\InventoryMovement;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un paquete no tiene stock propio: al venderlo, debe descontar el stock
 * real de cada producto que lo compone (multiplicado por la cantidad del
 * pivote), y devolverlo si la venta se edita o se cancela. Antes de este
 * fix, vender un paquete no tocaba el inventario en absoluto.
 */
class VentaPaqueteStockTest extends TestCase
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
            'modelo' => 'ModeloTest'.uniqid(),
            'precio' => 500,
            'stock' => 0,
        ]);

        $producto->agregarUnidades($cantidad, $series);

        return $producto->refresh();
    }

    public function test_vender_un_paquete_descuenta_el_stock_de_sus_productos(): void
    {
        $user = $this->usuarioAprobado();
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);

        $productoA = $this->productoConUnidades(3, ['A001', 'A002', 'A003']);
        $productoB = $this->productoConUnidades(5, ['B001', 'B002', 'B003', 'B004', 'B005']);

        $paquete = Paquete::create(['nombre' => 'Paquete de prueba']);
        $paquete->productos()->sync([
            $productoA->id => ['cantidad' => 1],
            $productoB->id => ['cantidad' => 2],
        ]);

        $response = $this->actingAs($user)->post(route('commercial.ventas.store'), [
            'customer_id' => $customer->id,
            'modalidad' => 'contado',
            'items' => [[
                'tipo_item' => 'paquete',
                'paquete_id' => $paquete->id,
                'nombre' => $paquete->nombre,
                'cantidad' => 2,
                'precio_unitario' => 1000,
            ]],
        ]);

        $response->assertRedirect();

        // 2 paquetes vendidos: A se descuenta x1 = 2 unidades, B se descuenta x2 = 4 unidades.
        $productoA->refresh();
        $productoB->refresh();
        $this->assertSame(1, $productoA->stock);
        $this->assertSame(1, $productoB->stock);

        $salidas = InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)->get();
        $this->assertCount(2, $salidas, 'Debe crearse una salida por cada producto componente del paquete.');
        $this->assertSame(2, $salidas->firstWhere('item_id', $productoA->id)->quantity);
        $this->assertSame(4, $salidas->firstWhere('item_id', $productoB->id)->quantity);

        $vendidosA = ProductoSerial::where('producto_id', $productoA->id)->where('vendido', true)->count();
        $vendidosB = ProductoSerial::where('producto_id', $productoB->id)->where('vendido', true)->count();
        $this->assertSame(2, $vendidosA);
        $this->assertSame(4, $vendidosB);
    }

    public function test_cancelar_venta_de_paquete_libera_el_stock_de_todos_sus_productos(): void
    {
        $user = $this->usuarioAprobado();
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);

        $productoA = $this->productoConUnidades(2, ['C001', 'C002']);
        $productoB = $this->productoConUnidades(2, ['D001', 'D002']);

        $paquete = Paquete::create(['nombre' => 'Paquete de prueba']);
        $paquete->productos()->sync([
            $productoA->id => ['cantidad' => 1],
            $productoB->id => ['cantidad' => 1],
        ]);

        $this->actingAs($user)->post(route('commercial.ventas.store'), [
            'customer_id' => $customer->id,
            'modalidad' => 'contado',
            'items' => [[
                'tipo_item' => 'paquete',
                'paquete_id' => $paquete->id,
                'nombre' => $paquete->nombre,
                'cantidad' => 1,
                'precio_unitario' => 1000,
            ]],
        ]);

        $productoA->refresh();
        $productoB->refresh();
        $this->assertSame(1, $productoA->stock);
        $this->assertSame(1, $productoB->stock);

        $venta = Venta::first();

        $this->actingAs($user)->delete(route('commercial.ventas.destroy', $venta))->assertRedirect();

        $productoA->refresh();
        $productoB->refresh();
        $this->assertSame(2, $productoA->stock, 'El producto A debe recuperar su stock completo.');
        $this->assertSame(2, $productoB->stock, 'El producto B debe recuperar su stock completo.');
        $this->assertSame(0, InventoryMovement::count(), 'Las salidas del paquete deben borrarse al cancelar.');
    }
}
