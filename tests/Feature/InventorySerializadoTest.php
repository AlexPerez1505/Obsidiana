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
 * Cubre el flujo de entrada por unidad (serie + evidencia individual, sin
 * importar si el producto está marcado como serializado o no), y la
 * corrección posterior de esos datos con PIN.
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

    /** Data URL base64 mínima (PNG 1x1) para simular una firma capturada. */
    private function firmaValida(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    public function test_la_pagina_de_crear_entrada_carga(): void
    {
        $user = $this->usuarioAprobado();

        $this->actingAs($user)->get(route('inventory.movimientos.create'))->assertOk();
    }

    public function test_entrada_guarda_evidencia_y_serie_por_unidad(): void
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
            'firma' => $this->firmaValida(),
            'unidades' => [
                ['no_serie' => 'SNU001', 'evidencias' => [UploadedFile::fake()->create('u1.jpg', 50, 'image/jpeg')]],
                ['no_serie' => 'SNU002', 'evidencias' => [UploadedFile::fake()->create('u2.jpg', 50, 'image/jpeg')]],
            ],
        ]);

        $response->assertRedirect(route('inventory.movimientos.index'));

        $producto = Producto::first();
        $this->assertNotNull($producto);
        $this->assertSame(2, $producto->stock);

        $seriales = ProductoSerial::where('producto_id', $producto->id)->orderBy('no_serie')->get();
        $this->assertCount(2, $seriales);

        foreach ($seriales as $serial) {
            $this->assertNotNull($serial->foto_path);
            $this->assertNotEmpty($serial->evidence_paths);
            Storage::disk('public')->assertExists($serial->foto_path);
            $this->assertSame($user->id, $serial->capturado_por);
        }
    }

    public function test_serial_duplicado_no_pierde_la_unidad_ni_su_evidencia(): void
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
        ]);
        $producto->agregarUnidades(1, ['SN-YA-EXISTE']);

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), [
            'equipment_type_id' => $tipo->id,
            'equipment_model_id' => $modelo->id,
            'precio' => 500,
            'cantidad' => 2,
            'movement_date' => now()->format('Y-m-d'),
            'firma' => $this->firmaValida(),
            'unidades' => [
                ['no_serie' => 'SN-YA-EXISTE', 'evidencias' => [UploadedFile::fake()->create('u1.jpg', 50, 'image/jpeg')]],
                ['no_serie' => 'SN-NUEVA', 'evidencias' => [UploadedFile::fake()->create('u2.jpg', 50, 'image/jpeg')]],
            ],
        ]);

        // Una serie no puede repetirse: la entrada completa se rechaza con el
        // aviso de quién ya la tiene, y no se guarda ninguna pieza a medias.
        $response->assertSessionHasErrors('unidades');
        $this->assertStringContainsString('SN-YA-EXISTE', session('errors')->first('unidades'));

        $producto->refresh();
        $this->assertSame(1, $producto->seriales()->count());
        $this->assertSame(0, ProductoSerial::where('no_serie', 'SN-NUEVA')->count());
    }

    public function test_cada_pieza_conserva_su_serie_sin_inventar_secuencias(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), [
            'equipment_type_id' => $tipo->id,
            'precio' => 500,
            'cantidad' => 3,
            'movement_date' => now()->format('Y-m-d'),
            'firma' => $this->firmaValida(),
            'unidades' => [
                ['no_serie' => '23A00010', 'evidencias' => [UploadedFile::fake()->create('u1.jpg', 50, 'image/jpeg')]],
                ['no_serie' => 'X-2', 'evidencias' => [UploadedFile::fake()->create('u2.jpg', 50, 'image/jpeg')]],
                ['no_serie' => 'Y-3', 'evidencias' => [UploadedFile::fake()->create('u3.jpg', 50, 'image/jpeg')]],
            ],
        ]);

        $response->assertRedirect(route('inventory.movimientos.index'));

        // Antes se armaba 23A00010, 23A00011, 23A00012 a partir de la
        // primera. Ahora cada pieza se queda con la serie que trae (para
        // numerar en secuencia está el botón "Generar series").
        $producto = Producto::first();
        $series = ProductoSerial::where('producto_id', $producto->id)->orderBy('id')->pluck('no_serie');
        $this->assertSame(['23A00010', 'X-2', 'Y-3'], $series->all());
    }

    public function test_la_serie_de_la_pieza_3_no_se_mueve_a_la_pieza_1(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);

        $this->actingAs($user)->post(route('inventory.movimientos.store'), [
            'equipment_type_id' => $tipo->id,
            'precio' => 500,
            'cantidad' => 3,
            'movement_date' => now()->format('Y-m-d'),
            'firma' => $this->firmaValida(),
            'unidades' => [
                ['no_serie' => 'AAA-1', 'evidencias' => [UploadedFile::fake()->create('u1.jpg', 50, 'image/jpeg')]],
                ['no_serie' => 'BBB-2', 'evidencias' => [UploadedFile::fake()->create('u2.jpg', 50, 'image/jpeg')]],
                ['no_serie' => 'ZZ-77', 'evidencias' => [UploadedFile::fake()->create('u3.jpg', 50, 'image/jpeg')]],
            ],
        ])->assertRedirect(route('inventory.movimientos.index'));

        $series = ProductoSerial::orderBy('id')->pluck('no_serie');
        $this->assertSame(['AAA-1', 'BBB-2', 'ZZ-77'], $series->all());
    }

    public function test_actualizar_serial_ya_no_pide_pin(): void
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

        $this->actingAs($user)->put(route('inventory.productos.seriales.update', $serial), [
            'no_serie' => 'SN-CORREGIDA',
        ])->assertSessionDoesntHaveErrors();

        $serial->refresh();
        $this->assertSame('SN-CORREGIDA', $serial->no_serie);
        $this->assertSame($user->id, $serial->editado_por);
    }
}
