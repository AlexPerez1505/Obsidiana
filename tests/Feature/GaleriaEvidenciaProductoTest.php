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

    /**
     * La evidencia se ve de entrada en la ventana de unidades: las tres
     * fotos como miniaturas y el video con su reproductor, sin tener que
     * abrir nada primero. Antes solo se veía la primera foto.
     */
    public function test_la_ventana_muestra_las_fotos_y_el_video_sin_abrir_la_galeria(): void
    {
        $user = $this->usuarioAprobado();
        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);
        $producto = Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'marca' => 'Olympus',
            'modelo' => 'HD-900',
            'precio' => 500,
            'stock' => 0,
        ]);

        ProductoSerial::create([
            'producto_id' => $producto->id,
            'codigo' => 'MB-000123',
            'no_serie' => 'SN-VISIBLE',
            'condicion' => 'usado',
            'evidence_paths' => ['productos/seriales/v1.jpg', 'productos/seriales/v2.jpg'],
            'foto_path' => 'productos/seriales/v1.jpg',
            'video_path' => 'productos/seriales/entrada.mp4',
            'vendido' => false,
        ]);

        $response = $this->actingAs($user)->get(route('inventory.productos.index'));

        $response->assertOk();

        // Cada foto se pinta como su propia miniatura, con su posición.
        $response->assertSee('Foto 1 de 2');
        $response->assertSee('Foto 2 de 2');
        $response->assertSee('data-indice="0"', false);
        $response->assertSee('data-indice="1"', false);

        // El video va con reproductor, no como enlace.
        $response->assertSee('<video src="'.\Illuminate\Support\Facades\Storage::disk(config('filesystems.fotos_disk', 'public'))->url('productos/seriales/entrada.mp4').'" controls', false);
        $response->assertSee('Video de entrada');

        // Con su número de serie, su etiqueta interna y el modelo.
        $response->assertSee('SN-VISIBLE');
        $response->assertSee('MB-000123');
        $response->assertSee('Olympus HD-900');
    }

    public function test_una_unidad_sin_evidencia_lo_dice_en_la_ventana(): void
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
            'no_serie' => 'SN-PELON',
            'vendido' => false,
        ]);

        $response = $this->actingAs($user)->get(route('inventory.productos.index'));

        $response->assertOk();
        $response->assertSee('se registró sin evidencia de entrada');
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
