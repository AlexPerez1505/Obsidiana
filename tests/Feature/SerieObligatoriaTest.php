<?php

namespace Tests\Feature;

use App\Models\EquipmentType;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Services\Inventario\EntradasDeInventario;
use App\Services\Inventario\FotoPorPedazos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class SerieObligatoriaTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private function datos(): array
    {
        return ['equipment_type_id' => EquipmentType::create(['name' => 'PRUEBA SERIE '.Str::uuid()])->id, 'stock' => 2];
    }

    public function test_producto_web_rechaza_series_vacias_sin_crear_piezas(): void
    {
        $user = $this->usuarioApi([], admin: true);
        $user->setAttribute('ficha_completada', true);
        $datos = $this->datos();
        $antes = ProductoSerial::count();
        foreach (['', " \n\t ", ",,\n"] as $texto) {
            $this->actingAs($user)->post(route('inventory.productos.store'), $datos + ['series_texto' => $texto])
                ->assertSessionHasErrors('series_texto');
            $this->assertSame($antes, ProductoSerial::count());
            $this->assertDatabaseMissing('productos', ['equipment_type_id' => $datos['equipment_type_id']]);
        }
    }

    public function test_producto_api_rechaza_series_vacias_o_incompletas(): void
    {
        $user = $this->usuarioApi([], admin: true);
        $datos = $this->datos();
        $this->conToken($user)->postJson('/api/v1/productos', $datos)->assertUnprocessable()->assertJsonValidationErrors('series_texto');
        $this->conToken($user)->postJson('/api/v1/productos', array_replace($datos, ['stock' => 3, 'series_texto' => "ABC\nDEF"]))
            ->assertUnprocessable()->assertJsonValidationErrors('series_texto');
        $this->assertDatabaseMissing('productos', ['equipment_type_id' => $datos['equipment_type_id']]);
    }

    public function test_producto_conserva_secuencia_y_acepta_serie_cero(): void
    {
        $user = $this->usuarioApi([], admin: true);
        $datos = $this->datos();
        $this->conToken($user)->postJson('/api/v1/productos', $datos + ['series_texto' => 'ABC001'])->assertCreated();
        $producto = Producto::where('equipment_type_id', $datos['equipment_type_id'])->firstOrFail();
        $this->assertSame(['ABC001', 'ABC002'], $producto->seriales()->orderBy('id')->pluck('no_serie')->all());
        $cero = array_replace($this->datos(), ['stock' => 1, 'series_texto' => '0']);
        $this->conToken($user)->postJson('/api/v1/productos', $cero)->assertCreated();
        $this->assertSame('0', Producto::where('equipment_type_id', $cero['equipment_type_id'])->firstOrFail()->seriales()->firstOrFail()->no_serie);
    }

    public function test_agregar_piezas_no_permite_series_vacias(): void
    {
        $datos = $this->datos();
        $producto = Producto::create(['equipment_type_id' => $datos['equipment_type_id'], 'tipo_equipo' => 'PRUEBA', 'stock' => 0]);
        try {
            app(\App\Services\Inventario\ProductosDeInventario::class)->agregarUnidades($producto, 1, ' ');
            $this->fail('Se agregó una pieza sin número de serie.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('series_texto', $e->errors());
        }
        $this->assertSame(0, $producto->seriales()->count());
    }

    public function test_formularios_marcan_la_serie_como_obligatoria(): void
    {
        $user = $this->usuarioApi([], admin: true);
        $user->setAttribute('ficha_completada', true);
        $producto = $this->actingAs($user)->get(route('inventory.productos.create'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/<textarea[^>]*id="series_texto"[^>]*required/', $producto);
        $entrada = $this->get(route('inventory.movimientos.create'))->assertOk()->getContent();
        $this->assertStringContainsString('name="unidades[${i}][no_serie]" required', $entrada);
        $this->assertStringContainsString('No. de serie del fabricante (obligatorio)', $entrada);
    }

    public function test_entrada_web_y_api_exigen_serie_en_cada_pieza(): void
    {
        $user = $this->usuarioApi([], admin: true);
        $user->setAttribute('ficha_completada', true);
        $datos = $this->datos() + ['cantidad' => 2, 'movement_date' => now()->toDateString(), 'firma' => 'pendiente', 'unidades' => [['no_serie' => 'ABC'], ['no_serie' => ' ']]];
        $antes = InventoryMovement::count();
        $this->actingAs($user)->post(route('inventory.movimientos.store'), $datos)->assertSessionHasErrors('unidades.1.no_serie');
        foreach (['nuevo', 'usado'] as $condicion) {
            $this->conToken($user)->postJson('/api/v1/movimientos', $datos + ['condicion' => $condicion])
                ->assertUnprocessable()->assertJsonValidationErrors('unidades.1.no_serie');
        }
        $this->assertSame($antes, InventoryMovement::count());
    }

    public function test_servicio_de_entrada_rechaza_serie_omitida_antes_de_guardar_archivos(): void
    {
        $user = $this->usuarioApi([], admin: true);
        $request = Request::create('/entrada', 'POST');
        $request->setUserResolver(fn () => $user);
        try {
            app(EntradasDeInventario::class)->registrar($request, ['cantidad' => 1, 'unidades' => [[]]]);
            $this->fail('Se aceptó una pieza sin número de serie.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('unidades.0.no_serie', $e->errors());
        }
    }

    public function test_entrada_con_fotos_compartidas_guarda_todas_las_series_y_rechaza_duplicados(): void
    {
        $disco = config('filesystems.fotos_disk', 'public');
        $anterior = Storage::disk($disco);
        Storage::set($disco, Storage::createLocalDriver(['root' => sys_get_temp_dir().'/obsidiana-series-'.Str::uuid()]));
        try {
            $user = $this->usuarioApi([], admin: true);
            $this->actingAs($user);
            $foto = FotoPorPedazos::CARPETA.'/foto_prueba.jpg';
            Storage::disk($disco)->put($foto, 'foto de prueba');
            $datos = $this->datos() + ['cantidad' => 2, 'movement_date' => now()->toDateString(),
                'firma' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
                'foto_compartida' => true, 'foto_compartida_paths' => [$foto], 'unidades' => [['no_serie' => 'ABC'], ['no_serie' => '0']]];
            $request = Request::create('/entrada', 'POST', $datos);
            $request->setUserResolver(fn () => $user);
            $entrada = app(EntradasDeInventario::class)->registrar($request, $datos)['movimiento'];
            $this->assertSame(['ABC', '0'], $entrada->seriales()->orderBy('id')->pluck('no_serie')->all());
            $antes = $entrada->seriales()->count();
            $archivos = Storage::disk($disco)->allFiles();
            try {
                app(EntradasDeInventario::class)->registrar($request, $datos);
                $this->fail('Se aceptó una serie duplicada como pieza sin serie.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('unidades', $e->errors());
            }
            $this->assertSame($antes, $entrada->seriales()->count());
            $this->assertCount(count($archivos), Storage::disk($disco)->allFiles());
        } finally {
            Storage::disk($disco)->deleteDirectory('');
            Storage::set($disco, $anterior);
        }
    }

    public function test_entrada_usada_tambien_acepta_fotos_compartidas(): void
    {
        $disco = config('filesystems.fotos_disk', 'public');
        $anterior = Storage::disk($disco);
        Storage::set($disco, Storage::createLocalDriver(['root' => sys_get_temp_dir().'/obsidiana-compartida-'.Str::uuid()]));
        try {
            $user = $this->usuarioApi([], admin: true);
            $this->actingAs($user);
            $foto = FotoPorPedazos::CARPETA.'/foto_compartida.jpg';
            Storage::disk($disco)->put($foto, 'foto de prueba');
            $datos = $this->datos() + ['condicion' => 'usado', 'cantidad' => 2, 'movement_date' => now()->toDateString(),
                'estado_general' => 'bueno', 'checklist' => ['manual_llave' => ['r' => 'si', 'texto' => 'Incluye llave', 'grupo' => 'Agregados a mano', 'manda' => 'solo_anotar']],
                'firma' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
                'foto_compartida' => true, 'foto_compartida_paths' => [$foto], 'unidades' => [['no_serie' => 'USA-1'], ['no_serie' => 'USA-2']]];
            $request = Request::create('/entrada', 'POST', $datos);
            $request->setUserResolver(fn () => $user);
            $entrada = app(EntradasDeInventario::class)->registrar($request, $datos)['movimiento'];
            $this->assertSame(['USA-1', 'USA-2'], $entrada->seriales()->orderBy('id')->pluck('no_serie')->all());
            // Las dos piezas quedaron con su copia de la foto compartida.
            foreach ($entrada->seriales as $serial) {
                $this->assertCount(1, $serial->evidence_paths);
            }
        } finally {
            Storage::disk($disco)->deleteDirectory('');
            Storage::set($disco, $anterior);
        }
    }
}
