<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Productos agrupados por tipo y en orden alfabético; paquetes agrupados y
 * con su contenido visible en Productos, Paquetes, el cotizador y los
 * documentos (cotización, venta y PDF).
 */
class ProductosAgrupadosTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private User $admin;

    private Paquete $torre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->usuarioApi([], true);

        $p = fn ($tipo, $sub, $modelo) => Producto::create(['tipo_equipo' => $tipo, 'subtipo' => $sub, 'marca' => 'STORZ', 'modelo' => $modelo, 'precio' => 1000, 'stock' => 0]);
        $camara = $p('LAPAROSCOPIA', 'CAMARA', 'C-100');
        $p('ENDOSCOPIA', 'GASTROSCOPIO', 'G-1');
        $monitor = $p('LAPAROSCOPIA', 'MONITOR', 'M-27');
        $p('ENDOSCOPIA', 'COLONOSCOPIO', 'K-9');

        $this->torre = Paquete::create(['nombre' => 'Torre de laparoscopía', 'precio' => 50000, 'activo' => true]);
        $this->torre->productos()->sync([$camara->id => ['cantidad' => 1], $monitor->id => ['cantidad' => 2]]);
    }

    public function test_productos_agrupados_por_tipo_y_en_orden_alfabetico(): void
    {
        $html = $this->actingAs($this->admin)->get(route('inventory.productos.index'))->assertOk()->getContent();

        // Tipos en orden y, dentro de cada uno, por subtipo.
        $orden = ['COLONOSCOPIO', 'GASTROSCOPIO', 'CAMARA', 'MONITOR'];
        $tabla = substr($html, strpos($html, 'data-view-list'));
        $pos = array_map(fn ($t) => strpos($tabla, $t), $orden);
        $this->assertSame($pos, collect($pos)->sort()->values()->all());
        $this->assertStringContainsString('data-grupo-toggle="ENDOSCOPIA"', $html);
        $this->assertStringContainsString('data-grupo-toggle="LAPAROSCOPIA"', $html);
        $this->assertLessThan(strpos($html, 'data-grupo-toggle="LAPAROSCOPIA"'), strpos($html, 'data-grupo-toggle="ENDOSCOPIA"'));

        // Filtro por tipo.
        $solo = $this->get(route('inventory.productos.index', ['tipo' => 'ENDOSCOPIA']))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-grupo-toggle="LAPAROSCOPIA"', $solo);

        // El paquete aparece en su grupo con lo que contiene.
        $this->assertStringContainsString('Torre de laparoscopía', $html);
        $this->assertMatchesRegularExpression('/2×<\/b> MONITOR · STORZ · M-27/u', $html);
    }

    public function test_paquetes_agrupados_con_contenido(): void
    {
        $this->assertSame('LAPAROSCOPIA', $this->torre->tipoPrincipal());

        $this->actingAs($this->admin)->get(route('inventory.paquetes.index'))->assertOk()
            ->assertSee('LAPAROSCOPIA')->assertSee('ver contenido')->assertSee('CAMARA · STORZ · C-100');
    }

    public function test_el_cotizador_y_los_documentos_muestran_lo_que_trae_el_paquete(): void
    {
        $this->actingAs($this->admin);

        $this->getJson(route('commercial.cotizaciones.productos.buscar', ['q' => 'Torre']))->assertOk()
            ->assertJsonPath('0.tipo_item', 'paquete')->assertJsonPath('0.contenido.1.cantidad', 2);

        $cliente = Customer::create(['nombre' => 'Hospital', 'apellido' => 'Prueba']);
        $this->post(route('commercial.cotizaciones.store'), [
            'customer_id' => $cliente->id, 'modalidad' => 'contado',
            'items' => [['tipo_item' => 'paquete', 'paquete_id' => $this->torre->id, 'nombre' => 'Torre de laparoscopía', 'cantidad' => 2, 'precio_unitario' => 50000]],
        ])->assertSessionHasNoErrors();
        $cot = Cotizacion::latest('id')->firstOrFail();

        // Detalle: cantidades por los 2 paquetes (1×2 cámaras, 2×2 monitores).
        $this->get(route('commercial.cotizaciones.show', $cot))->assertOk()
            ->assertSee('Incluye 2 artículos')->assertSee('4×', false)->assertSee('MONITOR');

        // Editar trae el contenido para el renglón.
        $this->get(route('commercial.cotizaciones.edit', $cot))->assertOk()->assertSee('"contenido"', false);

        // PDF (misma plantilla que la venta).
        $html = view('structure.commercial_management.cotizaciones.pdf', ['cotizacion' => $cot->load('items.paquete')])->render();
        $this->assertStringContainsString('Incluye:', $html);
        $this->assertStringContainsString('4&times; MONITOR STORZ M-27', $html);
    }
}
