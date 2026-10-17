<?php

namespace Tests\Feature\Api\V1\Inventario;

use App\Models\InventoryMovement;
use App\Models\PiezaProceso;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Inventario\Concerns\DatosDeInventario;
use Tests\TestCase;

class MovimientosTest extends TestCase
{
    use DatosDeInventario, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discoPublicoDePrueba();
    }

    public function test_registrar_entrada_crea_producto_piezas_evidencia_y_firma(): void
    {
        $user = $this->usuarioApi(['inventario.registrar', 'inventario.ver', 'precios.ver', 'precios.editar']);

        $res = $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada([
            'cantidad' => 3,
            'unidades' => [
                $this->unidad([
                    'no_serie' => '23A00001',
                    'evidencias' => [
                        UploadedFile::fake()->create('a.jpg', 50, 'image/jpeg'),
                        UploadedFile::fake()->create('b.jpg', 50, 'image/jpeg'),
                    ],
                ]),
                $this->unidad(),
                $this->unidad(),
            ],
        ]));

        $res->assertCreated()
            ->assertJsonPath('data.movement_type', 'entrada')
            ->assertJsonPath('data.quantity', 3)
            ->assertJsonPath('data.stock_before', 0)
            ->assertJsonPath('data.stock_after', 3)
            ->assertJsonPath('data.creator.id', $user->id)
            ->assertJsonCount(3, 'data.piezas')
            ->assertJsonPath('advertencias', []);

        $movimiento = InventoryMovement::firstOrFail();
        $res->assertJsonPath('message', "Entrada {$movimiento->folio} registrada correctamente.");
        $this->assertSame('ENT-'.now()->year.'-0001', $movimiento->folio);
        Storage::disk('public')->assertExists($movimiento->signature_path);
        $this->assertNotNull($res->json('data.signature_url'));

        $producto = Producto::firstOrFail();
        $this->assertSame(3, $producto->stock);
        $this->assertEquals(750, $producto->precio);
        $this->assertTrue($producto->es_serializado);

        // Cada pieza se queda con la serie que se le capturó: ya no se arma
        // una secuencia a partir de la primera.
        $this->assertSame(
            ['23A00001', null, null],
            ProductoSerial::orderBy('id')->pluck('no_serie')->all(),
        );

        $primera = ProductoSerial::where('no_serie', '23A00001')->first();
        $this->assertCount(2, $primera->evidence_paths);
        $this->assertSame($movimiento->id, $primera->inventory_movement_id);
        $this->assertNotNull($primera->codigo);
        $this->assertCount(2, collect($res->json('data.piezas'))->firstWhere('no_serie', '23A00001')['evidence_urls']);
    }

    public function test_entrada_sin_precios_editar_no_fija_precio(): void
    {
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada(['precio' => 999]))
            ->assertCreated();

        $this->assertNull(Producto::firstOrFail()->precio);
    }

    public function test_entrada_de_equipo_usado_arma_la_ruta_de_procesos(): void
    {
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada([
            'condicion' => 'usado',
            'estado_general' => 'regular',
            'checklist' => [
                'carcasa' => ['r' => 'no', 'nota' => 'Golpe en la tapa'],
                'enciende' => ['r' => 'si'],
                'inventado' => ['r' => 'si'],
            ],
            'procesos' => ['mantenimiento', 'hojalateria'],
        ]))->assertCreated()
            ->assertJsonPath('data.condicion', 'usado')
            ->assertJsonPath('data.estado_general', 'regular')
            ->assertJsonPath('data.piezas.0.estado', 'hojalateria')
            ->assertJsonPath('data.piezas.0.vendible', false);

        $movimiento = InventoryMovement::firstOrFail();
        // El checklist se limpia: lo que no es un punto conocido no se guarda.
        $this->assertSame(['carcasa', 'enciende'], array_keys($movimiento->checklist_recepcion));

        $pieza = ProductoSerial::firstOrFail();
        $this->assertSame(['hojalateria', 'mantenimiento'], PiezaProceso::where('producto_serial_id', $pieza->id)->orderBy('orden')->pluck('proceso')->all());
        // Una pieza en proceso no cuenta como stock vendible.
        $this->assertSame(0, Producto::firstOrFail()->stock);
    }

    public function test_equipo_usado_sin_checklist_da_422(): void
    {
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada(['condicion' => 'usado']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['estado_general', 'checklist']);
    }

    public function test_piezas_que_no_cuadran_con_la_cantidad_dan_422_y_no_guardan_nada(): void
    {
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada(['cantidad' => 2]))
            ->assertStatus(422)
            ->assertJsonPath('errors.unidades.0', 'Registraste 1 pieza(s), pero la cantidad dice 2. Debe haber una por cada unidad que llegó.');

        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_firma_invalida_da_422_y_limpia_la_evidencia(): void
    {
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada(['firma' => 'no-es-imagen']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('firma');

        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_pieza_sin_evidencia_da_422_con_el_mensaje_de_la_web(): void
    {
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada([
            'unidades' => [['no_serie' => 'X1']],
        ]))->assertStatus(422)
            ->assertJsonPath('errors', ['unidades.0.evidencias' => ['Cada pieza necesita al menos una foto de cómo llegó ella.']]);
    }

    public function test_serie_repetida_se_guarda_vacia_y_se_avisa(): void
    {
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada([
            'unidades' => [$this->unidad(['no_serie' => 'DUP-1'])],
        ]))->assertCreated();

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada([
            'unidades' => [$this->unidad(['no_serie' => 'DUP-1'])],
        ]))->assertCreated()
            ->assertJsonPath('advertencias.0', 'Estos números de serie ya existían para este producto y se guardaron sin serie (la evidencia y la unidad sí se conservaron): DUP-1.');

        // Las dos entradas cayeron en el mismo producto.
        $this->assertDatabaseCount('productos', 1);
        $this->assertSame(2, Producto::firstOrFail()->stock);
        $this->assertSame(1, ProductoSerial::whereNull('no_serie')->count());
    }

    public function test_video_por_pedazos_se_ensambla_y_se_liga_a_la_pieza(): void
    {
        $user = $this->usuarioApi(['inventario.registrar']);
        $uploadId = 'api-'.uniqid();

        $this->conToken($user)->post('/api/v1/movimientos/video-pedazo', [
            'chunk' => UploadedFile::fake()->create('p0.mp4', 100, 'video/mp4'),
            'upload_id' => $uploadId, 'index' => 0, 'total' => 2, 'extension' => 'mp4',
        ])->assertOk()->assertJsonPath('data.status', 'chunk_recibido');

        $ultimo = $this->conToken($user)->post('/api/v1/movimientos/video-pedazo', [
            'chunk' => UploadedFile::fake()->create('p1.mp4', 100, 'video/mp4'),
            'upload_id' => $uploadId, 'index' => 1, 'total' => 2, 'extension' => 'mp4',
        ])->assertOk()->assertJsonPath('data.status', 'listo');

        $ruta = $ultimo->json('data.video_path');
        Storage::disk('public')->assertExists($ruta);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada([
            'unidades' => [$this->unidad(['video_path' => $ruta])],
        ]))->assertCreated();

        $this->assertSame($ruta, ProductoSerial::firstOrFail()->video_path);
    }

    public function test_video_con_extension_no_permitida_o_ruta_inventada_da_422(): void
    {
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->post('/api/v1/movimientos/video-pedazo', [
            'chunk' => UploadedFile::fake()->create('p0.avi', 100),
            'upload_id' => 'api-123456789', 'index' => 0, 'total' => 1, 'extension' => 'avi',
        ])->assertStatus(422)->assertJsonValidationErrors('extension');

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada([
            'unidades' => [$this->unidad(['video_path' => '../../.env'])],
        ]))->assertStatus(422)->assertJsonValidationErrors('unidades.0.video_path');
    }

    public function test_listado_filtra_y_trae_el_resumen(): void
    {
        $user = $this->usuarioApi(['inventario.ver', 'inventario.registrar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada())->assertCreated();

        InventoryMovement::create([
            'folio' => 'SAL-2026-0001', 'movement_type' => 'salida', 'item_type' => 'producto', 'item_id' => 1,
            'item_name' => 'Torre', 'warehouse' => 'Almacen Central', 'quantity' => 1, 'unit' => 'Pza',
            'stock_before' => 1, 'stock_after' => 0, 'reference' => 'V-001', 'movement_date' => now()->subMonths(2)->toDateString(),
        ]);

        $this->conToken($user)->getJson('/api/v1/movimientos')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('resumen.movimientos', 2)
            ->assertJsonPath('resumen.entradas_mes', 1)
            ->assertJsonPath('resumen.piezas', 1)
            ->assertJsonStructure(['data', 'links', 'meta', 'resumen']);

        $this->conToken($user)->getJson('/api/v1/movimientos?tipo=salida')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tipo_vista', 'vendido');

        $this->conToken($user)->getJson('/api/v1/movimientos?desde='.now()->subMonth()->toDateString())
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.movement_type', 'entrada');

        $this->conToken($user)->getJson('/api/v1/movimientos?buscar=V-001')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->conToken($user)->getJson('/api/v1/movimientos?desde=10/10/2026')
            ->assertStatus(422);
    }

    public function test_detalle_trae_piezas_producto_y_etiquetas_en_html(): void
    {
        $user = $this->usuarioApi(['inventario.ver', 'inventario.registrar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada(['cantidad' => 2, 'unidades' => [$this->unidad(), $this->unidad()]]))
            ->assertCreated();

        $movimiento = InventoryMovement::firstOrFail();

        $this->conToken($user)->getJson("/api/v1/movimientos/{$movimiento->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.piezas')
            ->assertJsonPath('data.producto.id', $movimiento->item_id)
            ->assertJsonPath('data.orden_salida', null)
            ->assertJsonPath('data.movement_date', now()->toDateString());

        $codigo = ProductoSerial::firstOrFail()->codigo;

        $html = $this->conToken($user)->get("/api/v1/movimientos/{$movimiento->id}/etiquetas");
        $html->assertOk();
        $this->assertStringContainsString('text/html', $html->headers->get('Content-Type'));
        $html->assertSee($codigo);

        $this->conToken($user)->getJson('/api/v1/movimientos/99999')
            ->assertNotFound()->assertJsonPath('codigo', 'no_encontrado');
    }

    public function test_catalogos_del_formulario(): void
    {
        $this->catalogo();
        $user = $this->usuarioApi(['inventario.registrar']);

        $this->conToken($user)->getJson('/api/v1/movimientos/catalogos')
            ->assertOk()
            ->assertJsonPath('data.catalogo.tipos.0.name', 'TORRE DE ENDOSCOPIA')
            ->assertJsonPath('data.procesos.hojalateria', 'Hojalatería')
            ->assertJsonPath('data.puede_definir_precio', false)
            ->assertJsonPath('data.firma_registrada', null)
            ->assertJsonStructure(['data' => ['checklist' => ['funcionamiento' => ['titulo', 'puntos']], 'estados_generales', 'video']]);

        $this->conToken($this->usuarioApi(['inventario.ver']))->getJson('/api/v1/movimientos/catalogos')
            ->assertForbidden();
    }

    public function test_eliminar_ya_no_pide_pin_y_borra_piezas_y_evidencia(): void
    {
        $user = $this->usuarioApi(['inventario.registrar', 'inventario.eliminar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada())->assertCreated();
        $movimiento = InventoryMovement::firstOrFail();
        $evidencia = ProductoSerial::firstOrFail()->evidence_paths[0];

        $this->conToken($user)->deleteJson("/api/v1/movimientos/{$movimiento->id}")
            ->assertOk()
            ->assertJsonPath('message', "Movimiento {$movimiento->folio} eliminado.");

        $this->assertSoftDeleted($movimiento);
        $this->assertDatabaseCount('producto_seriales', 0);
        $this->assertSame(0, Producto::firstOrFail()->stock);
        Storage::disk('public')->assertMissing($evidencia);
        Storage::disk('public')->assertMissing($movimiento->signature_path);
    }

    public function test_no_se_elimina_una_entrada_con_piezas_vendidas(): void
    {
        $user = $this->usuarioApi(['inventario.registrar', 'inventario.eliminar']);

        $this->conToken($user)->post('/api/v1/movimientos', $this->datosEntrada())->assertCreated();
        $movimiento = InventoryMovement::firstOrFail();
        ProductoSerial::query()->update(['vendido' => true, 'vendido_en' => now()]);

        $this->conToken($user)->deleteJson("/api/v1/movimientos/{$movimiento->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'No se puede eliminar: alguna unidad de esta entrada ya se vendió.')
            ->assertJsonPath('codigo', 'conflicto');

        $this->assertNotSoftDeleted($movimiento);
    }

    public function test_permisos_y_token_de_integracion_recortado(): void
    {
        $sinPermiso = $this->usuarioApi();
        $this->conToken($sinPermiso)->getJson('/api/v1/movimientos')->assertForbidden();

        // El dueño es administrador, pero la llave solo puede ver.
        $admin = $this->usuarioApi(admin: true);
        $llave = fn () => $this->conToken($admin, ['inventario.ver'], TokenApi::TIPO_INTEGRACION);

        $llave()->getJson('/api/v1/movimientos')->assertOk();
        $llave()->post('/api/v1/movimientos', $this->datosEntrada())->assertForbidden();
        $llave()->postJson('/api/v1/movimientos/video-pedazo', [])->assertForbidden();

        $this->assertDatabaseCount('inventory_movements', 0);
    }
}
