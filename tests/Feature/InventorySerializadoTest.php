<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\EquipmentModel;
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
 * Cubre el flujo nuevo: productos que exigen serie + foto por unidad al
 * capturar una entrada, y la corrección posterior de esos datos con PIN.
 */
class InventorySerializadoTest extends TestCase
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

    public function test_la_pagina_de_crear_entrada_carga(): void
    {
        $user = $this->usuarioAprobado();

        $this->actingAs($user)->get(route('inventory.movimientos.create'))->assertOk();
    }

    public function test_entrada_serializada_guarda_foto_y_serie_por_unidad(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), [
            'equipment_type_id' => $tipo->id,
            'precio' => 1000,
            'cantidad' => 2,
            'proveedor' => 'ProveedorTest',
            'movement_date' => now()->format('Y-m-d'),
            'es_serializado' => '1',
            'unidades' => [
                ['no_serie' => 'SNU001', 'foto' => UploadedFile::fake()->create('u1.jpg', 50, 'image/jpeg')],
                ['no_serie' => 'SNU002', 'foto' => UploadedFile::fake()->create('u2.jpg', 50, 'image/jpeg')],
            ],
        ]);

        $response->assertRedirect(route('inventory.movimientos.index'));

        $producto = Producto::first();
        $this->assertNotNull($producto);
        $this->assertTrue((bool) $producto->es_serializado);
        $this->assertSame(2, $producto->stock);

        $seriales = ProductoSerial::where('producto_id', $producto->id)->orderBy('no_serie')->get();
        $this->assertCount(2, $seriales);

        foreach ($seriales as $serial) {
            $this->assertNotNull($serial->foto_path);
            Storage::disk('public')->assertExists($serial->foto_path);
            $this->assertSame($user->id, $serial->capturado_por);
        }

        // La evidencia general es opcional cuando el producto es serializado.
        $movimiento = InventoryMovement::first();
        $this->assertSame([], $movimiento->evidence_paths ?? []);
    }

    public function test_serial_duplicado_no_pierde_la_unidad_ni_la_foto(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);
        $marca = Brand::create(['name' => 'MarcaTest'.uniqid()]);
        $modelo = EquipmentModel::create(['brand_id' => $marca->id, 'name' => 'ModeloTest'.uniqid()]);

        $producto = Producto::create([
            'equipment_type_id' => $tipo->id,
            'equipment_model_id' => $modelo->id,
            'tipo_equipo' => $tipo->name,
            'precio' => 500,
            'stock' => 0,
            'es_serializado' => true,
        ]);
        $producto->agregarUnidades(1, ['SN-YA-EXISTE']);

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), [
            'equipment_type_id' => $tipo->id,
            'equipment_model_id' => $modelo->id,
            'precio' => 500,
            'cantidad' => 2,
            'movement_date' => now()->format('Y-m-d'),
            'es_serializado' => '1',
            'unidades' => [
                ['no_serie' => 'SN-YA-EXISTE', 'foto' => UploadedFile::fake()->create('u1.jpg', 50, 'image/jpeg')],
                ['no_serie' => 'SN-NUEVA', 'foto' => UploadedFile::fake()->create('u2.jpg', 50, 'image/jpeg')],
            ],
        ]);

        $response->assertRedirect(route('inventory.movimientos.index'));

        $producto->refresh();
        // La unidad vieja (1) + las 2 nuevas que llegaron = 3, aunque una
        // de las nuevas se quedó sin serial por el choque.
        $this->assertSame(3, $producto->stock);

        $sinSerie = ProductoSerial::where('producto_id', $producto->id)->whereNull('no_serie')->get();
        $this->assertCount(1, $sinSerie);
        $this->assertNotNull($sinSerie->first()->foto_path, 'La foto de la unidad rechazada no debe perderse.');

        $this->assertSame(1, ProductoSerial::where('producto_id', $producto->id)->where('no_serie', 'SN-NUEVA')->count());
    }

    public function test_actualizar_serial_pide_pin_o_password(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);
        $producto = Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'precio' => 500,
            'stock' => 0,
        ]);
        $producto->agregarUnidades(1, ['SN-ORIGINAL']);
        $serial = $producto->seriales()->first();

        // Password incorrecta: rechazado.
        $this->actingAs($user)->put(route('inventory.productos.seriales.update', $serial), [
            'password' => 'incorrecta',
            'no_serie' => 'SN-CORREGIDA',
        ])->assertSessionHasErrors('password');

        $serial->refresh();
        $this->assertSame('SN-ORIGINAL', $serial->no_serie);

        // Password correcta (la del factory es "password"): se actualiza.
        $this->actingAs($user)->put(route('inventory.productos.seriales.update', $serial), [
            'password' => 'password',
            'no_serie' => 'SN-CORREGIDA',
        ])->assertSessionDoesntHaveErrors();

        $serial->refresh();
        $this->assertSame('SN-CORREGIDA', $serial->no_serie);
        $this->assertSame($user->id, $serial->editado_por);
    }
}
