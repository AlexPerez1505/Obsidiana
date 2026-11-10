<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\ProductoSerial;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** La pistola en Entrada y salida: status rápido del equipo. */
class EscanerEstatusTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private function pieza(array $extra = []): ProductoSerial
    {
        $producto = Producto::create(['tipo_equipo' => 'ENDOSCOPIA', 'subtipo' => 'GASTROSCOPIO', 'marca' => 'OLYMPUS', 'modelo' => 'GIF-T', 'precio' => 1, 'stock' => 1]);

        return ProductoSerial::create($extra + [
            'producto_id' => $producto->id, 'codigo' => ProductoSerial::generarCodigos(1)[0],
            'condicion' => 'usado', 'estado' => 'disponible', 'no_serie' => 'SER-1',
        ]);
    }

    public function test_disponible_y_lo_que_lee_la_pistola(): void
    {
        $p = $this->pieza();
        $u = $this->usuarioApi(['inventario.ver']);

        // La pistola teclea la liga completa del QR.
        $this->actingAs($u)->postJson(route('inventory.movimientos.estatus'), ['codigo' => $p->urlPublica()])->assertOk()
            ->assertJsonPath('encontrado', true)->assertJsonPath('codigo', $p->codigo)
            ->assertJsonPath('tono', 'ok')->assertJsonPath('equipo', 'ENDOSCOPIA · GASTROSCOPIO')
            ->assertJsonPath('vendible', true);
    }

    public function test_en_proceso_dice_que_le_falta(): void
    {
        $p = $this->pieza(['estado' => 'mantenimiento']);
        $p->procesos()->create(['proceso' => 'mantenimiento', 'orden' => 0, 'estado' => 'en_curso']);
        $u = $this->usuarioApi(['inventario.ver']);

        $r = $this->actingAs($u)->postJson(route('inventory.movimientos.estatus'), ['codigo' => strtolower($p->codigo)])->assertOk()
            ->assertJsonPath('tono', 'warn')->assertJsonPath('vendible', false);
        $this->assertStringContainsString('le falta Mantenimiento', $r->json('resumen'));
    }

    public function test_no_registrada_y_basura(): void
    {
        $u = $this->usuarioApi(['inventario.ver']);
        $this->actingAs($u)->postJson(route('inventory.movimientos.estatus'), ['codigo' => 'MB-999999'])->assertOk()->assertJsonPath('encontrado', false);
        $this->actingAs($u)->postJson(route('inventory.movimientos.estatus'), ['codigo' => 'hola'])->assertOk()->assertJsonPath('encontrado', false);
        $this->actingAs($this->usuarioApi([]))->postJson(route('inventory.movimientos.estatus'), ['codigo' => 'MB-000001'])->assertForbidden();
    }

    public function test_la_pantalla_trae_el_escaner(): void
    {
        $this->actingAs($this->usuarioApi(['inventario.ver']))->get(route('inventory.movimientos.index'))->assertOk()
            ->assertSee('data-scan-abrir', false)->assertSee('Status del equipo')
            ->assertSee('data-lectorqr', false)->assertSee('Foto del QR')->assertSee('html5-qrcode', false);
    }

    public function test_la_pantalla_escanear_tambien_trae_la_camara(): void
    {
        $this->actingAs($this->usuarioApi(['inventario.escanear']))->get(route('inventory.escaneo.index'))->assertOk()
            ->assertSee('Escanear con cámara')->assertSee('Foto del QR');
    }
}
