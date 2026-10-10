<?php

namespace Tests\Feature\Api\V1\Inventario;

use App\Models\FichaTecnica;
use App\Models\Paquete;
use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Inventario\Concerns\DatosDeInventario;
use Tests\TestCase;

class FichasYPaquetesTest extends TestCase
{
    use DatosDeInventario, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discoPublicoDePrueba();
    }

    private function pdf(string $nombre = 'ficha.pdf'): UploadedFile
    {
        return UploadedFile::fake()->create($nombre, 120, 'application/pdf');
    }

    // ===================== Fichas técnicas =====================

    public function test_alta_de_ficha_con_pdf_ligada_a_un_producto(): void
    {
        $producto = $this->productoConPiezas();
        $user = $this->usuarioApi(['inventario.registrar']);

        $res = $this->conToken($user)->post('/api/v1/fichas-tecnicas', [
            'titulo' => 'Ficha de Torre CV-190',
            'producto_id' => $producto->id,
            'archivo' => $this->pdf(),
        ]);

        $res->assertCreated()
            ->assertJsonPath('message', 'Ficha técnica guardada correctamente.')
            ->assertJsonPath('data.producto_id', $producto->id)
            ->assertJsonPath('data.paquete_id', null)
            ->assertJsonPath('data.activo', true)
            ->assertJsonPath('data.producto.id', $producto->id);

        $ficha = FichaTecnica::firstOrFail();
        Storage::disk('public')->assertExists($ficha->archivo);
        $this->assertStringStartsWith('fichas/', $ficha->archivo);
        $this->assertNotNull($res->json('data.archivo_url'));

        // Un producto con ficha no acepta otra.
        $this->conToken($user)->post('/api/v1/fichas-tecnicas', [
            'titulo' => 'Otra',
            'producto_id' => $producto->id,
            'archivo' => $this->pdf(),
        ])->assertStatus(422)
            ->assertJsonPath('errors.producto_id.0', 'Ese producto ya tiene otra ficha técnica asignada.');
    }

    public function test_validaciones_de_la_ficha(): void
    {
        $producto = $this->productoConPiezas();
        $paquete = Paquete::create(['nombre' => 'Paquete']);
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->post('/api/v1/fichas-tecnicas', ['titulo' => 'Sin PDF'])
            ->assertStatus(422)->assertJsonPath('errors.archivo.0', 'Sube el PDF de la ficha técnica.');

        $this->conToken($user)->post('/api/v1/fichas-tecnicas', [
            'titulo' => 'No es PDF',
            'archivo' => UploadedFile::fake()->create('foto.jpg', 10, 'image/jpeg'),
        ])->assertStatus(422)->assertJsonPath('errors.archivo.0', 'El archivo debe ser un PDF.');

        $this->conToken($user)->post('/api/v1/fichas-tecnicas', [
            'titulo' => 'A los dos',
            'producto_id' => $producto->id,
            'paquete_id' => $paquete->id,
            'archivo' => $this->pdf(),
        ])->assertStatus(422)->assertJsonValidationErrors('producto_id');

        $this->assertDatabaseCount('fichas_tecnicas', 0);
    }

    public function test_editar_listar_descargar_y_borrar_ficha(): void
    {
        $paquete = Paquete::create(['nombre' => 'Paquete endoscopia']);
        $user = $this->usuarioApi(['inventario.ver', 'inventario.registrar', 'inventario.editar', 'inventario.eliminar']);

        $this->conToken($user)->post('/api/v1/fichas-tecnicas', [
            'titulo' => 'Ficha del paquete',
            'paquete_id' => $paquete->id,
            'activo' => false,
            'archivo' => $this->pdf(),
        ])->assertCreated()->assertJsonPath('data.activo', false);

        $ficha = FichaTecnica::firstOrFail();
        $archivoViejo = $ficha->archivo;

        // Sin PDF nuevo se conserva; lo que no se manda de la liga, también.
        $this->conToken($user)->putJson("/api/v1/fichas-tecnicas/{$ficha->id}", ['titulo' => 'Ficha renombrada'])
            ->assertOk()
            ->assertJsonPath('message', 'Ficha técnica actualizada correctamente.')
            ->assertJsonPath('data.titulo', 'Ficha renombrada')
            ->assertJsonPath('data.paquete_id', $paquete->id)
            ->assertJsonPath('data.activo', false);
        Storage::disk('public')->assertExists($archivoViejo);

        // Con PDF nuevo (multipart con _method=PUT) se reemplaza y se borra el viejo.
        $this->conToken($user)->post("/api/v1/fichas-tecnicas/{$ficha->id}", [
            '_method' => 'PUT',
            'titulo' => 'Ficha renombrada',
            'archivo' => $this->pdf('nueva.pdf'),
        ])->assertOk();
        Storage::disk('public')->assertMissing($archivoViejo);

        $this->conToken($user)->getJson('/api/v1/fichas-tecnicas?buscar=renombrada')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.paquete.nombre', 'Paquete endoscopia')
            ->assertJsonPath('resumen', ['total' => 1, 'con_pdf' => 1]);

        $this->conToken($user)->getJson('/api/v1/fichas-tecnicas?activo=1')->assertOk()->assertJsonCount(0, 'data');

        $this->conToken($user)->get("/api/v1/fichas-tecnicas/{$ficha->id}/descargar")
            ->assertOk()->assertDownload('ficha-renombrada.pdf');

        $this->conToken($user)->deleteJson("/api/v1/fichas-tecnicas/{$ficha->id}")
            ->assertOk()->assertJsonPath('message', 'Ficha técnica eliminada correctamente.');

        $this->assertDatabaseCount('fichas_tecnicas', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('fichas'));
    }

    public function test_la_liga_tambien_acepta_item_como_el_select_de_la_web(): void
    {
        $paquete = Paquete::create(['nombre' => 'Paquete']);
        $user = $this->usuarioApi(['inventario.registrar', 'inventario.editar']);

        $this->conToken($user)->post('/api/v1/fichas-tecnicas', [
            'titulo' => 'Por item',
            'item' => "paquete:{$paquete->id}",
            'archivo' => $this->pdf(),
        ])->assertCreated()->assertJsonPath('data.paquete_id', $paquete->id);

        $ficha = FichaTecnica::firstOrFail();

        // Vacío la deja sin ligar, igual que en la web.
        $this->conToken($user)->putJson("/api/v1/fichas-tecnicas/{$ficha->id}", ['titulo' => 'Por item', 'item' => ''])
            ->assertOk()->assertJsonPath('data.paquete_id', null)->assertJsonPath('data.producto_id', null);
    }

    public function test_descargar_una_ficha_sin_pdf_da_404(): void
    {
        $ficha = FichaTecnica::create(['titulo' => 'Sin archivo', 'activo' => true]);

        $this->conToken($this->usuarioApi(['inventario.ver']))
            ->getJson("/api/v1/fichas-tecnicas/{$ficha->id}/descargar")
            ->assertNotFound()
            ->assertJsonPath('message', 'Esta ficha técnica no tiene su PDF en el servidor.');
    }

    public function test_permisos_de_fichas(): void
    {
        $ficha = FichaTecnica::create(['titulo' => 'Ficha', 'activo' => true]);
        $soloVer = $this->usuarioApi(['inventario.ver']);

        $this->conToken($soloVer)->getJson("/api/v1/fichas-tecnicas/{$ficha->id}")->assertOk()->assertJsonPath('data.titulo', 'Ficha');
        $this->conToken($soloVer)->putJson("/api/v1/fichas-tecnicas/{$ficha->id}", ['titulo' => 'X'])->assertForbidden();
        $this->conToken($soloVer)->deleteJson("/api/v1/fichas-tecnicas/{$ficha->id}")->assertForbidden();
        $this->conToken($this->usuarioApi())->getJson('/api/v1/fichas-tecnicas')->assertForbidden();
    }

    // ===================== Paquetes =====================

    public function test_armar_editar_y_borrar_un_paquete(): void
    {
        $a = $this->productoConPiezas(['A-1']);
        $b = $this->productoConPiezas(['B-1'], ['modelo' => 'X2']);
        $user = $this->usuarioApi(['inventario.ver', 'inventario.registrar', 'inventario.editar', 'inventario.eliminar']);

        $res = $this->conToken($user)->postJson('/api/v1/paquetes', [
            'nombre' => 'Torre completa',
            'productos' => [['id' => $a->id, 'cantidad' => 2], ['id' => $b->id, 'cantidad' => 1]],
        ])->assertCreated()
            ->assertJsonPath('message', 'Paquete guardado correctamente.')
            ->assertJsonCount(2, 'data.productos');

        $paquete = Paquete::firstOrFail();
        $this->assertSame(2, (int) collect($res->json('data.productos'))->firstWhere('id', $a->id)['cantidad']);

        $this->conToken($user)->putJson("/api/v1/paquetes/{$paquete->id}", [
            'nombre' => 'Torre sin monitor',
            'productos' => [['id' => $b->id, 'cantidad' => 3]],
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Torre sin monitor')
            ->assertJsonCount(1, 'data.productos')
            ->assertJsonPath('data.productos.0.cantidad', 3);

        $this->assertDatabaseHas('paquete_producto', ['paquete_id' => $paquete->id, 'producto_id' => $b->id, 'cantidad' => 3]);
        $this->assertDatabaseMissing('paquete_producto', ['paquete_id' => $paquete->id, 'producto_id' => $a->id]);

        $this->conToken($user)->getJson('/api/v1/paquetes?buscar=monitor')->assertOk()->assertJsonCount(1, 'data');
        $this->conToken($user)->getJson("/api/v1/paquetes/{$paquete->id}")->assertOk()->assertJsonPath('data.id', $paquete->id);

        $this->conToken($user)->deleteJson("/api/v1/paquetes/{$paquete->id}")
            ->assertOk()->assertJsonPath('message', 'Paquete eliminado correctamente.');
        $this->assertDatabaseCount('paquetes', 0);
        $this->assertDatabaseCount('productos', 2);
    }

    public function test_paquete_sin_productos_o_con_producto_inexistente_da_422(): void
    {
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->postJson('/api/v1/paquetes', ['nombre' => 'Vacío'])
            ->assertStatus(422)->assertJsonValidationErrors('productos');

        $this->conToken($user)->postJson('/api/v1/paquetes', [
            'nombre' => 'Fantasma',
            'productos' => [['id' => 999, 'cantidad' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('productos.0.id');

        $this->assertDatabaseCount('paquetes', 0);
    }

    public function test_precios_de_paquetes_y_sus_productos_se_esconden(): void
    {
        $a = $this->productoConPiezas(['A-1']);
        $paquete = Paquete::create(['nombre' => 'Con precio', 'precio' => 5000]);
        $paquete->productos()->sync([$a->id => ['cantidad' => 1]]);

        $res = $this->conToken($this->usuarioApi(['inventario.ver']))->getJson('/api/v1/paquetes')->assertOk();
        $this->assertArrayNotHasKey('precio', $res->json('data.0'));
        $this->assertArrayNotHasKey('precio', $res->json('data.0.productos.0'));

        $this->conToken($this->usuarioApi(['inventario.ver', 'precios.ver']))->getJson("/api/v1/paquetes/{$paquete->id}")
            ->assertOk()
            ->assertJsonPath('data.precio', 5000)
            ->assertJsonPath('data.productos.0.precio', 1500);

        // Una llave de integración sin precios.ver tampoco los ve.
        $admin = $this->usuarioApi(admin: true);
        $res = $this->conToken($admin, ['inventario.ver'], TokenApi::TIPO_INTEGRACION)->getJson("/api/v1/paquetes/{$paquete->id}")->assertOk();
        $this->assertArrayNotHasKey('precio', $res->json('data'));
    }
}
