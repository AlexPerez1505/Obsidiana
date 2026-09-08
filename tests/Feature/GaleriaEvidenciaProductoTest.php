<?php

namespace Tests\Feature;

use App\Models\EquipmentType;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * En Productos, al abrir las unidades disponibles de un producto se debe
 * poder ver toda la evidencia de cómo llegó cada una (hasta 3 fotos +
 * video), no solo la primera foto. La galería/lightbox se dispara desde
 * un elemento con data-medios (JSON de {tipo, url} por cada foto/video).
 */
class GaleriaEvidenciaProductoTest extends TestCase
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

    public function test_unidad_con_3_fotos_y_video_expone_los_4_medios_en_el_data_atributo(): void
    {
        $user = $this->usuarioAprobado();
        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);
        $producto = Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'precio' => 500,
            'stock' => 0,
        ]);

        ProductoSerial::create([
            'producto_id' => $producto->id,
            'no_serie' => 'SN-GALERIA',
            'evidence_paths' => ['productos/seriales/f1.jpg', 'productos/seriales/f2.jpg', 'productos/seriales/f3.jpg'],
            'foto_path' => 'productos/seriales/f1.jpg',
            'video_path' => 'productos/seriales/video1.mp4',
            'vendido' => false,
        ]);

        $response = $this->actingAs($user)->get(route('inventory.productos.index'));

        $response->assertOk();
        $response->assertSee('data-medios', false);
        $response->assertSee('abrirGaleria(this)', false);
        $response->assertSee('f1.jpg', false);
        $response->assertSee('f2.jpg', false);
        $response->assertSee('f3.jpg', false);
        $response->assertSee('video1.mp4', false);
        $response->assertSee('Ver evidencia (3 fotos + video)');
    }

    public function test_unidad_sin_video_no_ofrece_video_en_el_texto(): void
    {
        $user = $this->usuarioAprobado();
        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);
        $producto = Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'precio' => 500,
            'stock' => 0,
        ]);

        ProductoSerial::create([
            'producto_id' => $producto->id,
            'no_serie' => 'SN-SIN-VIDEO',
            'evidence_paths' => ['productos/seriales/a1.jpg'],
            'foto_path' => 'productos/seriales/a1.jpg',
            'vendido' => false,
        ]);

        $response = $this->actingAs($user)->get(route('inventory.productos.index'));

        $response->assertOk();
        $response->assertSee('Ver evidencia (1 foto)');
        $response->assertDontSee('Ver evidencia (1 foto + video)');
    }
}
