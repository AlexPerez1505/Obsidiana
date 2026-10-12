<?php

namespace Tests\Feature\Api\V1\Comercial;

use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\FichaTecnica;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\TokenApi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class CotizacionesTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private Customer $cliente;

    private Producto $producto;

    private Paquete $paquete;

    private FichaTecnica $ficha;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cliente = Customer::create([
            'nombre' => 'Hospital',
            'apellido' => 'San José',
            'telefono' => '7225550000',
            'direccion' => 'Toluca',
            'etapa' => 'cliente',
            'activo' => true,
            'recibe_promocion' => false,
        ]);
        $this->producto = Producto::create(['tipo_equipo' => 'Torre', 'marca' => 'Stryker', 'modelo' => '1588', 'precio' => 10000, 'stock' => 0]);
        $this->paquete = Paquete::create(['nombre' => 'Kit de cortesía', 'precio' => 3000, 'activo' => true]);
        $this->ficha = FichaTecnica::create(['titulo' => 'Ficha 1588', 'activo' => true, 'producto_id' => $this->producto->id]);
    }

    /**
     * A financiamiento, con IVA, descuento, envío, valor a cuenta, un regalo
     * y un pago bloqueado. Trae montos falsos que el servidor debe ignorar.
     */
    private function payload(array $cambios = []): array
    {
        return $cambios + [
            'customer_id' => $this->cliente->id,
            'modalidad' => 'financiamiento',
            'aplica_iva' => true,
            'descuento_tipo' => 'porcentaje',
            'descuento_valor' => 10,
            'envio' => 500,
            'valor_a_cuenta' => 1000,
            'num_meses' => 2,
            'plan_nombre' => 'Plan 3 pagos',
            'garantia_meses' => 12,
            'nota_cliente' => 'Precios en MXN',
            'subtotal' => 1,
            'total' => 1,
            'items' => [
                [
                    'tipo_item' => 'producto',
                    'producto_id' => $this->producto->id,
                    'nombre' => 'Torre',
                    'marca' => 'Stryker',
                    'modelo' => '1588',
                    'imagen' => 'https://ejemplo.test/torre.jpg',
                    'cantidad' => 2,
                    'precio_unitario' => 10000,
                    'sobreprecio' => 500,
                ],
                [
                    'tipo_item' => 'paquete',
                    'paquete_id' => $this->paquete->id,
                    'nombre' => 'Kit de cortesía',
                    'cantidad' => 1,
                    'precio_unitario' => 3000,
                    'es_regalo' => true,
                ],
            ],
            'pagos' => [
                ['nombre' => 'Enganche', 'fecha' => now()->addDays(3)->toDateString(), 'monto' => 5000, 'bloqueado' => true],
                // Monto libre falso: se recalcula.
                ['nombre' => 'Primer pago', 'monto' => 1],
            ],
            'fichas' => [$this->ficha->id],
        ];
    }

    private function cotizacionDe(User $vendedor, string $folio): Cotizacion
    {
        return Cotizacion::create(['folio' => $folio, 'customer_id' => $this->cliente->id, 'seller_id' => $vendedor->id, 'modalidad' => 'contado', 'total' => 100]);
    }

    /* ===================== Crear ===================== */

    public function test_crear_recalcula_montos_y_plan_de_pagos(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.crear', 'cotizaciones.ver']);
        $anio = now()->year;

        $res = $this->conToken($yo)->postJson('/api/v1/cotizaciones', $this->payload());

        // subtotal (10000+500)×2 = 21000 (el regalo no suma) − 10% = 18900
        // + 500 de envío = 19400 + IVA 3104 = 22504; − 1000 a cuenta = 21504.
        $res->assertCreated()
            ->assertJsonPath('message', "Cotización COT-{$anio}-0001 guardada correctamente.")
            ->assertJsonPath('data.folio', "COT-{$anio}-0001")
            ->assertJsonPath('data.seller_id', $yo->id)
            ->assertJsonPath('data.estado', 'borrador')
            ->assertJsonPath('data.subtotal', 21000)
            ->assertJsonPath('data.descuento_monto', 2100)
            ->assertJsonPath('data.iva_monto', 3104)
            ->assertJsonPath('data.total', 22504)
            ->assertJsonPath('data.total_contrato', 21504)
            ->assertJsonPath('data.num_meses', 2)
            ->assertJsonPath('data.garantia_meses', 12)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.imagen_url', 'https://ejemplo.test/torre.jpg')
            ->assertJsonPath('data.items.0.importe', 21000)
            ->assertJsonPath('data.items.1.importe', 0)
            ->assertJsonPath('data.items.1.paquete_id', $this->paquete->id)
            ->assertJsonCount(3, 'data.pagos')
            ->assertJsonPath('data.pagos.0.nombre', 'Enganche')
            ->assertJsonPath('data.pagos.0.monto', 5000)
            ->assertJsonPath('data.pagos.1.monto', 8252)
            ->assertJsonPath('data.pagos.2.monto', 8252)
            ->assertJsonPath('data.pagos.2.nombre', 'Segundo pago')
            ->assertJsonPath('data.fichas.0.id', $this->ficha->id)
            ->assertJsonPath('data.customer.id', $this->cliente->id);

        $this->assertStringContainsString('/consulta/cotizacion/', $res->json('data.public_url'));
        $this->assertDatabaseHas('cotizacion_ficha', ['cotizacion_id' => $res->json('data.id'), 'ficha_tecnica_id' => $this->ficha->id]);
    }

    public function test_la_api_guarda_exactamente_lo_mismo_que_la_web(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.crear', 'cotizaciones.ver']);
        $anio = now()->year;

        $this->actingAs($yo)->post(route('commercial.cotizaciones.store'), $this->payload())
            ->assertRedirect();

        $this->conToken($yo)->postJson('/api/v1/cotizaciones', $this->payload())->assertCreated();

        [$web, $api] = Cotizacion::with(['items', 'pagos', 'fichas'])->orderBy('id')->get()->all();

        $this->assertSame("COT-{$anio}-0001", $web->folio);
        $this->assertSame("COT-{$anio}-0002", $api->folio);

        $cabecera = fn (Cotizacion $c) => collect($c->getAttributes())
            ->except(['id', 'folio', 'public_token', 'created_at', 'updated_at'])->all();
        $this->assertEquals($cabecera($web), $cabecera($api));

        $renglones = fn (Cotizacion $c, string $rel) => $c->{$rel}->map(fn ($r) => collect($r->getAttributes())
            ->except(['id', 'cotizacion_id', 'created_at', 'updated_at'])->all())->all();
        $this->assertEquals($renglones($web, 'items'), $renglones($api, 'items'));
        $this->assertEquals($renglones($web, 'pagos'), $renglones($api, 'pagos'));
        $this->assertEquals($web->fichas->pluck('id'), $api->fichas->pluck('id'));
    }

    public function test_crear_invalido_da_422(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.crear']);

        $this->conToken($yo)->postJson('/api/v1/cotizaciones', $this->payload(['items' => [], 'modalidad' => 'trueque']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items', 'modalidad']);

        $this->assertSame(0, Cotizacion::count());
    }

    public function test_crear_sin_permiso_o_con_llave_recortada_da_403(): void
    {
        $soloVer = $this->usuarioApi(['cotizaciones.ver']);
        $this->conToken($soloVer)->postJson('/api/v1/cotizaciones', $this->payload())->assertForbidden();

        $creador = $this->usuarioApi(['cotizaciones.ver', 'cotizaciones.crear']);
        $llave = $this->conToken($creador, ['cotizaciones.ver'], TokenApi::TIPO_INTEGRACION);
        $llave->postJson('/api/v1/cotizaciones', $this->payload())->assertForbidden();
        $llave->getJson('/api/v1/cotizaciones')->assertOk();

        $this->assertSame(0, Cotizacion::count());
    }

    /* ===================== Calcular ===================== */

    public function test_calcular_da_la_misma_cuenta_sin_guardar(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.editar']);

        $this->conToken($yo)->postJson('/api/v1/cotizaciones/calcular', collect($this->payload())->except('customer_id')->all())
            ->assertOk()
            ->assertJsonPath('data.subtotal', 21000)
            ->assertJsonPath('data.base', 19400)
            ->assertJsonPath('data.total', 22504)
            ->assertJsonPath('data.total_contrato', 21504)
            ->assertJsonCount(3, 'data.pagos')
            ->assertJsonPath('data.pagos.1.monto', 8252);

        $this->assertSame(0, Cotizacion::count());

        $this->conToken($this->usuarioApi(['cotizaciones.ver']))
            ->postJson('/api/v1/cotizaciones/calcular', $this->payload())->assertForbidden();
    }

    /* ===================== Listado y detalle ===================== */

    public function test_sin_ver_todas_solo_lista_y_abre_las_propias(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.ver']);
        $otro = $this->usuarioApi();
        $mia = $this->cotizacionDe($yo, 'COT-2026-0001');
        $ajena = $this->cotizacionDe($otro, 'COT-2026-0002');

        $peticion = $this->conToken($yo);

        $peticion->getJson('/api/v1/cotizaciones')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mia->id);

        $peticion->getJson('/api/v1/cotizaciones/'.$ajena->id)
            ->assertForbidden()->assertJsonPath('message', 'Este documento lo hizo otro asesor.');
        $peticion->getJson('/api/v1/cotizaciones/'.$ajena->id.'/pdf')->assertForbidden();
        $peticion->getJson('/api/v1/cotizaciones/999999')->assertNotFound();

        $peticion->getJson('/api/v1/cotizaciones/'.$mia->id)
            ->assertOk()->assertJsonPath('data.folio', 'COT-2026-0001')->assertJsonPath('data.total', 100);
    }

    public function test_con_ver_todas_lista_todo_y_filtra(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.ver', 'cotizaciones.ver_todas']);
        $otro = $this->usuarioApi();
        $this->cotizacionDe($yo, 'COT-2026-0001');
        $ajena = $this->cotizacionDe($otro, 'COT-2026-0002');
        $ajena->update(['estado' => 'convertida']);

        $peticion = $this->conToken($yo);

        $peticion->getJson('/api/v1/cotizaciones')->assertOk()->assertJsonCount(2, 'data');
        $peticion->getJson('/api/v1/cotizaciones?estado=convertida')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.estado_label', 'Convertida a venta');
        $peticion->getJson('/api/v1/cotizaciones?buscar=0002')->assertOk()->assertJsonCount(1, 'data');
        $peticion->getJson('/api/v1/cotizaciones?buscar=San%20Jos%C3%A9')->assertOk()->assertJsonCount(2, 'data');
        $peticion->getJson('/api/v1/cotizaciones?orden=folio')->assertOk()->assertJsonPath('data.0.folio', 'COT-2026-0001');
        $peticion->getJson('/api/v1/cotizaciones?estado=perdida')->assertStatus(422);
    }

    /* ===================== Editar, eliminar, PDF ===================== */

    public function test_editar_reemplaza_renglones_plan_y_fichas(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.crear', 'cotizaciones.editar']);
        $peticion = $this->conToken($yo);
        $id = $peticion->postJson('/api/v1/cotizaciones', $this->payload())->assertCreated()->json('data.id');

        $peticion->putJson("/api/v1/cotizaciones/{$id}", [
            'customer_id' => $this->cliente->id,
            'modalidad' => 'contado',
            'items' => [
                ['tipo_item' => 'producto', 'producto_id' => $this->producto->id, 'nombre' => 'Torre', 'cantidad' => 1, 'precio_unitario' => 9000],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Cotización COT-'.now()->year.'-0001 actualizada.')
            ->assertJsonPath('data.total', 9000)
            ->assertJsonPath('data.plan_nombre', null)
            ->assertJsonPath('data.num_meses', 0)
            ->assertJsonPath('data.garantia_meses', 6)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonCount(0, 'data.pagos')
            ->assertJsonCount(0, 'data.fichas');

        $this->assertDatabaseCount('cotizacion_items', 1);
        $this->assertDatabaseCount('cotizacion_pagos', 0);
    }

    public function test_eliminar_pide_su_permiso(): void
    {
        $editor = $this->usuarioApi(['cotizaciones.editar']);
        $cot = $this->cotizacionDe($editor, 'COT-2026-0001');

        $this->conToken($editor)->deleteJson('/api/v1/cotizaciones/'.$cot->id)->assertForbidden();

        $quien = $this->usuarioApi(['cotizaciones.eliminar', 'cotizaciones.ver_todas']);
        $this->conToken($quien)->deleteJson('/api/v1/cotizaciones/'.$cot->id)
            ->assertOk()->assertJsonPath('message', 'Cotización COT-2026-0001 eliminada.');

        $this->assertDatabaseMissing('cotizaciones', ['id' => $cot->id]);
    }

    public function test_pdf_es_el_de_la_web(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.crear', 'cotizaciones.ver']);
        $id = $this->conToken($yo)->postJson('/api/v1/cotizaciones', $this->payload(['fichas' => []]))->json('data.id');

        $res = $this->conToken($yo)->get("/api/v1/cotizaciones/{$id}/pdf");

        $res->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->assertStringContainsString('COT-'.now()->year.'-0001.pdf', $res->headers->get('Content-Disposition'));
    }

    public function test_catalogos(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.ver']);

        $this->conToken($yo)->getJson('/api/v1/cotizaciones/catalogos')
            ->assertOk()
            ->assertJsonPath('data.garantias_meses', [0, 6, 9, 12, 18, 36])
            ->assertJsonPath('data.iva_tasa', 0.16)
            ->assertJsonPath('data.estados.4.label', 'Convertida a venta');
    }
}
