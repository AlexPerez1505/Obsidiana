<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\PiezaComponente;
use App\Models\ProductoSerial;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Inventario\Concerns\DatosDeInventario;
use Tests\TestCase;

/**
 * Componentes de una pieza: un sistema llega con procesador y fuente, cada
 * uno con su pigtail e interfaz; o llegan por partes y lo demás se recibe
 * después. Cada componente con su serie y sus fotos.
 */
class PiezaComponentesTest extends TestCase
{
    use DatosDeInventario, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();

        $raiz = storage_path('framework/testing/disks/public-componentes');
        (new Filesystem)->cleanDirectory($raiz);
        Storage::set('public', Storage::createLocalDriver(['root' => $raiz, 'url' => '/storage']));
    }

    private function pieza($user): ProductoSerial
    {
        $this->actingAs($user)->post(route('inventory.movimientos.store'), $this->datosEntrada())
            ->assertRedirect(route('inventory.movimientos.index'));

        return InventoryMovement::latest('id')->firstOrFail()->seriales()->firstOrFail();
    }

    private function foto($user): string
    {
        return $this->actingAs($user)->post(route('inventory.movimientos.fotoChunk'), [
            'chunk' => UploadedFile::fake()->image('pigtail.jpg'),
            'upload_id' => 'prueba-'.uniqid(),
            'index' => 0,
            'total' => 1,
            'extension' => 'jpg',
        ])->assertOk()->json('foto_path');
    }

    public function test_un_set_con_componentes_dentro_de_componentes(): void
    {
        $yo = $this->usuarioApi(['inventario.ver', 'inventario.registrar']);
        $pieza = $this->pieza($yo);
        $foto = $this->foto($yo);

        $this->actingAs($yo)->postJson(route('inventory.componentes.store', $pieza), [
            'nombre' => 'Procesador', 'no_serie' => 'proc-001', 'estado' => 'recibido', 'fotos' => [$foto],
        ])->assertOk()->assertJsonPath('total', 1);
        $procesador = PiezaComponente::firstOrFail();
        $this->assertSame('PROCESADOR', $procesador->nombre);
        $this->assertSame('PROC-001', $procesador->no_serie);
        $this->assertSame([$foto], $procesador->fotos);
        $this->assertNotNull($procesador->recibido_en);

        // Dentro del procesador, su pigtail y su interfaz.
        $json = $this->actingAs($yo)->postJson(route('inventory.componentes.store', $pieza), [
            'lista' => "Pigtail\nInterfaz", 'padre_id' => $procesador->id, 'estado' => 'recibido',
        ])->assertOk()->assertJsonPath('total', 3)->json();

        $this->assertSame(['INTERFAZ', 'PIGTAIL'], collect($json['componentes'])->where('nivel', 1)->pluck('nombre')->sort()->values()->all());
        $this->assertSame(2, PiezaComponente::where('padre_id', $procesador->id)->count());

        $this->actingAs($yo)->getJson(route('inventory.componentes.index', $pieza))
            ->assertOk()->assertJsonPath('componentes.0.nombre', 'PROCESADOR')->assertJsonPath('componentes.1.nivel', 1);

        // La ficha pública (QR) enseña lo que incluye.
        $this->get(route('publico.equipo', $pieza->codigo))->assertOk()->assertSee('Componentes')->assertSee('PIGTAIL')->assertSee('S/N PROC-001');
    }

    public function test_lo_que_llega_despues_se_registra_por_llegar_y_se_recibe_con_su_serie(): void
    {
        $yo = $this->usuarioApi(['inventario.ver', 'inventario.registrar']);
        $pieza = $this->pieza($yo);

        $this->actingAs($yo)->postJson(route('inventory.componentes.store', $pieza), [
            'lista' => 'Pigtail, Interfaz', 'estado' => 'por_llegar',
        ])->assertOk()->assertJsonPath('por_llegar', 2);

        $pigtail = PiezaComponente::where('nombre', 'PIGTAIL')->firstOrFail();
        $this->assertNull($pigtail->recibido_en);

        // A la semana llega: se le pone serie y foto.
        $foto = $this->foto($yo);
        $this->actingAs($yo)->putJson(route('inventory.componentes.update', $pigtail), [
            'nombre' => 'Pigtail', 'no_serie' => 'PT-77', 'estado' => 'recibido', 'fotos' => [$foto],
        ])->assertOk()->assertJsonPath('por_llegar', 1)->assertJsonPath('message', 'PIGTAIL quedó como recibido.');

        $pigtail->refresh();
        $this->assertSame('recibido', $pigtail->estado);
        $this->assertSame($yo->id, (int) $pigtail->recibido_por);
        $this->assertNotNull($pigtail->recibido_en);

        // Quitar la foto la borra del disco.
        Storage::disk('public')->assertExists($foto);
        $this->actingAs($yo)->putJson(route('inventory.componentes.update', $pigtail), [
            'nombre' => 'Pigtail', 'no_serie' => 'PT-77', 'estado' => 'recibido', 'quitar_fotos' => [$foto],
        ])->assertOk();
        $this->assertNull($pigtail->fresh()->fotos);
        Storage::disk('public')->assertMissing($foto);

        // El detalle del movimiento enseña el botón con lo que falta.
        $this->actingAs($yo)->get(route('inventory.movimientos.show', $pieza->inventory_movement_id))
            ->assertOk()->assertSee('data-componentes="'.$pieza->id.'"', false)->assertSee('1 por llegar');
    }

    public function test_la_serie_no_se_repite_ni_con_otro_componente_ni_con_una_pieza(): void
    {
        $yo = $this->usuarioApi(['inventario.ver', 'inventario.registrar']);
        $pieza = $this->pieza($yo);

        $this->actingAs($yo)->postJson(route('inventory.componentes.store', $pieza), ['nombre' => 'Fuente', 'no_serie' => 'F-1', 'estado' => 'recibido'])->assertOk();
        $this->actingAs($yo)->postJson(route('inventory.componentes.store', $pieza), ['nombre' => 'Otra', 'no_serie' => ' f-1 ', 'estado' => 'recibido'])
            ->assertStatus(422)->assertJsonValidationErrors('no_serie');

        $pieza->update(['no_serie' => 'EQUIPO-9']);
        $this->actingAs($yo)->postJson(route('inventory.componentes.store', $pieza), ['nombre' => 'Otra', 'no_serie' => 'equipo-9', 'estado' => 'recibido'])
            ->assertStatus(422)->assertJsonValidationErrors('no_serie');

        // Un componente no puede ir dentro de lo que trae dentro.
        $fuente = PiezaComponente::firstOrFail();
        $this->actingAs($yo)->postJson(route('inventory.componentes.store', $pieza), ['nombre' => 'Cable', 'padre_id' => $fuente->id, 'estado' => 'recibido'])->assertOk();
        $cable = PiezaComponente::where('nombre', 'CABLE')->firstOrFail();
        $this->actingAs($yo)->putJson(route('inventory.componentes.update', $fuente), ['nombre' => 'Fuente', 'padre_id' => $cable->id, 'estado' => 'recibido'])
            ->assertStatus(422)->assertJsonValidationErrors('padre_id');
    }

    public function test_quitar_componente_con_lo_que_trae_dentro_y_permisos(): void
    {
        $yo = $this->usuarioApi(['inventario.ver', 'inventario.registrar']);
        $otro = $this->usuarioApi(['inventario.ver', 'inventario.registrar']);
        $solo_ver = $this->usuarioApi(['inventario.ver']);
        $pieza = $this->pieza($yo);

        $this->actingAs($yo)->postJson(route('inventory.componentes.store', $pieza), ['nombre' => 'Procesador', 'estado' => 'recibido'])->assertOk();
        $proc = PiezaComponente::firstOrFail();
        $this->actingAs($yo)->postJson(route('inventory.componentes.store', $pieza), ['lista' => 'Pigtail', 'padre_id' => $proc->id, 'estado' => 'recibido'])->assertOk();

        // Solo ver: consulta pero no agrega.
        $this->actingAs($solo_ver)->getJson(route('inventory.componentes.index', $pieza))->assertOk()->assertJsonPath('puede_editar', false);
        $this->actingAs($solo_ver)->postJson(route('inventory.componentes.store', $pieza), ['nombre' => 'X', 'estado' => 'recibido'])->assertForbidden();

        // Otro almacenista no quita lo que yo capturé.
        $this->actingAs($otro)->deleteJson(route('inventory.componentes.destroy', $proc))->assertForbidden();

        // Yo sí, y se van también los que trae dentro.
        $this->actingAs($yo)->deleteJson(route('inventory.componentes.destroy', $proc))->assertOk()->assertJsonPath('total', 0);
        $this->assertSame(0, PiezaComponente::count());
    }
}
