<?php

namespace Tests\Feature\Api\V1\Almacen;

use App\Models\Category;
use App\Models\Congress;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Congresos por la API: las piezas se llevan (solo una etiqueta), se
 * regresan, y las vendidas conservan la marca.
 */
class CongresosTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function datos(array $extra = []): array
    {
        return $extra + [
            'nombre' => 'Congreso de Prueba',
            'categoria_id' => Category::firstOrCreate(['nombre' => 'Endoscopia'])->id,
            'fecha_inicio' => now()->addDays(2)->toDateString(),
            'fecha_finalizacion' => now()->addDays(4)->toDateString(),
            'hora_montaje' => '08:00',
            'hora_desmontaje' => '18:00',
            'direccion' => 'Centro de Convenciones',
            'costo' => 1000,
            'pagado' => 250,
        ];
    }

    private function congreso(array $extra = []): Congress
    {
        return Congress::create($this->datos($extra));
    }

    private function producto(int $unidades = 2): Producto
    {
        $producto = Producto::create(['tipo_equipo' => 'Endoscopio', 'marca' => 'Olympus', 'modelo' => 'X1', 'stock' => 0]);
        $producto->agregarUnidades($unidades, array_slice(['SN-A', 'SN-B', 'SN-C'], 0, $unidades));

        return $producto;
    }

    private function editor()
    {
        return $this->conToken($this->usuarioApi(['congresos.ver', 'congresos.editar']));
    }

    public function test_crear_con_piezas_y_usuarios_y_ver_el_detalle(): void
    {
        Storage::fake('public');

        $producto = $this->producto();
        $vendedor = $this->usuarioApi();

        $res = $this->editor()->post('/api/v1/congresos', $this->datos([
            'serial_ids' => $producto->seriales()->pluck('id')->all(),
            'user_ids' => [$vendedor->id],
            'archivos' => [UploadedFile::fake()->create('plano.pdf', 10, 'application/pdf')],
        ]));

        $res->assertCreated()
            ->assertJsonPath('data.nombre', 'CONGRESO DE PRUEBA')
            ->assertJsonPath('data.costo', 1000)
            ->assertJsonPath('data.saldo_pendiente', 750)
            ->assertJsonPath('data.estado', 'upcoming')
            ->assertJsonPath('data.unidades_presentes_count', 2)
            ->assertJsonPath('data.productos.0.cantidad', 2)
            ->assertJsonPath('data.usuarios.0.id', $vendedor->id)
            ->assertJsonCount(1, 'data.archivos')
            ->assertJsonPath('message', 'Congreso guardado correctamente.');

        $congreso = Congress::firstOrFail();
        Storage::disk('public')->assertExists($congreso->path_archivo[0]);

        // Llevar una pieza no cambia su estado: sigue vendible.
        $this->assertSame(['disponible'], $producto->seriales()->pluck('estado')->unique()->values()->all());
        $this->assertSame(2, $producto->fresh()->stock);

        $this->conToken($this->usuarioApi(['congresos.ver']))
            ->getJson("/api/v1/congresos/{$congreso->id}")
            ->assertOk()
            ->assertJsonPath('data.fecha_inicio', now()->addDays(2)->toDateString())
            ->assertJsonPath('data.hora_montaje', '08:00')
            ->assertJsonPath('data.tiene_piezas_sin_regresar', false);
    }

    public function test_listado_con_filtros(): void
    {
        $proximo = $this->congreso(['nombre' => 'Expo Futuro']);
        $pasado = $this->congreso([
            'nombre' => 'Expo Pasada',
            'fecha_inicio' => now()->subDays(10)->toDateString(),
            'fecha_finalizacion' => now()->subDays(8)->toDateString(),
        ]);

        $api = $this->conToken($this->usuarioApi(['congresos.ver']));

        $api->getJson('/api/v1/congresos')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $pasado->id);

        $api->getJson('/api/v1/congresos?estado=finished')
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $pasado->id);
        $api->getJson('/api/v1/congresos?buscar=futuro')
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $proximo->id);
        $api->getJson('/api/v1/congresos?desde='.now()->toDateString())
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $proximo->id);
    }

    public function test_llevar_regresar_y_las_vendidas_conservan_la_marca(): void
    {
        $congreso = $this->congreso();
        $producto = $this->producto(3);
        [$a, $b, $c] = $producto->seriales()->orderBy('id')->get()->all();
        $api = $this->editor();

        $api->getJson("/api/v1/congresos/unidades-disponibles?producto_id={$producto->id}")
            ->assertOk()->assertJsonCount(3, 'data');

        $api->postJson("/api/v1/congresos/{$congreso->id}/unidades", ['serial_ids' => [$a->id, $b->id, $c->id]])
            ->assertOk()
            ->assertJsonPath('data.unidades_presentes_count', 3)
            ->assertJsonPath('message', '3 unidad(es) agregadas al congreso.');

        // Ya están allá: no se pueden volver a llevar.
        $api->postJson("/api/v1/congresos/{$congreso->id}/unidades", ['serial_ids' => [$a->id]])
            ->assertStatus(422)
            ->assertJsonPath('errors.serial_ids.0', 'Esas unidades ya no están disponibles.');
        $api->getJson("/api/v1/congresos/unidades-disponibles?producto_id={$producto->id}")
            ->assertJsonCount(0, 'data');

        // Regresar una puntual.
        $api->deleteJson("/api/v1/congresos/{$congreso->id}/unidades/{$a->id}")
            ->assertOk()->assertJsonPath('data.unidades_presentes_count', 2);
        $this->assertNull($a->fresh()->congress_id);

        // Una se vende allá; al regresar todas, esa se queda marcada.
        $b->forceFill(['vendido' => true])->save();

        $api->postJson("/api/v1/congresos/{$congreso->id}/regresar-todas")
            ->assertOk()
            ->assertJsonPath('message', 'Se regresaron 1 pieza(s) al almacén.')
            ->assertJsonPath('data.unidades_presentes_count', 0)
            ->assertJsonPath('data.unidades_vendidas_count', 1);

        $this->assertNull($c->fresh()->congress_id);
        $this->assertNull($c->fresh()->enviado_a_congreso_en);
        $this->assertSame($congreso->id, $b->fresh()->congress_id, 'La vendida conserva el rastro del congreso.');

        $api->postJson("/api/v1/congresos/{$congreso->id}/regresar-todas")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Este congreso no tiene piezas por regresar.');

        // Una pieza de otro congreso no se regresa desde este.
        $otro = $this->congreso(['nombre' => 'Otro']);
        $b->forceFill(['congress_id' => $otro->id])->save();
        $api->deleteJson("/api/v1/congresos/{$congreso->id}/unidades/{$b->id}")->assertNotFound();
    }

    public function test_participantes_y_usuarios(): void
    {
        $congreso = $this->congreso();
        $usuario = $this->usuarioApi();
        $api = $this->editor();

        $participante = $api->postJson("/api/v1/congresos/{$congreso->id}/participantes", ['nombre' => 'Dra. López', 'rol' => 'Ponente'])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'DRA. LÓPEZ')
            ->json('data.id');

        $api->postJson("/api/v1/congresos/{$congreso->id}/usuarios", ['user_id' => $usuario->id])
            ->assertOk()->assertJsonPath('data.usuarios.0.id', $usuario->id);

        $api->getJson("/api/v1/congresos/{$congreso->id}")->assertJsonPath('data.participantes.0.id', $participante);

        $api->deleteJson("/api/v1/congresos/{$congreso->id}/participantes/{$participante}")
            ->assertOk()->assertJsonPath('message', 'Participante eliminado.');
        $api->deleteJson("/api/v1/congresos/{$congreso->id}/usuarios/{$usuario->id}")
            ->assertOk()->assertJsonCount(0, 'data.usuarios');

        $this->assertDatabaseCount('congreso_participantes', 0);
    }

    public function test_editar_quitar_archivos_y_borrar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('congresos/viejo.pdf', 'x');
        Storage::disk('public')->put('avatars/ajeno.png', 'x');

        $congreso = $this->congreso();
        $congreso->forceFill(['path_archivo' => ['congresos/viejo.pdf']])->save();
        $api = $this->editor();

        $api->putJson("/api/v1/congresos/{$congreso->id}", $this->datos([
            'nombre' => 'Nombre Nuevo',
            // Un archivo que no es del congreso no se borra.
            'quitar_archivos' => ['congresos/viejo.pdf', 'avatars/ajeno.png'],
        ]))
            ->assertOk()
            ->assertJsonPath('data.nombre', 'NOMBRE NUEVO')
            ->assertJsonCount(0, 'data.archivos');

        Storage::disk('public')->assertMissing('congresos/viejo.pdf');
        Storage::disk('public')->assertExists('avatars/ajeno.png');

        $api->deleteJson("/api/v1/congresos/{$congreso->id}")
            ->assertOk()->assertJsonPath('message', 'Congreso eliminado correctamente.');
        $this->assertDatabaseCount('congresos_eventos', 0);
    }

    public function test_validacion_igual_que_la_web(): void
    {
        $this->editor()->postJson('/api/v1/congresos', $this->datos([
            'fecha_montaje' => now()->addDays(3)->toDateString(),
            'pagado' => 5000,
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.pagado.0', 'Lo pagado no puede ser mayor que el costo del congreso.')
            ->assertJsonPath('errors.fecha_montaje.0', 'La fecha de montaje debe ser el mismo día en que inicia el congreso o antes (el stand se arma antes de que arranque).');

        $this->assertDatabaseCount('congresos_eventos', 0);
    }

    public function test_catalogos_del_formulario(): void
    {
        Category::create(['nombre' => 'Endoscopia']);
        $this->producto(1);

        $this->editor()->getJson('/api/v1/congresos/catalogos')
            ->assertOk()
            ->assertJsonPath('data.categorias.0.nombre', 'Endoscopia')
            ->assertJsonPath('data.productos.0.marca', 'Olympus')
            ->assertJsonStructure(['data' => ['usuarios']]);
    }

    public function test_permisos(): void
    {
        $congreso = $this->congreso();

        $this->conToken($this->usuarioApi())->getJson('/api/v1/congresos')->assertForbidden();

        $lector = $this->conToken($this->usuarioApi(['congresos.ver']));
        $lector->getJson("/api/v1/congresos/{$congreso->id}")->assertOk();
        $lector->deleteJson("/api/v1/congresos/{$congreso->id}")->assertForbidden();
        $lector->getJson('/api/v1/congresos/catalogos')->assertForbidden();

        $llave = $this->conToken($this->usuarioApi(['congresos.ver', 'congresos.editar']), ['congresos.ver'], TokenApi::TIPO_INTEGRACION);
        $llave->getJson('/api/v1/congresos')->assertOk();
        $llave->postJson("/api/v1/congresos/{$congreso->id}/regresar-todas")->assertForbidden();

        $this->assertDatabaseCount('congresos_eventos', 1);
    }
}
