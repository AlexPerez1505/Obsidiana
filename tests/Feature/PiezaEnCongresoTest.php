<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Congress;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Saber dónde está una pieza, no solo qué tiene un congreso.
 *
 * Llevarse una pieza a un congreso no la bloquea (allá mismo se puede
 * vender), pero deja de estar en el anaquel. Quien la busque tiene que
 * enterarse: al escanearla, en el listado de Productos, y el congreso
 * tiene que avisar cuando terminó y nadie regresó lo que sobró.
 */
class PiezaEnCongresoTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioAprobado(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    private function congreso(array $overrides = []): Congress
    {
        $categoria = Category::firstOrCreate(['nombre' => 'Endoscopia']);

        return Congress::create(array_merge([
            'nombre' => 'Congreso de Endoscopia 2026',
            'categoria_id' => $categoria->id,
            'fecha_inicio' => now()->addDays(2)->toDateString(),
            'fecha_finalizacion' => now()->addDays(4)->toDateString(),
            'hora_montaje' => '08:00',
            'hora_desmontaje' => '18:00',
        ], $overrides));
    }

    private function producto(): Producto
    {
        $producto = Producto::create([
            'tipo_equipo' => 'Endoscopio',
            'marca' => 'Olympus',
            'modelo' => 'X1',
            'stock' => 0,
        ]);

        $producto->agregarUnidades(2, ['SN-A', 'SN-B']);

        return $producto;
    }

    public function test_al_escanear_una_pieza_en_congreso_se_avisa_donde_esta(): void
    {
        $user = $this->usuarioAprobado();
        $congress = $this->congreso();
        $producto = $this->producto();

        $pieza = $producto->seriales()->first();
        $pieza->update(['congress_id' => $congress->id, 'enviado_a_congreso_en' => now()]);

        $respuesta = $this->actingAs($user)->postJson(route('inventory.escaneo.buscar'), [
            'codigo' => $pieza->codigo,
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('encontrado', true);
        $respuesta->assertJsonPath('congreso', 'Congreso de Endoscopia 2026');
        $respuesta->assertJsonPath('congreso_desde', now()->format('d/m/Y'));
        // Seguir en un congreso no la vuelve invendible: allá se vende.
        $respuesta->assertJsonPath('vendible', true);
    }

    public function test_una_pieza_en_el_almacen_no_reporta_congreso(): void
    {
        $user = $this->usuarioAprobado();
        $pieza = $this->producto()->seriales()->first();

        $respuesta = $this->actingAs($user)->postJson(route('inventory.escaneo.buscar'), [
            'codigo' => $pieza->codigo,
        ]);

        $respuesta->assertJsonPath('congreso', null);
    }

    public function test_el_listado_de_productos_marca_la_unidad_que_esta_en_congreso(): void
    {
        $user = $this->usuarioAprobado();
        $congress = $this->congreso();
        $producto = $this->producto();

        $producto->seriales()->first()->update(['congress_id' => $congress->id]);

        $pagina = $this->actingAs($user)->get(route('inventory.productos.index'));

        $pagina->assertOk();
        $pagina->assertSee('En congreso: Congreso de Endoscopia 2026');
        // Y el acceso directo para ver todo lo que está fuera del almacén.
        $pagina->assertSee('En congreso (1)');
    }

    public function test_el_filtro_deja_solo_los_productos_con_piezas_en_congreso(): void
    {
        $user = $this->usuarioAprobado();
        $congress = $this->congreso();

        $enCongreso = $this->producto();
        $enCongreso->seriales()->first()->update(['congress_id' => $congress->id]);

        $enAlmacen = Producto::create([
            'tipo_equipo' => 'Monitor',
            'marca' => 'Mindray',
            'modelo' => 'M9',
            'stock' => 0,
        ]);
        $enAlmacen->agregarUnidades(1, ['SN-Z']);

        $pagina = $this->actingAs($user)->get(route('inventory.productos.index', ['ubicacion' => 'congreso']));

        $pagina->assertOk();
        $pagina->assertSee('Olympus');
        $pagina->assertDontSee('Mindray');
    }

    public function test_vender_una_pieza_la_saca_de_lo_que_esta_alla_pero_conserva_el_rastro(): void
    {
        $congress = $this->congreso();
        $producto = $this->producto();

        $piezas = $producto->seriales()->orderBy('id')->get();
        $piezas->each(fn ($p) => $p->update(['congress_id' => $congress->id]));

        // Una se vende estando en el congreso.
        $piezas->first()->update(['vendido' => true, 'vendido_en' => now(), 'estado' => 'vendido']);

        $congress->refresh();

        $this->assertSame(1, $congress->unidadesPresentes()->count(), 'Solo debe quedar una pieza allá.');
        $this->assertSame(1, $congress->unidadesVendidas()->count(), 'La vendida se cuenta como venta del congreso.');
        // El historial completo del evento sigue siendo de 2 piezas.
        $this->assertSame(2, $congress->unidadesEnCongreso()->count());
        // Y la tabla de "productos en el congreso" solo cuenta lo presente.
        $this->assertSame(1, $congress->productosResumen()->sum('cantidad'));
    }

    public function test_un_congreso_terminado_con_piezas_avisa_y_se_pueden_regresar_todas(): void
    {
        $user = $this->usuarioAprobado();

        $congress = $this->congreso([
            'fecha_inicio' => now()->subDays(6)->toDateString(),
            'fecha_finalizacion' => now()->subDays(3)->toDateString(),
        ]);

        $producto = $this->producto();
        $piezas = $producto->seriales()->orderBy('id')->get();
        $piezas->each(fn ($p) => $p->update(['congress_id' => $congress->id]));

        // Una se vendió allá: esa no se regresa, es el rastro de la venta.
        $vendida = $piezas->first();
        $vendida->update(['vendido' => true, 'vendido_en' => now(), 'estado' => 'vendido']);

        $this->assertTrue($congress->fresh()->tienePiezasSinRegresar());

        $pagina = $this->actingAs($user)->get(route('inventory.congresos.index', ['congreso' => $congress->id]));
        $pagina->assertSee('ya terminó y todavía tiene piezas allá', false);

        $this->actingAs($user)
            ->post(route('inventory.congresos.productos.regresarTodas', $congress))
            ->assertRedirect(route('inventory.congresos.index', ['congreso' => $congress->id]));

        $congress->refresh();

        $this->assertSame(0, $congress->unidadesPresentes()->count());
        $this->assertFalse($congress->tienePiezasSinRegresar());
        // La vendida conserva su marca del congreso.
        $this->assertSame($congress->id, $vendida->fresh()->congress_id);
    }

    public function test_no_se_puede_regresar_todas_si_no_hay_nada_alla(): void
    {
        $user = $this->usuarioAprobado();
        $congress = $this->congreso();

        $this->actingAs($user)
            ->from(route('inventory.congresos.index'))
            ->post(route('inventory.congresos.productos.regresarTodas', $congress))
            ->assertSessionHasErrors('unidades');
    }
}
