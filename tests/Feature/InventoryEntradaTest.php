<?php

namespace Tests\Feature;

use App\Models\EquipmentType;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cubre el alta de inventario vía Entrada: debe crear el producto, sus
 * unidades/seriales, guardar la evidencia del envío, y exigir que la
 * cantidad y los seriales capturados coincidan.
 */
class InventoryEntradaTest extends TestCase
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

    private function datosBase(array $overrides = []): array
    {
        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);

        return array_merge([
            'equipment_type_id' => $tipo->id,
            'precio' => 750,
            'cantidad' => 1,
            'proveedor' => 'ProveedorTest',
            'movement_date' => now()->format('Y-m-d'),
        ], $overrides);
    }

    public function test_registrar_entrada_crea_el_producto_sus_unidades_y_guarda_la_evidencia(): void
    {
        Storage::fake('public');

        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), array_merge(
            $this->datosBase(['cantidad' => 3, 'series_texto' => '23A00001']),
            ['evidencias' => [UploadedFile::fake()->create('evidencia.jpg', 50, 'image/jpeg')]]
        ));

        $response->assertRedirect(route('inventory.movimientos.index'));

        $producto = Producto::first();
        $this->assertNotNull($producto);
        $this->assertSame(3, $producto->stock);

        // Un solo serial base con cantidad 3: la secuencia se completa sola.
        $series = ProductoSerial::where('producto_id', $producto->id)->pluck('no_serie')->sort()->values();
        $this->assertSame(['23A00001', '23A00002', '23A00003'], $series->all());

        $movimiento = InventoryMovement::where('movement_type', InventoryMovement::TYPE_ENTRY)->first();
        $this->assertNotNull($movimiento);
        $this->assertCount(1, $movimiento->evidence_paths);
        Storage::disk('public')->assertExists($movimiento->evidence_paths[0]);
    }

    public function test_entrada_sin_evidencia_no_se_guarda(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(
            route('inventory.movimientos.store'),
            $this->datosBase()
        );

        $response->assertSessionHasErrors('evidencias');
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_series_que_no_coinciden_con_la_cantidad_regresan_error(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), array_merge(
            $this->datosBase(['cantidad' => 3, 'series_texto' => "SN1\nSN2"]),
            ['evidencias' => [UploadedFile::fake()->create('evidencia.jpg', 50, 'image/jpeg')]]
        ));

        $response->assertSessionHasErrors('series_texto');
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_eliminar_entrada_borra_las_unidades_y_la_evidencia_del_disco(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $this->actingAs($user)->post(route('inventory.movimientos.store'), array_merge(
            $this->datosBase(['cantidad' => 1]),
            ['evidencias' => [UploadedFile::fake()->create('evidencia.jpg', 50, 'image/jpeg')]]
        ));

        $movimiento = InventoryMovement::first();
        $evidencia = $movimiento->evidence_paths[0];

        $this->actingAs($user)
            ->delete(route('inventory.movimientos.destroy', $movimiento), ['password' => 'password'])
            ->assertRedirect(route('inventory.movimientos.index'));

        $this->assertSame(0, InventoryMovement::count());
        Storage::disk('public')->assertMissing($evidencia);
    }

    public function test_no_se_puede_eliminar_una_entrada_con_unidades_ya_vendidas(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $this->actingAs($user)->post(route('inventory.movimientos.store'), array_merge(
            $this->datosBase(['cantidad' => 1, 'series_texto' => 'SN-VENDIDA']),
            ['evidencias' => [UploadedFile::fake()->create('evidencia.jpg', 50, 'image/jpeg')]]
        ));

        $movimiento = InventoryMovement::first();
        ProductoSerial::where('no_serie', 'SN-VENDIDA')->update(['vendido' => true, 'vendido_en' => now()]);

        $response = $this->actingAs($user)
            ->delete(route('inventory.movimientos.destroy', $movimiento), ['password' => 'password']);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseCount('inventory_movements', 1);
    }
}
