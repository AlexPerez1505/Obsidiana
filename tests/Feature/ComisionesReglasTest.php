<?php

namespace Tests\Feature;

use App\Models\Cobro;
use App\Models\Congress;
use App\Models\Customer;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Services\Ventas\Comisiones;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Reglas: endoscopia 3 % y lo demás 4 % al vendedor; ventas de congreso
 * (por la venta o por el cliente) 2 % repartido entre quienes fueron.
 */
class ComisionesReglasTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private User $ana;

    private User $beto;

    private User $caro;

    private Producto $gastro;

    private Producto $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ana = $this->usuarioApi();
        $this->beto = $this->usuarioApi();
        $this->caro = $this->usuarioApi();
        $this->gastro = Producto::create(['tipo_equipo' => 'ENDOSCOPIA VETERINARIA', 'subtipo' => 'GASTROSCOPIO', 'marca' => 'OLYMPUS', 'modelo' => 'G1', 'precio' => 1, 'stock' => 0]);
        $this->monitor = Producto::create(['tipo_equipo' => 'LAPAROSCOPIA', 'subtipo' => 'MONITOR', 'marca' => 'STORZ', 'modelo' => 'M1', 'precio' => 1, 'stock' => 0]);
    }

    private function congreso(string $nombre): Congress
    {
        $cat = \App\Models\Category::firstOrCreate(['nombre' => 'Veterinaria']);

        return Congress::create(['nombre' => $nombre.' '.uniqid(), 'categoria_id' => $cat->id, 'fecha_inicio' => now()->subDays(3), 'fecha_finalizacion' => now()->subDay()]);
    }

    /** Venta con renglones [producto, importe], sin pasar por inventario. */
    private function venta(User $vendedor, array $renglones, array $extra = [], ?Customer $cliente = null): Venta
    {
        $subtotal = array_sum(array_column($renglones, 1));
        $desc = (float) ($extra['descuento_monto'] ?? 0);
        $iva = ! empty($extra['aplica_iva']) ? round(($subtotal - $desc) * 0.16, 2) : 0;
        $cliente ??= Customer::create(['nombre' => 'Cliente '.uniqid()]);

        $v = Venta::forceCreate($extra + [
            'folio' => 'VEN-T-'.uniqid(), 'customer_id' => $cliente->id, 'seller_id' => $vendedor->id, 'modalidad' => 'contado',
            'aplica_iva' => false, 'subtotal' => $subtotal, 'descuento_valor' => 0, 'descuento_monto' => 0, 'envio' => 0,
            'iva_monto' => $iva, 'valor_a_cuenta' => 0, 'total' => $subtotal - $desc + $iva, 'total_contrato' => $subtotal - $desc + $iva,
            'num_meses' => 0, 'garantia_meses' => 6, 'estado' => 'borrador',
        ]);
        foreach ($renglones as $i => [$prod, $importe]) {
            $v->items()->create(['tipo_item' => 'producto', 'producto_id' => $prod->id, 'nombre' => $prod->subtipo, 'cantidad' => 1,
                'precio_unitario' => $importe, 'sobreprecio' => 0, 'es_regalo' => false, 'orden' => $i]);
        }

        return $v;
    }

    private function filas(string $base = 'vendido')
    {
        $periodo = now()->format('Y-m');
        [$ini, $fin] = Comisiones::limites($periodo);

        return app(Comisiones::class)->filasDelPeriodo($ini, $fin, $periodo, $base)->keyBy(fn ($f) => $f['asesor']->id);
    }

    public function test_endoscopia_3_y_lo_demas_4_al_vendedor(): void
    {
        $this->venta($this->ana, [[$this->gastro, 10000], [$this->monitor, 5000]]);

        $f = $this->filas()[$this->ana->id];
        $this->assertEquals(300, $f['comision_endoscopia']);
        $this->assertEquals(200, $f['comision_otros']);
        $this->assertEquals(500, $f['comision']);
    }

    public function test_sin_iva_y_despues_del_descuento(): void
    {
        // 10,000 de endoscopia con 1,000 de descuento e IVA: comisión sobre 9,000.
        $this->venta($this->ana, [[$this->gastro, 10000]], ['descuento_monto' => 1000, 'aplica_iva' => true]);

        $this->assertEquals(270, $this->filas()[$this->ana->id]['comision']);
    }

    public function test_venta_de_congreso_2_por_ciento_repartido_entre_quienes_fueron(): void
    {
        $congreso = $this->congreso('Congreso Vet ');
        $congreso->notifiedUsers()->sync([$this->beto->id, $this->caro->id]);

        // Vende Ana, pero es de congreso: Ana no gana su 3/4 %; Beto y Caro se reparten el 2 %.
        $this->venta($this->ana, [[$this->gastro, 20000]], ['congreso_id' => $congreso->id]);

        $f = $this->filas();
        $this->assertEquals(200, $f[$this->beto->id]['comision_congreso']);
        $this->assertEquals(200, $f[$this->caro->id]['comision_congreso']);
        $this->assertEquals(0, $f[$this->ana->id]['comision']);
    }

    public function test_cliente_registrado_en_congreso_tambien_cuenta(): void
    {
        $congreso = $this->congreso('Congreso Vet ');
        $congreso->notifiedUsers()->sync([$this->ana->id, $this->beto->id, $this->caro->id]);
        $cliente = Customer::create(['nombre' => 'Clínica de congreso', 'congreso_id' => $congreso->id]);

        $this->venta($this->ana, [[$this->monitor, 30000]], [], $cliente);

        $f = $this->filas();
        foreach ([$this->ana, $this->beto, $this->caro] as $u) {
            $this->assertEquals(200, $f[$u->id]['comision_congreso']);   // 2 % de 30,000 / 3
        }
        $this->assertEquals(0, $f[$this->ana->id]['comision_otros']);
    }

    public function test_congreso_sin_asignados_se_queda_al_vendedor_con_aviso(): void
    {
        $congreso = $this->congreso('Congreso vacío ');
        $this->venta($this->ana, [[$this->gastro, 10000]], ['congreso_id' => $congreso->id]);

        $f = $this->filas()[$this->ana->id];
        $this->assertEquals(200, $f['comision_congreso']);
        $this->assertCount(1, $f['avisos']);
    }

    public function test_sobre_lo_cobrado_toma_la_parte_que_entro(): void
    {
        $v = $this->venta($this->ana, [[$this->monitor, 10000]]);
        Cobro::create(['venta_id' => $v->id, 'folio' => 'REC-T-'.uniqid(), 'fecha' => now(), 'monto' => 5000, 'metodo' => 'efectivo', 'registrado_por' => $this->ana->id]);

        $this->assertEquals(200, $this->filas('cobrado')[$this->ana->id]['comision']);  // 4 % de la mitad
        $this->assertEquals(400, $this->filas('vendido')[$this->ana->id]['comision']);
    }

    public function test_la_pantalla_muestra_el_desglose(): void
    {
        $this->venta($this->ana, [[$this->gastro, 10000]]);
        $admin = $this->usuarioApi([], true);

        $this->actingAs($admin)->get(route('commercial.comisiones.index'))->assertOk()
            ->assertSee('endoscopia')->assertSee('repartido entre quienes fueron')->assertSee('$300.00');
    }
}
