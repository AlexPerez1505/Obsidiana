<?php

namespace Tests\Feature\Api\V1\Ventas;

use App\Models\Cobro;
use App\Models\Cotizacion;
use App\Models\InventoryMovement;
use App\Models\OrdenSalida;
use App\Models\TokenApi;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Ventas\Concerns\DatosDeVentas;
use Tests\TestCase;

class VentasApiTest extends TestCase
{
    use DatosDeVentas, RefreshDatabase, UsaApi;

    public function test_el_listado_trae_saldos_y_respeta_el_alcance_del_asesor(): void
    {
        $yo = $this->usuarioApi(['ventas.ver']);
        $otro = $this->usuarioApi();

        $mia = $this->ventaFinanciada($yo);
        $this->ventaFinanciada($otro);

        $res = $this->conToken($yo)->getJson('/api/v1/ventas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mia->id)
            ->assertJsonPath('data.0.total', 3000)
            ->assertJsonPath('data.0.saldo', 3000)
            ->assertJsonPath('data.0.estado_pago', 'pendiente')
            ->assertJsonPath('data.0.seller.id', $yo->id)
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'total']]);

        $this->assertIsFloat($res->json('data.0.subtotal') + 0.0);

        // Con ver_todas aparecen las de todos.
        $jefe = $this->usuarioApi(['ventas.ver', 'ventas.ver_todas']);
        $this->conToken($jefe)->getJson('/api/v1/ventas')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_filtros_del_listado(): void
    {
        $yo = $this->usuarioApi(['ventas.ver']);
        $cliente = $this->cliente(['nombre' => 'Hospital', 'apellido' => 'Ángeles']);
        $buscada = $this->ventaDe($yo, ['customer_id' => $cliente->id, 'items' => [$this->renglonEquipo(100)]]);
        $this->ventaDe($yo, ['customer_id' => $this->cliente()->id, 'items' => [$this->renglonEquipo(200)]]);

        $this->conToken($yo)->getJson('/api/v1/ventas?buscar=hospital')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $buscada->id);

        $this->conToken($yo)->getJson('/api/v1/ventas?estado=cancelada')
            ->assertOk()->assertJsonCount(0, 'data');

        $this->conToken($yo)->getJson('/api/v1/ventas?orden=total')
            ->assertOk()->assertJsonPath('data.0.id', $buscada->id);

        $this->conToken($yo)->getJson('/api/v1/ventas?desde=10-10-2026')
            ->assertStatus(422)->assertJsonStructure(['errors' => ['desde']]);
    }

    public function test_sin_permiso_o_con_llave_recortada_da_403(): void
    {
        $sinPermiso = $this->usuarioApi();
        $this->conToken($sinPermiso)->getJson('/api/v1/ventas')
            ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');

        // El dueño puede todo, pero la llave solo trae ventas.ver.
        $admin = $this->usuarioApi([], admin: true);
        $llave = fn () => $this->conToken($admin, ['ventas.ver'], TokenApi::TIPO_INTEGRACION);

        $llave()->getJson('/api/v1/ventas')->assertOk();
        $llave()->postJson('/api/v1/ventas', [])->assertForbidden();
    }

    public function test_la_venta_de_otro_asesor_no_se_abre(): void
    {
        $yo = $this->usuarioApi(['ventas.ver', 'ventas.editar']);
        $ajena = $this->ventaFinanciada($this->usuarioApi());

        $this->conToken($yo)->getJson("/api/v1/ventas/{$ajena->id}")->assertForbidden();
        $this->conToken($yo)->getJson("/api/v1/ventas/{$ajena->id}/pdf")->assertForbidden();
        $this->conToken($yo)->getJson('/api/v1/ventas/999999')->assertNotFound()->assertJsonPath('codigo', 'no_encontrado');
    }

    public function test_registrar_una_venta_baja_el_stock_y_genera_la_orden_de_salida(): void
    {
        $yo = $this->usuarioApi(['ventas.crear']);
        $cliente = $this->cliente();
        $producto = $this->productoConUnidades(3, ['SN001', 'SN002', 'SN003']);

        $res = $this->conToken($yo)->postJson('/api/v1/ventas', [
            'customer_id' => $cliente->id,
            'modalidad' => 'financiamiento',
            'num_meses' => 2,
            'aplica_iva' => true,
            'items' => [$this->renglonProducto($producto, 2)],
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.seller_id', $yo->id)
            ->assertJsonPath('data.subtotal', 1000)
            ->assertJsonPath('data.iva_monto', 160)
            ->assertJsonPath('data.total', 1160)
            ->assertJsonCount(3, 'data.pagos')
            ->assertJsonPath('data.items.0.no_series', 'SN001, SN002')
            ->assertJsonPath('data.orden_salida.estado', OrdenSalida::PENDIENTE);

        $this->assertStringContainsString('registrada correctamente', $res->json('message'));
        $this->assertSame(1, $producto->refresh()->stock);

        $salida = InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)->sole();
        $this->assertSame(2, $salida->quantity);
        $this->assertSame($res->json('data.folio'), $salida->reference);
    }

    public function test_registrar_valida_igual_que_la_web(): void
    {
        $yo = $this->usuarioApi(['ventas.crear']);

        $this->conToken($yo)->postJson('/api/v1/ventas', ['modalidad' => 'otra'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['customer_id', 'modalidad', 'items']]);

        $this->assertSame(0, Venta::count());
    }

    public function test_desde_una_cotizacion_toma_lo_que_falta_y_la_marca_convertida(): void
    {
        $yo = $this->usuarioApi(['ventas.crear']);
        $cotizacion = $this->cotizacionDe($yo);

        // Solo se manda la cotización: todo lo demás sale de ella.
        $res = $this->conToken($yo)->postJson('/api/v1/ventas', ['cotizacion_id' => $cotizacion->id])
            ->assertCreated()
            ->assertJsonPath('data.cotizacion_id', $cotizacion->id)
            ->assertJsonPath('data.customer_id', $cotizacion->customer_id)
            ->assertJsonPath('data.total', 2000)
            ->assertJsonPath('data.items.0.nombre', 'Videogastroscopio');

        $this->assertSame('convertida', $cotizacion->refresh()->estado);
        $this->assertSame(1, Venta::count());

        // Las fechas del plan se recorren para que el primer pago caiga hoy.
        $this->assertSame(now()->toDateString(), $res->json('data.pagos.0.fecha'));
    }

    public function test_no_se_convierte_la_cotizacion_de_otro_asesor(): void
    {
        $yo = $this->usuarioApi(['ventas.crear']);
        $ajena = $this->cotizacionDe($this->usuarioApi());

        $this->conToken($yo)->postJson('/api/v1/ventas', ['cotizacion_id' => $ajena->id])->assertForbidden();
        $this->conToken($yo)->getJson("/api/v1/ventas/formulario?cotizacion_id={$ajena->id}")->assertForbidden();

        $this->assertSame(0, Venta::count());
        $this->assertNotSame('convertida', $ajena->refresh()->estado);
    }

    public function test_formulario_prellena_desde_la_cotizacion_con_avisos_de_stock(): void
    {
        $yo = $this->usuarioApi(['ventas.crear']);
        $producto = $this->productoConUnidades(1);
        $cotizacion = $this->cotizacionDe($yo, $producto, 3);

        $this->conToken($yo)->getJson("/api/v1/ventas/formulario?cotizacion_id={$cotizacion->id}")
            ->assertOk()
            ->assertJsonPath('data.prellenado.customer_id', $cotizacion->customer_id)
            ->assertJsonPath('data.prellenado.items.0.producto_id', $producto->id)
            ->assertJsonPath('data.prellenado.pagos.0.fecha', now()->toDateString())
            ->assertJsonPath('data.cotizacion.id', $cotizacion->id)
            ->assertJsonCount(1, 'data.avisos_stock')
            ->assertJsonPath('data.garantias', Venta::GARANTIAS);
    }

    public function test_editar_rehace_los_renglones_y_regresa_el_stock(): void
    {
        $yo = $this->usuarioApi(['ventas.editar']);
        $cliente = $this->cliente();
        $producto = $this->productoConUnidades(2);
        $venta = $this->ventaDe($yo, ['customer_id' => $cliente->id, 'items' => [$this->renglonProducto($producto, 2)]]);
        $this->assertSame(0, $producto->refresh()->stock);

        $this->conToken($yo)->putJson("/api/v1/ventas/{$venta->id}", [
            'customer_id' => $cliente->id,
            'modalidad' => 'contado',
            'items' => [$this->renglonProducto($producto, 1)],
        ])
            ->assertOk()
            ->assertJsonPath('data.total', 500)
            ->assertJsonPath('message', "Venta {$venta->folio} actualizada.");

        $this->assertSame(1, $producto->refresh()->stock);
    }

    public function test_editar_no_deja_el_total_debajo_de_lo_cobrado_ni_toca_canceladas(): void
    {
        $yo = $this->usuarioApi(['ventas.editar']);
        $venta = $this->ventaFinanciada($yo);
        $this->cobrar($venta, 2000);

        $this->conToken($yo)->putJson("/api/v1/ventas/{$venta->id}", [
            'customer_id' => $venta->customer_id,
            'modalidad' => 'financiamiento',
            'items' => [$this->renglonEquipo(1000)],
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['items']]);

        $this->assertSame(3000.0, (float) $venta->refresh()->total);

        $venta->update(['estado' => 'cancelada']);

        $this->conToken($yo)->putJson("/api/v1/ventas/{$venta->id}", [])
            ->assertStatus(409)
            ->assertJsonPath('codigo', 'conflicto')
            ->assertJsonPath('message', 'Una venta cancelada no se edita.');
    }

    public function test_cancelar_pide_pin_y_regresa_el_equipo(): void
    {
        $yo = $this->usuarioApi(['ventas.editar']);
        $producto = $this->productoConUnidades(1);
        $venta = $this->ventaDe($yo, ['customer_id' => $this->cliente()->id, 'items' => [$this->renglonProducto($producto, 1)]]);

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/cancelar", ['motivo' => 'Se arrepintió'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['password']]);

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/cancelar", ['motivo' => 'Se arrepintió', 'password' => 'otra'])
            ->assertStatus(422)->assertJsonPath('errors.password.0', 'PIN o contraseña incorrecta.');

        $this->assertSame(0, $producto->refresh()->stock);

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/cancelar", ['motivo' => 'Se arrepintió', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'cancelada')
            ->assertJsonPath('data.orden_salida.estado', OrdenSalida::CANCELADA);

        $this->assertSame(1, $producto->refresh()->stock);

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/cancelar", ['motivo' => 'Otra vez', 'password' => 'password'])
            ->assertStatus(409)->assertJsonPath('message', 'Esta venta ya estaba cancelada.');
    }

    public function test_eliminar_solo_sin_cobros(): void
    {
        $yo = $this->usuarioApi(['ventas.eliminar']);
        $conCobro = $this->ventaFinanciada($yo);
        $this->cobrar($conCobro, 100);

        $this->conToken($yo)->deleteJson("/api/v1/ventas/{$conCobro->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Esta venta ya tiene cobros registrados: no se puede eliminar, solo cancelar.');

        $producto = $this->productoConUnidades(1);
        $limpia = $this->ventaDe($yo, ['customer_id' => $this->cliente()->id, 'items' => [$this->renglonProducto($producto, 1)]]);

        $this->conToken($yo)->deleteJson("/api/v1/ventas/{$limpia->id}")
            ->assertOk()->assertJsonPath('message', "Venta {$limpia->folio} eliminada.");

        $this->assertModelMissing($limpia);
        $this->assertSame(1, $producto->refresh()->stock);
    }

    public function test_pdfs_de_la_venta(): void
    {
        $yo = $this->usuarioApi(['ventas.ver']);
        $plazos = $this->ventaFinanciada($yo);
        $contado = $this->ventaDe($yo, ['customer_id' => $this->cliente()->id, 'garantia_meses' => 0, 'items' => [$this->renglonEquipo(100)]]);

        $this->conToken($yo)->get("/api/v1/ventas/{$plazos->id}/contrato")
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->conToken($yo)->getJson("/api/v1/ventas/{$contado->id}/contrato")
            ->assertNotFound()->assertJsonPath('message', 'El contrato solo aplica a ventas a plazos.');

        $this->conToken($yo)->getJson("/api/v1/ventas/{$contado->id}/carta-garantia")
            ->assertNotFound()->assertJsonPath('message', 'Esta venta se registró sin garantía: no hay carta garantía que generar.');

        $this->conToken($yo)->get("/api/v1/ventas/{$contado->id}/pdf")
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    private function cobrar(Venta $venta, float $monto): Cobro
    {
        return Cobro::create([
            'venta_id' => $venta->id,
            'folio' => Cobro::siguienteFolio(),
            'fecha' => now()->toDateString(),
            'monto' => $monto,
            'metodo' => 'efectivo',
        ]);
    }

    private function cotizacionDe($asesor, $producto = null, int $cantidad = 1): Cotizacion
    {
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-PRUEBA-'.uniqid(),
            'customer_id' => $this->cliente()->id,
            'seller_id' => $asesor->id,
            'modalidad' => 'financiamiento',
            'aplica_iva' => false,
            'subtotal' => 2000,
            'total' => 2000,
            'total_contrato' => 2000,
            'num_meses' => 1,
            'garantia_meses' => 6,
            'estado' => 'enviada',
        ]);

        $cotizacion->items()->create($producto ? [
            'tipo_item' => 'producto',
            'producto_id' => $producto->id,
            'nombre' => 'Videogastroscopio',
            'cantidad' => $cantidad,
            'precio_unitario' => 2000 / $cantidad,
            'orden' => 0,
        ] : [
            'tipo_item' => 'equipo',
            'nombre' => 'Videogastroscopio',
            'cantidad' => 1,
            'precio_unitario' => 2000,
            'orden' => 0,
        ]);

        // Se cotizó hace tres semanas: el plan viene desfasado.
        $cotizacion->pagos()->create(['nombre' => 'Pago inicial', 'fecha' => now()->subWeeks(3), 'monto' => 1000, 'porcentaje' => 50, 'orden' => 0]);
        $cotizacion->pagos()->create(['nombre' => 'Primer pago', 'fecha' => now()->subWeeks(3)->addMonth(), 'monto' => 1000, 'porcentaje' => 50, 'orden' => 1]);

        return $cotizacion;
    }
}
