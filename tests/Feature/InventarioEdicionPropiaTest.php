<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\TokenApi;
use App\Support\EdicionPropia;
use App\Support\GeneradorDeSeries;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Inventario\Concerns\DatosDeInventario;
use Tests\TestCase;

/**
 * Lo que pidió almacén para el inventario:
 *
 *  - quien registra tiene 8 horas para corregir o borrar lo suyo, sin
 *    permiso de editar; después, solo quien tiene el permiso;
 *  - corregir una pieza ya no pide PIN ni contraseña;
 *  - las fotos de una pieza se suman (hasta 3), no se reemplazan;
 *  - muchas series se corrigen de una vez;
 *  - referencia opcional del producto;
 *  - la serie generada ya no lleva el tipo al frente.
 */
class InventarioEdicionPropiaTest extends TestCase
{
    use DatosDeInventario, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();

        // Disco propio: Storage::fake('public') lo comparten todos los
        // procesos de pruebas y uno le borra los archivos al otro.
        $raiz = storage_path('framework/testing/disks/public-edicion-propia');
        (new Filesystem)->cleanDirectory($raiz);
        Storage::set('public', Storage::createLocalDriver(['root' => $raiz, 'url' => '/storage']));
    }

    /** Un almacenista: registra entradas, pero no edita ni borra inventario. */
    private function almacenista()
    {
        return $this->usuarioApi(['inventario.ver', 'inventario.registrar']);
    }

    private function registrarEntrada($user, array $extra = []): InventoryMovement
    {
        $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosEntrada($extra))
            ->assertRedirect(route('inventory.movimientos.index'));

        return InventoryMovement::latest('id')->firstOrFail();
    }

    // ------------------------------------------------------------------
    // Plazo de 8 horas
    // ------------------------------------------------------------------

    public function test_quien_registra_corrige_su_producto_dentro_de_8_horas_y_despues_ya_no(): void
    {
        $yo = $this->almacenista();
        $this->registrarEntrada($yo);

        $producto = Producto::firstOrFail();
        $pieza = $producto->seriales()->firstOrFail();
        $this->assertSame($yo->id, (int) $producto->created_by);

        $this->actingAs($yo)->get(route('inventory.productos.edit', $producto))
            ->assertOk()
            ->assertSee('Tú registraste este producto')
            ->assertSee('Buscar por serie');

        $this->actingAs($yo)->put(route('inventory.productos.seriales.update', $pieza), ['no_serie' => 'MIA-1'])
            ->assertSessionHasNoErrors();
        $this->assertSame('MIA-1', $pieza->fresh()->no_serie);

        // Pasan las 8 horas.
        $this->travel(EdicionPropia::HORAS * 60 + 1)->minutes();

        $this->actingAs($yo)->get(route('inventory.productos.edit', $producto))->assertForbidden();
        $this->actingAs($yo)->put(route('inventory.productos.seriales.update', $pieza), ['no_serie' => 'TARDE'])
            ->assertForbidden();
        $this->actingAs($yo)->delete(route('inventory.productos.destroy', $producto))->assertForbidden();
        $this->assertSame('MIA-1', $pieza->fresh()->no_serie);

        // Quien tiene el permiso sigue pudiendo, sin plazo.
        $editor = $this->usuarioApi(['inventario.ver', 'inventario.editar']);
        $this->actingAs($editor)->put(route('inventory.productos.seriales.update', $pieza), ['no_serie' => 'EDITOR'])
            ->assertSessionHasNoErrors();
        $this->assertSame('EDITOR', $pieza->fresh()->no_serie);
    }

    public function test_otro_usuario_sin_permiso_no_usa_el_plazo_ajeno(): void
    {
        $this->registrarEntrada($this->almacenista());
        $producto = Producto::firstOrFail();

        $otro = $this->almacenista();

        $this->actingAs($otro)->get(route('inventory.productos.edit', $producto))->assertForbidden();
        $this->actingAs($otro)->put(route('inventory.productos.seriales.update', $producto->seriales()->first()), ['no_serie' => 'X'])
            ->assertForbidden();
    }

    public function test_quien_registra_borra_su_entrada_dentro_del_plazo_sin_pin(): void
    {
        $yo = $this->almacenista();
        $movimiento = $this->registrarEntrada($yo);

        // Antes pedía PIN; ahora basta con ser quien la registró (o tener permiso).
        $this->actingAs($yo)->delete(route('inventory.movimientos.destroy', $movimiento))
            ->assertRedirect(route('inventory.movimientos.index'));

        $this->assertSoftDeleted($movimiento);
        $this->assertDatabaseCount('producto_seriales', 0);
    }

    public function test_pasado_el_plazo_la_entrada_solo_la_borra_quien_tiene_permiso(): void
    {
        $yo = $this->almacenista();
        $movimiento = $this->registrarEntrada($yo);

        $this->travel(EdicionPropia::HORAS + 1)->hours();

        $this->actingAs($yo)->delete(route('inventory.movimientos.destroy', $movimiento))->assertForbidden();
        $this->assertNotSoftDeleted($movimiento);

        $this->actingAs($this->usuarioApi(['inventario.eliminar']))
            ->delete(route('inventory.movimientos.destroy', $movimiento))
            ->assertRedirect(route('inventory.movimientos.index'));
        $this->assertSoftDeleted($movimiento);
    }

    public function test_los_productos_de_antes_no_tienen_plazo_propio(): void
    {
        // Sin created_by: se registró antes de que existiera la regla.
        $producto = $this->productoConPiezas(['VIEJA-1']);
        $producto->forceFill(['created_by' => null])->save();
        ProductoSerial::query()->update(['capturado_por' => null]);

        $yo = $this->almacenista();

        $this->actingAs($yo)->get(route('inventory.productos.edit', $producto))->assertForbidden();
    }

    public function test_por_la_api_el_plazo_exige_que_la_llave_traiga_registrar(): void
    {
        $yo = $this->almacenista();
        $this->registrarEntrada($yo);
        $producto = Producto::firstOrFail();
        $pieza = $producto->seriales()->firstOrFail();
        $url = "/api/v1/productos/{$producto->id}/seriales/{$pieza->id}";

        $this->conToken($yo)->putJson($url, ['no_serie' => 'APP-1'])
            ->assertOk()
            ->assertJsonPath('data.no_serie', 'APP-1')
            ->assertJsonPath('data.puede_editar', true);

        // Una llave de integración recortada a solo ver no hereda el plazo.
        $this->conToken($yo, ['inventario.ver'], TokenApi::TIPO_INTEGRACION)
            ->putJson($url, ['no_serie' => 'LLAVE'])
            ->assertForbidden();

        $this->conToken($yo)->getJson("/api/v1/productos/{$producto->id}")
            ->assertJsonPath('data.puede_editar', true)
            ->assertJsonPath('data.puede_eliminar', true);
    }

    // ------------------------------------------------------------------
    // Fotos de una pieza: se suman, máximo 3
    // ------------------------------------------------------------------

    public function test_las_fotos_de_una_pieza_se_suman_hasta_tres(): void
    {
        $editor = $this->usuarioApi(['inventario.ver', 'inventario.registrar', 'inventario.editar']);
        $this->registrarEntrada($editor);
        $pieza = ProductoSerial::firstOrFail();
        $url = route('inventory.productos.seriales.update', $pieza);

        $this->assertCount(1, $pieza->evidence_paths);

        // Una foto nueva se suma a la que ya tenía; la serie no se toca.
        $pieza->update(['no_serie' => 'CONSERVA']);
        $this->actingAs($editor)->put($url, ['fotos' => [UploadedFile::fake()->image('b.jpg')]])
            ->assertSessionHasNoErrors();
        $pieza->refresh();
        $this->assertCount(2, $pieza->evidence_paths);
        $this->assertSame('CONSERVA', $pieza->no_serie);

        // Dos más ya no caben (2 + 2 > 3).
        $this->actingAs($editor)->put($url, ['fotos' => [UploadedFile::fake()->image('c.jpg'), UploadedFile::fake()->image('d.jpg')]])
            ->assertSessionHasErrors('fotos');
        $this->assertCount(2, $pieza->fresh()->evidence_paths);

        // Quitando una, sí caben dos.
        $vieja = $pieza->evidence_paths[0];
        $this->actingAs($editor)->put($url, [
            'quitar_fotos' => [$vieja],
            'fotos' => [UploadedFile::fake()->image('c.jpg'), UploadedFile::fake()->image('d.jpg')],
        ])->assertSessionHasNoErrors();

        $pieza->refresh();
        $this->assertCount(3, $pieza->evidence_paths);
        $this->assertNotContains($vieja, $pieza->evidence_paths);
        $this->assertSame($pieza->evidence_paths[0], $pieza->foto_path);
        Storage::disk('public')->assertMissing($vieja);
    }

    public function test_quitar_fotos_no_borra_archivos_ajenos(): void
    {
        $editor = $this->usuarioApi(['inventario.ver', 'inventario.editar']);
        Storage::disk('public')->put('avatars/ajeno.jpg', 'x');
        $pieza = $this->productoConPiezas(['F-1'])->seriales()->firstOrFail();

        $this->actingAs($editor)->put(route('inventory.productos.seriales.update', $pieza), [
            'quitar_fotos' => ['avatars/ajeno.jpg'],
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists('avatars/ajeno.jpg');
    }

    // ------------------------------------------------------------------
    // Series en lote
    // ------------------------------------------------------------------

    public function test_series_en_lote_se_guardan_juntas_y_permiten_intercambiar(): void
    {
        $editor = $this->usuarioApi(['inventario.ver', 'inventario.editar']);
        $producto = $this->productoConPiezas(['A', 'B', null]);
        [$a, $b, $c] = $producto->seriales()->orderBy('id')->get()->all();

        $this->actingAs($editor)->put(route('inventory.productos.seriales.lote', $producto), [
            'series' => [$a->id => 'B', $b->id => 'A', $c->id => 'C'],
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Se guardaron 3 número(s) de serie.');

        $this->assertSame(['B', 'A', 'C'], $producto->seriales()->orderBy('id')->pluck('no_serie')->all());
        $this->assertStringContainsString('C', $producto->fresh()->no_serie);
    }

    public function test_series_en_lote_rechaza_repetidas_sin_guardar_nada(): void
    {
        $editor = $this->usuarioApi(['inventario.ver', 'inventario.editar']);
        $producto = $this->productoConPiezas(['A', 'B', 'C']);
        [$a, $b] = $producto->seriales()->orderBy('id')->get()->all();

        $this->actingAs($editor)->put(route('inventory.productos.seriales.lote', $producto), [
            'series' => [$a->id => 'NUEVA', $b->id => 'C'],
        ])->assertSessionHasErrors('series');

        $this->assertSame(['A', 'B', 'C'], $producto->seriales()->orderBy('id')->pluck('no_serie')->all());
    }

    public function test_series_en_lote_por_la_api_y_piezas_ajenas(): void
    {
        $editor = $this->usuarioApi(['inventario.ver', 'inventario.editar']);
        $producto = $this->productoConPiezas(['A', 'B']);
        $otro = $this->productoConPiezas(['Z'], ['modelo' => 'OTRO']);
        [$a] = $producto->seriales()->orderBy('id')->get()->all();

        $this->conToken($editor)->putJson("/api/v1/productos/{$producto->id}/seriales", [
            'series' => [$a->id => 'A-1'],
        ])->assertOk()->assertJsonPath('cambiadas', 1);

        $this->conToken($editor)->putJson("/api/v1/productos/{$producto->id}/seriales", [
            'series' => [$otro->seriales()->first()->id => 'INTRUSA'],
        ])->assertStatus(422);

        // Sin permiso ni plazo propio: 403.
        $this->conToken($this->almacenista())->putJson("/api/v1/productos/{$producto->id}/seriales", [
            'series' => [$a->id => 'NO'],
        ])->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Referencia y series generadas
    // ------------------------------------------------------------------

    public function test_la_referencia_se_guarda_se_busca_y_se_completa_por_modelo(): void
    {
        $yo = $this->usuarioApi(['inventario.ver', 'inventario.registrar']);
        $this->registrarEntrada($yo, ['referencia' => 'REF 1588-010-122']);

        $producto = Producto::firstOrFail();
        $this->assertSame('REF 1588-010-122', $producto->referencia);

        // Una segunda entrada sin referencia no la borra.
        $this->registrarEntrada($yo);
        $this->assertSame('REF 1588-010-122', $producto->fresh()->referencia);

        $this->actingAs($yo)->get(route('inventory.productos.index', ['search' => '1588-010']))
            ->assertOk()->assertSee('Ref. REF 1588-010-122');

        $this->actingAs($yo)->getJson(route('inventory.productos.buscarPorModelo', ['equipment_model_id' => $producto->equipment_model_id]))
            ->assertJsonPath('referencia', 'REF 1588-010-122');

        $this->conToken($yo)->getJson("/api/v1/productos/{$producto->id}")
            ->assertJsonPath('data.referencia', 'REF 1588-010-122');
    }

    public function test_la_referencia_es_opcional_en_el_alta_directa(): void
    {
        $yo = $this->usuarioApi(['inventario.ver', 'inventario.registrar']);
        $cat = $this->catalogo();

        $this->actingAs($yo)->post(route('inventory.productos.store'), [
            'equipment_type_id' => $cat['tipo']->id,
            'equipment_model_id' => $cat['modelo']->id,
            'stock' => 1,
        ])->assertSessionHasNoErrors();

        $this->actingAs($yo)->post(route('inventory.productos.store'), [
            'equipment_type_id' => $cat['tipo']->id,
            'stock' => 1,
            'referencia' => str_repeat('x', 121),
        ])->assertSessionHasErrors('referencia');
    }

    public function test_un_producto_aguanta_cien_piezas_con_serie(): void
    {
        // Antes el caché productos.no_serie era varchar(255): con unas 20
        // series tronaba "Data too long" y la entrada no se guardaba.
        $series = array_map(fn ($i) => 'SERIE-LARGA-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT), range(1, 100));

        $producto = $this->productoConPiezas($series);

        $this->assertSame(100, $producto->stock);
        $this->assertStringContainsString('SERIE-LARGA-00100', $producto->no_serie);
    }

    public function test_la_serie_generada_ya_no_lleva_el_tipo_al_frente(): void
    {
        $cat = $this->catalogo();

        $this->assertSame(
            'PRO-OLY-CV190',
            GeneradorDeSeries::prefijo($cat['tipo']->id, $cat['subtipo']->id, $cat['marca']->id, $cat['modelo']->id),
        );

        // Solo con el tipo no hay de dónde armarla: queda EQ.
        $this->assertSame('EQ', GeneradorDeSeries::prefijo($cat['tipo']->id, null, null, null));
    }
}
