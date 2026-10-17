<?php

namespace Tests\Feature\Api\V1\Inventario;

use App\Models\FichaTecnica;
use App\Models\InventoryMovement;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Inventario\Concerns\DatosDeInventario;
use Tests\TestCase;

/**
 * La lógica de las pantallas de inventario se movió a servicios para
 * compartirla con la API. Esto cuida que la web siga respondiendo igual
 * (redirects, avisos y errores en el mismo campo) en lo que las pruebas
 * viejas no cubrían.
 */
class WebSigueIgualTest extends TestCase
{
    use DatosDeInventario, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discoPublicoDePrueba();
    }

    public function test_las_pantallas_siguen_cargando(): void
    {
        $user = $this->usuarioApi(admin: true);
        $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosEntrada())->assertRedirect();

        $movimiento = InventoryMovement::firstOrFail();
        $producto = Producto::firstOrFail();
        $ficha = FichaTecnica::create(['titulo' => 'Ficha', 'producto_id' => $producto->id, 'activo' => true]);

        foreach ([
            route('inventory.movimientos.index', ['tipo' => 'entrada']),
            route('inventory.movimientos.show', $movimiento),
            route('inventory.movimientos.etiquetas', $movimiento),
            route('inventory.productos.index', ['search' => 'OLYMPUS']),
            route('inventory.productos.create'),
            route('inventory.productos.show', $producto),
            route('inventory.productos.edit', $producto),
            route('inventory.fichas.index'),
            route('inventory.fichas.create'),
            route('inventory.fichas.edit', $ficha),
            route('inventory.paquetes.index'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_alta_de_producto_desde_la_web(): void
    {
        $cat = $this->catalogo();
        $user = $this->usuarioApi(['inventario.registrar', 'inventario.ver']);

        $this->actingAs($user)->from(route('inventory.productos.create'))
            ->post(route('inventory.productos.store'), [
                'equipment_type_id' => $cat['tipo']->id,
                'equipment_model_id' => $cat['modelo']->id,
                'stock' => 2,
                'series_texto' => 'W-1',
            ])
            ->assertRedirect(route('inventory.productos.index'))
            ->assertSessionHas('status', 'Producto guardado correctamente.');

        $this->assertSame(['W-1', 'W-2'], Producto::firstOrFail()->seriales()->orderBy('no_serie')->pluck('no_serie')->all());

        // El textarea vacío llega como null y no debe tronar.
        $this->actingAs($user)->post(route('inventory.productos.store'), [
            'equipment_type_id' => $cat['tipo']->id,
            'equipment_model_id' => $cat['modelo']->id,
            'stock' => 1,
            'series_texto' => '',
        ])->assertSessionHas('status', 'Se agregaron 1 unidad(es) al stock existente de CV-190.');

        $this->actingAs($user)->from(route('inventory.productos.create'))
            ->post(route('inventory.productos.store'), [
                'equipment_type_id' => $cat['tipo']->id,
                'stock' => 3,
                'series_texto' => "X\nY",
            ])
            ->assertRedirect(route('inventory.productos.create'))
            ->assertSessionHasErrors('series_texto')
            ->assertSessionHasInput('stock', 3);
    }

    public function test_piezas_desde_la_pantalla_de_edicion(): void
    {
        $producto = $this->productoConPiezas(['E-1']);
        $user = $this->usuarioApi(['inventario.editar', 'inventario.eliminar']);
        $editar = route('inventory.productos.edit', $producto);

        $this->actingAs($user)->from($editar)
            ->post(route('inventory.productos.seriales.store', $producto), ['cantidad' => 2, 'series_texto' => 'E-1'])
            ->assertRedirect($editar)
            ->assertSessionHasErrors(['series_texto' => 'Uno de esos números de serie ya existe para este producto. Revísalos y vuelve a intentar.']);

        $this->actingAs($user)->from($editar)
            ->post(route('inventory.productos.seriales.store', $producto), ['cantidad' => 2])
            ->assertRedirect($editar)
            ->assertSessionHas('status', 'Se agregaron 2 unidad(es) al inventario.');

        $pieza = $producto->seriales()->where('no_serie', 'E-1')->firstOrFail();
        $pieza->update(['vendido' => true]);

        $this->actingAs($user)->from($editar)
            ->delete(route('inventory.productos.seriales.destroy', $pieza))
            ->assertRedirect($editar)
            ->assertSessionHasErrors(['serial' => 'Esa unidad ya se vendió, no se puede eliminar del inventario.']);
    }

    public function test_fichas_desde_la_web(): void
    {
        $producto = $this->productoConPiezas();
        $user = $this->usuarioApi(['inventario.registrar', 'inventario.editar', 'inventario.eliminar']);

        $this->actingAs($user)->post(route('inventory.fichas.store'), [
            'titulo' => 'Ficha web',
            'item' => "producto:{$producto->id}",
            'activo' => '1',
            'archivo' => UploadedFile::fake()->create('f.pdf', 50, 'application/pdf'),
        ])->assertRedirect(route('inventory.fichas.index'))
            ->assertSessionHas('status', 'Ficha técnica guardada correctamente.');

        $ficha = FichaTecnica::firstOrFail();
        $this->assertSame($producto->id, $ficha->producto_id);
        Storage::disk('public')->assertExists($ficha->archivo);

        $this->actingAs($user)->post(route('inventory.fichas.store'), [
            'titulo' => 'Repetida',
            'item' => "producto:{$producto->id}",
            'archivo' => UploadedFile::fake()->create('f.pdf', 50, 'application/pdf'),
        ])->assertSessionHasErrors(['item' => 'Ese producto ya tiene otra ficha técnica asignada.']);

        // Sin "activo" en el formulario la casilla quedó desmarcada.
        $this->actingAs($user)->put(route('inventory.fichas.update', $ficha), ['titulo' => 'Ficha web 2', 'item' => ''])
            ->assertSessionHas('status', 'Ficha técnica actualizada correctamente.');
        $ficha->refresh();
        $this->assertFalse($ficha->activo);
        $this->assertNull($ficha->producto_id);

        $this->actingAs($user)->delete(route('inventory.fichas.destroy', $ficha))
            ->assertSessionHas('status', 'Ficha técnica eliminada correctamente.');
        Storage::disk('public')->assertMissing($ficha->archivo);
    }

    public function test_escaneo_y_video_por_pedazos_desde_la_web(): void
    {
        $pieza = $this->productoConPiezas(['S-1'])->seriales()->firstOrFail();
        $user = $this->usuarioApi(['inventario.escanear', 'inventario.registrar']);

        $this->actingAs($user)->postJson(route('inventory.escaneo.buscar'), ['codigo' => 'hola'])
            ->assertOk()->assertExactJson(['encontrado' => false, 'mensaje' => 'Eso no parece una etiqueta del sistema.']);

        $this->actingAs($user)->postJson(route('inventory.escaneo.buscar'), ['codigo' => 'MB-999999'])
            ->assertOk()->assertJsonPath('mensaje', 'La etiqueta MB-999999 no está registrada.');

        $this->actingAs($user)->postJson(route('inventory.escaneo.buscar'), ['codigo' => $pieza->urlPublica()])
            ->assertOk()->assertJsonPath('encontrado', true)->assertJsonPath('codigo', $pieza->codigo);

        // Llega el último pedazo sin los anteriores: el mismo 422 de siempre.
        $this->actingAs($user)->postJson(route('inventory.movimientos.videoChunk'), [
            'chunk' => UploadedFile::fake()->create('p.mp4', 10, 'video/mp4'),
            'upload_id' => 'web-12345678', 'index' => 1, 'total' => 2, 'extension' => 'mp4',
        ])->assertStatus(422)->assertExactJson(['message' => 'Faltan pedazos del video, vuelve a subirlo.']);
    }

    public function test_borrar_entrada_desde_la_web(): void
    {
        $user = $this->usuarioApi(['inventario.registrar', 'inventario.eliminar', 'inventario.ver']);

        $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosEntrada())
            ->assertRedirect(route('inventory.movimientos.index'))
            ->assertSessionHas('status');

        $movimiento = InventoryMovement::firstOrFail();
        $detalle = route('inventory.movimientos.show', $movimiento);

        // Ya no pide PIN.
        $movimiento->seriales()->update(['vendido' => true]);

        $this->actingAs($user)->from($detalle)
            ->delete(route('inventory.movimientos.destroy', $movimiento))
            ->assertRedirect($detalle)
            ->assertSessionHasErrors(['movimiento' => 'No se puede eliminar: alguna unidad de esta entrada ya se vendió.']);

        $movimiento->seriales()->update(['vendido' => false]);

        $this->actingAs($user)->delete(route('inventory.movimientos.destroy', $movimiento))
            ->assertRedirect(route('inventory.movimientos.index'))
            ->assertSessionHas('status', "Movimiento {$movimiento->folio} eliminado.");
    }
}
