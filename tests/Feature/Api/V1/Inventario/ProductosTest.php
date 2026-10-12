<?php

namespace Tests\Feature\Api\V1\Inventario;

use App\Models\FichaTecnica;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Inventario\Concerns\DatosDeInventario;
use Tests\TestCase;

class ProductosTest extends TestCase
{
    use DatosDeInventario, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discoPublicoDePrueba();
    }

    public function test_listado_esconde_el_precio_sin_precios_ver(): void
    {
        $producto = $this->productoConPiezas();

        $sinPrecio = $this->usuarioApi(['inventario.ver']);
        $res = $this->conToken($sinPrecio)->getJson('/api/v1/productos')->assertOk()
            ->assertJsonPath('data.0.id', $producto->id)
            ->assertJsonPath('data.0.stock', 2);
        $this->assertArrayNotHasKey('precio', $res->json('data.0'));

        // Ordenar por precio también lo revelaría: se ignora.
        $this->conToken($sinPrecio)->getJson('/api/v1/productos?orden=precio')->assertOk();

        $conPrecio = $this->usuarioApi(['inventario.ver', 'precios.ver']);
        $this->conToken($conPrecio)->getJson('/api/v1/productos')->assertOk()
            ->assertJsonPath('data.0.precio', 1500);
    }

    public function test_la_llave_de_integracion_recorta_precios_aunque_el_dueno_sea_admin(): void
    {
        $this->productoConPiezas();
        $admin = $this->usuarioApi(admin: true);

        $res = $this->conToken($admin, ['inventario.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/productos')->assertOk();
        $this->assertArrayNotHasKey('precio', $res->json('data.0'));

        $this->conToken($admin, ['inventario.ver'], TokenApi::TIPO_INTEGRACION)
            ->postJson('/api/v1/productos', ['equipment_type_id' => 1, 'stock' => 1])
            ->assertForbidden();

        $this->conToken($admin)->getJson('/api/v1/productos')->assertOk()->assertJsonPath('data.0.precio', 1500);
    }

    public function test_filtros_del_listado(): void
    {
        $this->productoConPiezas(['AAA-1'], ['marca' => 'Pentax', 'modelo' => 'EG-29']);
        $olympus = $this->productoConPiezas(['BBB-1']);
        $user = $this->usuarioApi(['inventario.ver']);

        $this->conToken($user)->getJson('/api/v1/productos?buscar=pentax')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.marca', 'Pentax');

        $this->conToken($user)->getJson('/api/v1/productos?buscar=BBB-1&con_piezas=1')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.piezas_disponibles.0.no_serie', 'BBB-1');

        $this->conToken($user)->getJson('/api/v1/productos?ubicacion=congreso')
            ->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('resumen.piezas_en_congreso', 0);

        FichaTecnica::create(['titulo' => 'Ficha', 'producto_id' => $olympus->id, 'activo' => true]);
        $this->conToken($user)->getJson('/api/v1/productos?sin_ficha=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.marca', 'Pentax');
    }

    public function test_alta_con_series_en_secuencia_y_sin_precio_si_no_puede_editarlo(): void
    {
        $cat = $this->catalogo();
        $user = $this->usuarioApi(['inventario.registrar']);

        $res = $this->conToken($user)->postJson('/api/v1/productos', [
            'equipment_type_id' => $cat['tipo']->id,
            'subtype_id' => $cat['subtipo']->id,
            'brand_id' => $cat['marca']->id,
            'equipment_model_id' => $cat['modelo']->id,
            'precio' => 9999,
            'stock' => 3,
            'series' => ['SER-0007'],
        ]);

        $res->assertCreated()
            ->assertJsonPath('message', 'Producto guardado correctamente.')
            ->assertJsonPath('data.stock', 3)
            ->assertJsonPath('data.marca', 'OLYMPUS');

        $producto = Producto::firstOrFail();
        $this->assertNull($producto->precio);
        $this->assertSame(['SER-0007', 'SER-0008', 'SER-0009'], $producto->seriales()->orderBy('no_serie')->pluck('no_serie')->all());

        // El mismo modelo otra vez: se suma a la fila que ya existe.
        $this->conToken($user)->postJson('/api/v1/productos', [
            'equipment_type_id' => $cat['tipo']->id,
            'equipment_model_id' => $cat['modelo']->id,
            'stock' => 2,
        ])->assertCreated()
            ->assertJsonPath('data.id', $producto->id)
            ->assertJsonPath('data.stock', 5)
            ->assertJsonPath('message', 'Se agregaron 2 unidad(es) al stock existente de OLYMPUS CV-190.');

        $this->assertDatabaseCount('productos', 1);
    }

    public function test_alta_con_precio_por_quien_puede_editarlo_e_imagen(): void
    {
        $cat = $this->catalogo();
        $user = $this->usuarioApi(['inventario.registrar', 'precios.editar', 'precios.ver']);

        $this->conToken($user)->post('/api/v1/productos', [
            'equipment_type_id' => $cat['tipo']->id,
            'precio' => 1234.5,
            'stock' => 0,
            'imagen' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertCreated()->assertJsonPath('data.precio', 1234.5);

        $producto = Producto::firstOrFail();
        $this->assertEquals(1234.5, $producto->precio);
        $this->assertNotNull($producto->imagen_path);
    }

    public function test_alta_con_series_que_no_cuadran_da_422(): void
    {
        $cat = $this->catalogo();
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->postJson('/api/v1/productos', [
            'equipment_type_id' => $cat['tipo']->id,
            'stock' => 3,
            'series_texto' => "A\nB",
        ])->assertStatus(422)
            ->assertJsonPath('errors.series_texto.0', 'Capturaste 2 número(s) de serie, pero el stock dice 3. Deben coincidir, o deja el campo vacío si no vas a registrar series.');

        $this->conToken($user)->postJson('/api/v1/productos', ['stock' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('equipment_type_id');

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_detalle_es_el_kardex(): void
    {
        $producto = $this->productoConPiezas(['K-1', 'K-2', 'K-3']);
        ProductoSerial::where('no_serie', 'K-1')->update(['vendido' => true]);
        ProductoSerial::where('no_serie', 'K-2')->update(['estado' => 'mantenimiento']);
        $producto->recalcularStock();

        $this->conToken($this->usuarioApi(['inventario.ver']))
            ->getJson("/api/v1/productos/{$producto->id}")
            ->assertOk()
            ->assertJsonPath('data.stock', 1)
            ->assertJsonPath('data.conteos', ['total' => 3, 'disponibles' => 1, 'en_proceso' => 1, 'vendidas' => 1])
            ->assertJsonCount(3, 'data.piezas')
            ->assertJsonPath('data.piezas.0.no_serie', 'K-3')
            ->assertJsonPath('data.movimientos', []);
    }

    public function test_editar_conserva_el_precio_sin_permiso_y_cambia_imagen_por_post_con_method(): void
    {
        $cat = $this->catalogo();
        $producto = $this->productoConPiezas(['E-1'], ['equipment_type_id' => $cat['tipo']->id]);
        $user = $this->usuarioApi(['inventario.editar']);

        $this->conToken($user)->post("/api/v1/productos/{$producto->id}", [
            '_method' => 'PUT',
            'equipment_type_id' => $cat['tipo']->id,
            'descripcion' => 'Torre completa',
            'precio' => 1,
            'imagen' => UploadedFile::fake()->image('nueva.jpg'),
        ])->assertOk()
            ->assertJsonPath('message', 'Producto actualizado correctamente.')
            ->assertJsonPath('data.descripcion', 'Torre completa');

        $producto->refresh();
        $this->assertEquals(1500, $producto->precio);
        $this->assertNotNull($producto->imagen_path);
        $this->assertSame(1, $producto->stock);

        $this->conToken($this->usuarioApi(['inventario.ver']))
            ->putJson("/api/v1/productos/{$producto->id}", ['equipment_type_id' => $cat['tipo']->id])
            ->assertForbidden();
    }

    public function test_eliminar_producto(): void
    {
        $producto = $this->productoConPiezas();

        $this->conToken($this->usuarioApi(['inventario.editar']))
            ->deleteJson("/api/v1/productos/{$producto->id}")->assertForbidden();

        $this->conToken($this->usuarioApi(['inventario.eliminar']))
            ->deleteJson("/api/v1/productos/{$producto->id}")
            ->assertOk()->assertJsonPath('message', 'Producto eliminado correctamente.');

        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('producto_seriales', 0);
    }

    public function test_buscar_por_modelo_y_generar_series(): void
    {
        $cat = $this->catalogo();
        $producto = $this->productoConPiezas(['23A0099'], ['equipment_model_id' => $cat['modelo']->id]);

        $user = $this->usuarioApi(['inventario.ver', 'inventario.registrar']);

        $res = $this->conToken($user)->getJson('/api/v1/productos/buscar-por-modelo?equipment_model_id='.$cat['modelo']->id)
            ->assertOk()
            ->assertJsonPath('data.existe', true)
            ->assertJsonPath('data.tiene_precio', true)
            ->assertJsonPath('data.pide_precio', false)
            ->assertJsonPath('data.no_serie_sugerido', '23A0100')
            ->assertJsonPath('data.producto.id', $producto->id);
        $this->assertArrayNotHasKey('precio', $res->json('data.producto'));

        $this->conToken($user)->getJson('/api/v1/productos/buscar-por-modelo?equipment_model_id=999')
            ->assertStatus(422);

        $this->conToken($user)->postJson('/api/v1/productos/generar-series', [
            'equipment_type_id' => $cat['tipo']->id,
            'equipment_model_id' => $cat['modelo']->id,
            'cantidad' => 2,
        ])->assertOk()
            ->assertJsonPath('data.prefijo', 'TOR-CV190')
            ->assertJsonPath('data.series', ['TOR-CV190-0001', 'TOR-CV190-0002']);
    }

    public function test_catalogos_piden_registrar_o_editar(): void
    {
        $this->catalogo();

        $this->conToken($this->usuarioApi(['inventario.ver']))->getJson('/api/v1/productos/catalogos')->assertForbidden();

        $this->conToken($this->usuarioApi(['inventario.editar']))->getJson('/api/v1/productos/catalogos')
            ->assertOk()
            ->assertJsonPath('data.ve_precio', false)
            ->assertJsonStructure(['data' => ['catalogo' => ['tipos', 'subtipos', 'marcas', 'modelos'], 'opciones' => ['marca', 'subtypes_by_type']]]);
    }

    public function test_agregar_piezas_a_un_producto(): void
    {
        $producto = $this->productoConPiezas(['P-1']);
        $user = $this->usuarioApi(['inventario.editar']);

        $this->conToken($user)->postJson("/api/v1/productos/{$producto->id}/seriales", [
            'cantidad' => 2,
            'series_texto' => 'P-2, P-3',
        ])->assertCreated()
            ->assertJsonPath('message', 'Se agregaron 2 unidad(es) al inventario.')
            ->assertJsonPath('data.stock', 3)
            ->assertJsonCount(3, 'data.piezas');

        // Una serie que ya existe para el producto: 422, no se crea nada.
        $this->conToken($user)->postJson("/api/v1/productos/{$producto->id}/seriales", [
            'cantidad' => 1,
            'series' => ['P-1'],
        ])->assertStatus(422)
            ->assertJsonPath('errors.series_texto.0', 'Uno de esos números de serie ya existe para este producto. Revísalos y vuelve a intentar.');

        $this->assertSame(3, $producto->fresh()->stock);
    }

    public function test_corregir_una_pieza_pide_pin_y_respeta_la_serie_si_no_se_manda(): void
    {
        $producto = $this->productoConPiezas(['C-1', 'C-2']);
        $pieza = $producto->seriales()->where('no_serie', 'C-1')->firstOrFail();
        $user = $this->usuarioApi(['inventario.editar']);
        $url = "/api/v1/productos/{$producto->id}/seriales/{$pieza->id}";

        $this->conToken($user)->putJson($url, ['password' => 'mala', 'no_serie' => 'NUEVA'])
            ->assertStatus(422)->assertJsonPath('errors.password.0', 'PIN o contraseña incorrecta.');

        $this->conToken($user)->putJson($url, ['password' => 'password', 'no_serie' => 'C-2'])
            ->assertStatus(422)->assertJsonPath('errors.no_serie.0', 'Ese número de serie ya existe para este producto.');

        $this->conToken($user)->post($url, [
            '_method' => 'PUT',
            'password' => 'password',
            'foto' => UploadedFile::fake()->image('pieza.jpg'),
        ])->assertOk()
            ->assertJsonPath('message', 'Unidad actualizada correctamente.')
            ->assertJsonPath('data.no_serie', 'C-1');

        $pieza->refresh();
        $this->assertNotNull($pieza->foto_path);
        $this->assertSame($user->id, $pieza->editado_por);

        $this->conToken($user)->putJson($url, ['password' => 'password', 'no_serie' => 'C-9'])
            ->assertOk()->assertJsonPath('data.no_serie', 'C-9');
        $this->assertStringContainsString('C-9', $producto->fresh()->no_serie);
    }

    public function test_quitar_una_pieza(): void
    {
        $producto = $this->productoConPiezas(['Q-1', 'Q-2']);
        $otro = $this->productoConPiezas(['Z-1']);
        [$vendida, $libre] = $producto->seriales()->orderBy('no_serie')->get()->all();
        $vendida->update(['vendido' => true]);
        $user = $this->usuarioApi(['inventario.eliminar']);

        $this->conToken($user)->deleteJson("/api/v1/productos/{$producto->id}/seriales/{$vendida->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Esa unidad ya se vendió, no se puede eliminar del inventario.');

        // La pieza tiene que ser de ese producto.
        $this->conToken($user)->deleteJson("/api/v1/productos/{$otro->id}/seriales/{$libre->id}")
            ->assertNotFound();

        $this->conToken($user)->deleteJson("/api/v1/productos/{$producto->id}/seriales/{$libre->id}")
            ->assertOk()->assertJsonPath('message', 'Unidad eliminada del inventario.');

        $this->assertModelMissing($libre);
        $this->assertSame(0, $producto->fresh()->stock);
    }
}
