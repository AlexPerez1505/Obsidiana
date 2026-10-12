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
 * unidades/seriales, y exigir evidencia (1 a 3 fotos, video opcional) por
 * cada unidad que llega, no una evidencia general del lote.
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

    /** Data URL base64 mínima (PNG 1x1) para simular una firma capturada. */
    private function firmaValida(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    /**
     * Simula la subida de un video por chunks (en un solo pedazo, ya que
     * es pequeño) y regresa la ruta ya ensamblada que el formulario real
     * mandaría en "unidades.{i}.video_path".
     */
    private function subirVideoDePrueba(User $user): string
    {
        $respuesta = $this->actingAs($user)->post(route('inventory.movimientos.videoChunk'), [
            'chunk' => UploadedFile::fake()->create('chunk.mp4', 500, 'video/mp4'),
            'upload_id' => 'test-'.uniqid(),
            'index' => 0,
            'total' => 1,
            'extension' => 'mp4',
        ]);

        return $respuesta->json('video_path');
    }

    /** Una unidad con 1 foto de evidencia y sin video, lista para el submit. */
    private function unidad(array $overrides = []): array
    {
        return array_merge([
            'no_serie' => null,
            'evidencias' => [UploadedFile::fake()->create('u.jpg', 50, 'image/jpeg')],
        ], $overrides);
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
            'firma' => $this->firmaValida(),
            'unidades' => [$this->unidad()],
        ], $overrides);
    }

    public function test_registrar_entrada_crea_el_producto_sus_unidades_y_guarda_la_evidencia_por_unidad(): void
    {
        Storage::fake('public');

        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosBase([
            'cantidad' => 3,
            'unidades' => [
                $this->unidad([
                    'no_serie' => '23A00001',
                    'evidencias' => [
                        UploadedFile::fake()->create('u1a.jpg', 50, 'image/jpeg'),
                        UploadedFile::fake()->create('u1b.jpg', 50, 'image/jpeg'),
                    ],
                    'video_path' => $this->subirVideoDePrueba($user),
                ]),
                $this->unidad(),
                $this->unidad(),
            ],
        ]));

        $response->assertRedirect(route('inventory.movimientos.index'));

        $producto = Producto::first();
        $this->assertNotNull($producto);
        $this->assertSame(3, $producto->stock);

        // Un solo serial base con cantidad 3: la secuencia se completa sola.
        $series = ProductoSerial::where('producto_id', $producto->id)->pluck('no_serie')->sort()->values();
        $this->assertSame(['23A00001', '23A00002', '23A00003'], $series->all());

        $primera = ProductoSerial::where('no_serie', '23A00001')->first();
        $this->assertCount(2, $primera->evidence_paths);
        foreach ($primera->evidence_paths as $path) {
            Storage::disk('public')->assertExists($path);
        }
        $this->assertNotNull($primera->video_path);
        Storage::disk('public')->assertExists($primera->video_path);

        $segunda = ProductoSerial::where('no_serie', '23A00002')->first();
        $this->assertCount(1, $segunda->evidence_paths);
        $this->assertNull($segunda->video_path);

        $movimiento = InventoryMovement::where('movement_type', InventoryMovement::TYPE_ENTRY)->first();
        $this->assertNotNull($movimiento);
        $this->assertNotNull($movimiento->signature_path);
        Storage::disk('public')->assertExists($movimiento->signature_path);
        $this->assertSame($user->id, $movimiento->created_by);
    }

    public function test_entrada_sin_unidades_no_se_guarda(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $data = $this->datosBase();
        unset($data['unidades']);

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), $data);

        $response->assertSessionHasErrors('unidades');
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_una_unidad_sin_evidencia_no_se_guarda(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosBase([
            'unidades' => [['no_serie' => null, 'evidencias' => []]],
        ]));

        $response->assertSessionHasErrors('unidades.0.evidencias');
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_una_unidad_con_mas_de_3_fotos_regresa_error(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosBase([
            'unidades' => [$this->unidad([
                'evidencias' => [
                    UploadedFile::fake()->create('e1.jpg', 50, 'image/jpeg'),
                    UploadedFile::fake()->create('e2.jpg', 50, 'image/jpeg'),
                    UploadedFile::fake()->create('e3.jpg', 50, 'image/jpeg'),
                    UploadedFile::fake()->create('e4.jpg', 50, 'image/jpeg'),
                ],
            ])],
        ]));

        $response->assertSessionHasErrors('unidades.0.evidencias');
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_entrada_sin_firma_no_se_guarda(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosBase(['firma' => '']));

        $response->assertSessionHasErrors('firma');
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_unidad_sin_video_se_guarda_porque_es_opcional(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosBase());

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('inventory.movimientos.index'));

        $this->assertDatabaseCount('inventory_movements', 1);
        $serial = ProductoSerial::first();
        $this->assertNotNull($serial->evidence_paths);
        $this->assertNull($serial->video_path);
    }

    public function test_unidad_con_video_path_inventado_no_se_guarda(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosBase([
            'unidades' => [$this->unidad(['video_path' => 'inventario/entradas/video_no-existe.mp4'])],
        ]));

        $response->assertSessionHasErrors('unidades.0.video_path');
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_renglones_de_unidades_que_no_coinciden_con_la_cantidad_regresan_error(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosBase([
            'cantidad' => 3,
            'unidades' => [$this->unidad(), $this->unidad()],
        ]));

        $response->assertSessionHasErrors('unidades');
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_eliminar_entrada_borra_las_unidades_y_toda_su_evidencia_del_disco(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosBase([
            'unidades' => [$this->unidad([
                'evidencias' => [
                    UploadedFile::fake()->create('e1.jpg', 50, 'image/jpeg'),
                    UploadedFile::fake()->create('e2.jpg', 50, 'image/jpeg'),
                ],
                'video_path' => $this->subirVideoDePrueba($user),
            ])],
        ]));

        $movimiento = InventoryMovement::first();
        $serial = ProductoSerial::first();
        $evidencias = $serial->evidence_paths;
        $video = $serial->video_path;
        $firma = $movimiento->signature_path;

        $this->assertCount(2, $evidencias);
        $this->assertNotNull($video);

        $this->actingAs($user)
            ->delete(route('inventory.movimientos.destroy', $movimiento), ['password' => 'password'])
            ->assertRedirect(route('inventory.movimientos.index'));

        $this->assertSame(0, InventoryMovement::count());
        foreach ($evidencias as $path) {
            Storage::disk('public')->assertMissing($path);
        }
        Storage::disk('public')->assertMissing($video);
        Storage::disk('public')->assertMissing($firma);
    }

    public function test_no_se_puede_eliminar_una_entrada_con_unidades_ya_vendidas(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosBase([
            'unidades' => [$this->unidad(['no_serie' => 'SN-VENDIDA'])],
        ]));

        $movimiento = InventoryMovement::first();
        ProductoSerial::where('no_serie', 'SN-VENDIDA')->update(['vendido' => true, 'vendido_en' => now()]);

        $response = $this->actingAs($user)
            ->delete(route('inventory.movimientos.destroy', $movimiento), ['password' => 'password']);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseCount('inventory_movements', 1);
    }
}
