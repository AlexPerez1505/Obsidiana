<?php

namespace Tests\Feature\Api\V1\Ventas;

use App\Models\Factura;
use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Ventas\Concerns\DatosDeVentas;
use Tests\TestCase;

class FacturasApiTest extends TestCase
{
    use DatosDeVentas, RefreshDatabase, UsaApi;

    public function test_generar_un_borrador_desde_la_venta_copia_montos_y_la_marca_facturada(): void
    {
        $yo = $this->usuarioApi(['facturacion.crear', 'facturacion.ver']);
        $cliente = $this->cliente(['rfc' => 'XAXX010101000']);
        $venta = $this->ventaDe($yo, [
            'customer_id' => $cliente->id,
            'aplica_iva' => true,
            'items' => [$this->renglonEquipo(1000, 'Lavadora de endoscopios'), $this->renglonEquipo(500, 'Carro')],
        ]);

        $this->conToken($yo)->getJson("/api/v1/facturas/formulario?venta_id={$venta->id}")
            ->assertOk()
            ->assertJsonPath('data.prellenado.customer_id', $cliente->id)
            ->assertJsonPath('data.prellenado.rfc', 'XAXX010101000')
            ->assertJsonPath('data.prellenado.razon_social', 'Cliente De Prueba')
            ->assertJsonPath('data.venta.total', 1740)
            ->assertJsonPath('data.formas_pago.3', ['clave' => '28', 'nombre' => 'Tarjeta de débito'])
            ->assertJsonCount(2, 'data.metodos_pago');

        $res = $this->conToken($yo)->postJson('/api/v1/facturas', [
            'venta_id' => $venta->id,
            'customer_id' => $cliente->id,
            'rfc' => 'XAXX010101000',
            'uso_cfdi' => 'G03',
            'metodo_pago' => 'PUE',
        ])->assertCreated()
            ->assertJsonPath('data.estado', 'borrador')
            ->assertJsonPath('data.total', 1740)
            ->assertJsonPath('data.iva_monto', 240)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.venta.estado', 'facturada');

        $this->assertStringStartsWith('FAC-BORR-', $res->json('data.folio'));
        $this->assertSame('facturada', $venta->refresh()->estado);

        $id = $res->json('data.id');

        $this->conToken($yo)->getJson("/api/v1/facturas/{$id}")->assertOk()->assertJsonPath('data.customer.id', $cliente->id);
        $this->conToken($yo)->get("/api/v1/facturas/{$id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_listado_filtros_validacion_y_borrado(): void
    {
        $yo = $this->usuarioApi(['facturacion.crear', 'facturacion.ver']);
        $uno = $this->cliente(['nombre' => 'Hospital', 'apellido' => 'Central']);
        $dos = $this->cliente();

        $this->conToken($yo)->postJson('/api/v1/facturas', ['customer_id' => $uno->id, 'razon_social' => 'Hospital Central SA'])->assertCreated();
        $this->conToken($yo)->postJson('/api/v1/facturas', ['customer_id' => $dos->id])->assertCreated();

        $this->conToken($yo)->postJson('/api/v1/facturas', ['customer_id' => 999999])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['customer_id']]);

        $this->conToken($yo)->getJson('/api/v1/facturas')->assertOk()->assertJsonCount(2, 'data');
        $this->conToken($yo)->getJson('/api/v1/facturas?buscar=central')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.customer_id', $uno->id);
        $this->conToken($yo)->getJson("/api/v1/facturas?customer_id={$dos->id}")->assertOk()->assertJsonCount(1, 'data');

        $factura = Factura::where('customer_id', $dos->id)->first();
        $this->conToken($yo)->deleteJson("/api/v1/facturas/{$factura->id}")
            ->assertOk()->assertJsonPath('message', "Borrador {$factura->folio} eliminado.");
        $this->assertModelMissing($factura);
    }

    public function test_permisos_de_facturacion(): void
    {
        $soloVer = $this->usuarioApi(['facturacion.ver']);
        $cliente = $this->cliente();

        $this->conToken($soloVer)->getJson('/api/v1/facturas')->assertOk();
        $this->conToken($soloVer)->postJson('/api/v1/facturas', ['customer_id' => $cliente->id])->assertForbidden();
        $this->conToken($soloVer)->getJson('/api/v1/facturas/formulario')->assertForbidden();
        $this->conToken($this->usuarioApi())->getJson('/api/v1/facturas')->assertForbidden();

        $admin = $this->usuarioApi([], admin: true);
        $this->conToken($admin, ['facturacion.ver'], TokenApi::TIPO_INTEGRACION)
            ->postJson('/api/v1/facturas', ['customer_id' => $cliente->id])->assertForbidden();

        $this->assertSame(0, Factura::count());
    }
}
