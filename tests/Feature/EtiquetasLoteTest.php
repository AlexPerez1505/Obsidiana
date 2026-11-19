<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\ProductoSerial;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** Imprimir las etiquetas de varios productos o entradas de un jalón. */
class EtiquetasLoteTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    public function test_etiquetas_de_varios_productos_sin_las_vendidas(): void
    {
        $a = Producto::create(['tipo_equipo' => 'LAPAROSCOPIA', 'subtipo' => 'CAMARA', 'marca' => 'STORZ', 'modelo' => 'C1', 'precio' => 1, 'stock' => 0]);
        $b = Producto::create(['tipo_equipo' => 'ENDOSCOPIA', 'subtipo' => 'GASTRO', 'marca' => 'OLYMPUS', 'modelo' => 'G1', 'precio' => 1, 'stock' => 0]);
        $a->agregarUnidades(2, ['SA-1', 'SA-2']);
        $b->agregarUnidades(1, ['SB-1']);
        ProductoSerial::where('no_serie', 'SA-2')->update(['vendido' => true]);
        $codigos = ProductoSerial::whereIn('no_serie', ['SA-1', 'SA-2', 'SB-1'])->pluck('codigo', 'no_serie');

        $user = $this->usuarioApi(['inventario.ver']);
        $r = $this->actingAs($user)->get(route('inventory.etiquetas.lote', ['productos' => [$a->id, $b->id]]))->assertOk();
        $r->assertSee($codigos['SA-1'])->assertSee($codigos['SB-1'])->assertDontSee($codigos['SA-2'])->assertSee('Incluir también las vendidas');

        $this->get(route('inventory.etiquetas.lote', ['productos' => [$a->id], 'vendidas' => 1]))->assertOk()->assertSee($codigos['SA-2']);

        // Sin permiso de inventario no entra.
        $this->actingAs($this->usuarioApi([]))->get(route('inventory.etiquetas.lote', ['productos' => [$a->id]]))->assertForbidden();
    }

    public function test_pantallas_con_la_seleccion(): void
    {
        $user = $this->usuarioApi([], true);
        $this->actingAs($user)->get(route('inventory.productos.index'))->assertOk()->assertSee('btn-etiquetas', false)->assertSee('sel-todos', false);
        $this->get(route('inventory.movimientos.index'))->assertOk()->assertSee('mvEtiquetas', false);
        $this->get(route('inventory.etiquetas.lote'))->assertOk()->assertSee('No hay piezas con etiqueta');
    }
}
