<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Al hacer clic en un movimiento del listado se abre su detalle, y ahí sale
 * todo lo que quedó registrado: sus piezas y la evidencia de cada una.
 *
 * Antes había que buscar "Ver detalle" en el menú de tres puntos, y el
 * detalle de una salida no mostraba las piezas ni su evidencia.
 */
class MovimientoDetalleTest extends TestCase
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
            'marca' => 'Olympus',
            'modelo' => 'HD-900',
            'precio' => 500,
            'stock' => 0,
        ]);

        $producto->agregarUnidades($cantidad, $series);

        return $producto->refresh();
    }

    public function test_cada_fila_del_listado_lleva_a_su_detalle(): void
    {
        $user = $this->usuarioAprobado();
        $producto = $this->productoConUnidades(2, ['SN-A', 'SN-B']);

        $movimiento = InventoryMovement::create([
            'folio' => 'ENT-TEST-1',
            'movement_type' => InventoryMovement::TYPE_ENTRY,
            'item_type' => InventoryMovement::ITEM_PRODUCT,
            'item_id' => $producto->id,
            'item_code' => (string) $producto->id,
            'item_name' => 'Olympus HD-900',
            'warehouse' => 'Almacen Central',
            'quantity' => 2,
            'unit' => 'Pza',
            'stock_before' => 0,
            'stock_after' => 2,
            'movement_date' => now(),
            'created_by' => $user->id,
        ]);

        $pagina = $this->actingAs($user)->get(route('inventory.movimientos.index'));

        $pagina->assertOk();
        // La fila entera es clickeable, no solo el menú de tres puntos.
        $pagina->assertSee('data-ver="'.route('inventory.movimientos.show', $movimiento).'"', false);
        $pagina->assertSee('mv-clic', false);
    }

    public function test_el_detalle_de_una_entrada_lista_todas_sus_piezas(): void
    {
        $user = $this->usuarioAprobado();
        $producto = $this->productoConUnidades(3, ['SN-001', 'SN-002', 'SN-003']);

        $movimiento = InventoryMovement::create([
            'folio' => 'ENT-TEST-2',
            'movement_type' => InventoryMovement::TYPE_ENTRY,
            'item_type' => InventoryMovement::ITEM_PRODUCT,
            'item_id' => $producto->id,
            'item_code' => (string) $producto->id,
            'item_name' => 'Olympus HD-900',
            'warehouse' => 'Almacen Central',
            'quantity' => 3,
            'unit' => 'Pza',
            'stock_before' => 0,
            'stock_after' => 3,
            'movement_date' => now(),
            'created_by' => $user->id,
        ]);

        // Las piezas quedan ligadas a este movimiento, como en el alta real.
        $producto->seriales()->update(['inventory_movement_id' => $movimiento->id]);

        $pagina = $this->actingAs($user)->get(route('inventory.movimientos.show', $movimiento));

        $pagina->assertOk();
        $pagina->assertSee('SN-001');
        $pagina->assertSee('SN-002');
        $pagina->assertSee('SN-003');
        $pagina->assertSee('Evidencia por unidad');
    }

    public function test_el_detalle_de_una_salida_tambien_muestra_las_piezas_que_salieron(): void
    {
        $user = $this->usuarioAprobado();
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);
        $producto = $this->productoConUnidades(2, ['SN-VENDIDA', 'SN-EN-STOCK']);

        // Vender genera la salida sola, con su metadata del renglón.
        $this->actingAs($user)->post(route('commercial.ventas.store'), [
            'customer_id' => $customer->id,
            'modalidad' => 'contado',
            'items' => [[
                'tipo_item' => 'producto',
                'producto_id' => $producto->id,
                'nombre' => 'Olympus HD-900',
                'cantidad' => 1,
                'precio_unitario' => 500,
            ]],
        ])->assertRedirect();

        $salida = InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)->firstOrFail();

        $pagina = $this->actingAs($user)->get(route('inventory.movimientos.show', $salida));

        $pagina->assertOk();
        // La pieza que salió, con el bloque de evidencia que antes se ocultaba.
        $pagina->assertSee('SN-VENDIDA');
        $pagina->assertSee('Piezas que salieron y su evidencia');
        // Y no aparece la que se quedó en stock.
        $pagina->assertDontSee('SN-EN-STOCK');
    }
}
