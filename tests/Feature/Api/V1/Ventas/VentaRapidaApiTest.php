<?php

namespace Tests\Feature\Api\V1\Ventas;

use App\Models\Cobro;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\OrdenSalida;
use App\Models\TokenApi;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Ventas\Concerns\DatosDeVentas;
use Tests\TestCase;

class VentaRapidaApiTest extends TestCase
{
    use DatosDeVentas, RefreshDatabase, UsaApi;

    public function test_formulario_y_busqueda_de_pieza(): void
    {
        $yo = $this->usuarioApi(['ventas.crear']);
        $producto = $this->productoConUnidades(1, ['SN-FAB-77'], precio: 850);
        $pieza = $producto->seriales()->first();

        $this->conToken($yo)->getJson('/api/v1/ventas/rapida/formulario')
            ->assertOk()
            ->assertJsonPath('data.metodos.0', ['clave' => 'efectivo', 'nombre' => 'Efectivo'])
            ->assertJsonPath('data.garantias', Venta::GARANTIAS)
            ->assertJsonPath('data.iva_tasa', 0.16);

        // Por la liga del QR, por la etiqueta o por el número de serie del fabricante.
        foreach (['https://obsidiana.test/equipo/'.$pieza->codigo, $pieza->codigo, 'sn-fab-77'] as $leido) {
            $this->conToken($yo)->getJson('/api/v1/ventas/rapida/pieza?codigo='.urlencode($leido))
                ->assertOk()
                ->assertJsonPath('data.serial_id', $pieza->id)
                ->assertJsonPath('data.vendible', true)
                ->assertJsonPath('data.precio', 850)
                ->assertJsonStructure(['data' => ['foto_url']]);
        }

        $this->conToken($yo)->getJson('/api/v1/ventas/rapida/pieza?codigo=MB-999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'No hay ninguna pieza con la etiqueta o número de serie "MB-999999".');

        $this->conToken($yo)->getJson('/api/v1/ventas/rapida/pieza')->assertStatus(422);
    }

    public function test_cobrar_crea_la_venta_entregada_con_su_cobro_y_baja_el_stock(): void
    {
        $yo = $this->usuarioApi(['ventas.crear']);
        $producto = $this->productoConUnidades(3, precio: 100);
        $escaneada = $producto->seriales()->orderByDesc('id')->first();

        $res = $this->conToken($yo)->postJson('/api/v1/ventas/rapida', [
            'items' => [[
                'producto_id' => $producto->id,
                'cantidad' => 2,
                'precio_unitario' => 100,
                'seriales' => [$escaneada->id],
            ]],
            'metodo' => 'tarjeta',
            'nota' => 'Dr. Ramírez',
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.estado', 'confirmada')
            ->assertJsonPath('data.total', 200)
            ->assertJsonPath('data.saldo', 0)
            ->assertJsonPath('data.customer.publico_en_general', true)
            ->assertJsonPath('data.orden_salida.estado', OrdenSalida::ENTREGADA)
            ->assertJsonPath('cobro.monto', 200)
            ->assertJsonPath('cobro.metodo', 'tarjeta');

        $this->assertStringContainsString('Dr. Ramírez', $res->json('message'));
        $this->assertSame(1, $producto->refresh()->stock);
        $this->assertTrue((bool) $escaneada->refresh()->vendido, 'La pieza escaneada es la que sale.');
        $this->assertNotNull(InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)->sole()->entregado_en);
    }

    public function test_si_no_alcanza_no_se_crea_nada(): void
    {
        $yo = $this->usuarioApi(['ventas.crear']);
        $producto = $this->productoConUnidades(1);

        $this->conToken($yo)->postJson('/api/v1/ventas/rapida', [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 100]],
            'metodo' => 'efectivo',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['items']]);

        $this->conToken($yo)->postJson('/api/v1/ventas/rapida', ['metodo' => 'cheque'])
            ->assertStatus(422)
            ->assertJsonPath('errors.items.0', 'Escanea al menos una pieza.')
            ->assertJsonStructure(['errors' => ['metodo']]);

        $this->assertSame(0, Venta::count());
        $this->assertSame(0, Cobro::count());
        $this->assertSame(1, $producto->refresh()->stock);
    }

    public function test_no_vende_a_clientes_ajenos_ni_sin_permiso(): void
    {
        $yo = $this->usuarioApi(['ventas.crear']);
        $producto = $this->productoConUnidades(1);
        $ajeno = Customer::create(['nombre' => 'Ajeno', 'asesor_id' => $this->usuarioApi()->id]);

        $this->conToken($yo)->postJson('/api/v1/ventas/rapida', [
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 100]],
            'metodo' => 'efectivo',
            'customer_id' => $ajeno->id,
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['customer_id']]);

        $admin = $this->usuarioApi([], admin: true);
        $this->conToken($admin, ['ventas.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/ventas/rapida/formulario')->assertForbidden();

        $this->assertSame(0, Venta::count());
    }
}
