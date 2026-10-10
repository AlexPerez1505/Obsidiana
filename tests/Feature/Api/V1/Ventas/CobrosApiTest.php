<?php

namespace Tests\Feature\Api\V1\Ventas;

use App\Models\Cobro;
use App\Models\TokenApi;
use App\Models\VentaPago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Ventas\Concerns\DatosDeVentas;
use Tests\TestCase;

class CobrosApiTest extends TestCase
{
    use DatosDeVentas, RefreshDatabase, UsaApi;

    public function test_un_cobro_se_reparte_en_orden_sin_partirse(): void
    {
        Storage::fake('public');
        $yo = $this->usuarioApi(['cobranza.registrar', 'cobranza.ver']);
        $venta = $this->ventaFinanciada($yo);

        $res = $this->conToken($yo)->post("/api/v1/ventas/{$venta->id}/cobros", [
            'fecha' => now()->toDateString(),
            'monto' => 1500,
            'metodo' => 'transferencia',
            'referencia' => 'SPEI 123',
            'evidencias' => [UploadedFile::fake()->image('ticket.jpg')],
        ], ['Accept' => 'application/json']);

        $res->assertCreated()
            ->assertJsonPath('data.monto', 1500)
            ->assertJsonPath('data.metodo_label', 'Transferencia')
            ->assertJsonPath('data.aplicado', 1500)
            ->assertJsonCount(2, 'data.aplicaciones')
            ->assertJsonCount(1, 'data.evidencias')
            ->assertJsonPath('aplicacion.lineas.0.liquida', true)
            ->assertJsonPath('aplicacion.lineas.1.resta', 500);

        $this->assertStringContainsString('Se aplicó a 2 pagos', $res->json('message'));
        $this->assertSame(1, Cobro::count(), 'Un solo recibo por lo que pagó el cliente.');
        Storage::disk('public')->assertExists($venta->cobros()->first()->evidencias()->first()->archivo);

        $this->conToken($yo)->getJson("/api/v1/ventas/{$venta->id}/cobros")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('resumen.total_cobrado', 1500)
            ->assertJsonPath('resumen.saldo', 1500)
            ->assertJsonPath('resumen.estado_pago', 'parcial')
            ->assertJsonPath('parcialidades.0.estado', 'pagado')
            ->assertJsonPath('parcialidades.1.saldo', 500)
            ->assertJsonPath('parcialidades.2.cobrado', 0);
    }

    public function test_no_se_cobra_de_mas_ni_a_una_venta_cancelada(): void
    {
        $yo = $this->usuarioApi(['cobranza.registrar']);
        $venta = $this->ventaFinanciada($yo);

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/cobros", [
            'fecha' => now()->toDateString(), 'monto' => 3000.50, 'metodo' => 'efectivo',
        ])->assertStatus(422)->assertJsonPath('errors.monto.0', 'El monto supera el saldo pendiente ($3,000.00).');

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/cobros", ['metodo' => 'bitcoin'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['fecha', 'monto', 'metodo']]);

        $venta->update(['estado' => 'cancelada']);

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/cobros", [
            'fecha' => now()->toDateString(), 'monto' => 10, 'metodo' => 'efectivo',
        ])->assertStatus(409)->assertJsonPath('message', 'Esta venta está cancelada: no se le pueden registrar cobros.');

        $this->assertSame(0, Cobro::count());
    }

    public function test_permisos_y_alcance_de_la_cobranza(): void
    {
        $soloVer = $this->usuarioApi(['cobranza.ver']);
        $venta = $this->ventaFinanciada($soloVer);

        $this->conToken($soloVer)->postJson("/api/v1/ventas/{$venta->id}/cobros", [
            'fecha' => now()->toDateString(), 'monto' => 10, 'metodo' => 'efectivo',
        ])->assertForbidden();

        // La cobranza de la venta de otro asesor tampoco.
        $otro = $this->usuarioApi(['cobranza.ver']);
        $this->conToken($otro)->getJson("/api/v1/ventas/{$venta->id}/cobros")->assertForbidden();

        // Llave de integración del admin, recortada a ver.
        $admin = $this->usuarioApi([], admin: true);
        $this->conToken($admin, ['cobranza.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson("/api/v1/ventas/{$venta->id}/cobros")->assertForbidden(); // sin ventas.ver_todas no es suya

        $this->conToken($admin, ['cobranza.ver', 'ventas.ver_todas'], TokenApi::TIPO_INTEGRACION)
            ->getJson("/api/v1/ventas/{$venta->id}/cobros")->assertOk();

        $this->conToken($admin, ['cobranza.ver', 'ventas.ver_todas'], TokenApi::TIPO_INTEGRACION)
            ->postJson("/api/v1/ventas/{$venta->id}/parcialidades/rebalancear")->assertForbidden();
    }

    public function test_cancelar_un_cobro_y_bajar_su_recibo(): void
    {
        $yo = $this->usuarioApi(['cobranza.registrar', 'cobranza.ver']);
        $venta = $this->ventaFinanciada($yo);
        $otraVenta = $this->ventaFinanciada($yo);

        $id = $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/cobros", [
            'fecha' => now()->toDateString(), 'monto' => 1000, 'metodo' => 'efectivo',
        ])->assertCreated()->json('data.id');

        $this->conToken($yo)->get("/api/v1/ventas/{$venta->id}/cobros/{$id}/recibo")
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // Por la dirección de otra venta no existe.
        $this->conToken($yo)->deleteJson("/api/v1/ventas/{$otraVenta->id}/cobros/{$id}")
            ->assertNotFound()->assertJsonPath('codigo', 'no_encontrado');

        $this->conToken($yo)->deleteJson("/api/v1/ventas/{$venta->id}/cobros/{$id}")
            ->assertOk()->assertJsonPath('message', 'Cobro cancelado.');

        $this->assertSame(0, Cobro::count());
        $this->assertSame(3000.0, $venta->refresh()->saldo());
        $this->assertDatabaseHas('venta_bitacora', ['venta_id' => $venta->id, 'tipo' => 'cobro_cancelado']);
    }

    public function test_ajustes_al_plan_de_pagos(): void
    {
        $yo = $this->usuarioApi(['cobranza.ajustar', 'cobranza.registrar']);
        $venta = $this->ventaFinanciada($yo);
        [$inicial, $primer, $segundo] = $venta->pagos()->get()->all();

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/cobros", [
            'fecha' => now()->toDateString(), 'monto' => 1000, 'metodo' => 'efectivo',
        ])->assertCreated();

        // No baja de lo cobrado.
        $this->conToken($yo)->putJson("/api/v1/ventas/{$venta->id}/parcialidades/{$inicial->id}", [
            'fecha' => now()->toDateString(), 'monto' => 500,
        ])->assertStatus(422)->assertJsonPath('errors.monto.0', 'No puede quedar por debajo de lo ya cobrado ($1,000.00).');

        // Con cobros no se borra.
        $this->conToken($yo)->deleteJson("/api/v1/ventas/{$venta->id}/parcialidades/{$inicial->id}")
            ->assertStatus(409)->assertJsonPath('message', 'Esa parcialidad ya tiene cobros; cancela primero sus cobros.');

        // Cambiar un monto deja el plan descuadrado; rebalancear lo reparte.
        $this->conToken($yo)->putJson("/api/v1/ventas/{$venta->id}/parcialidades/{$primer->id}", [
            'fecha' => $primer->fecha->toDateString(), 'monto' => 800,
        ])->assertOk()->assertJsonPath('data.monto', 800);

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/parcialidades/rebalancear")
            ->assertOk()
            ->assertJsonPath('message', 'Se repartió la diferencia entre 2 parcialidad(es).')
            ->assertJsonPath('data.1.monto', 900)
            ->assertJsonPath('data.2.monto', 1100);

        // Recorrer: lo cobrado no se mueve.
        $nueva = now()->addMonths(3)->toDateString();
        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/parcialidades/recorrer", ['nueva_fecha' => $nueva])
            ->assertOk()
            ->assertJsonPath('data.0.fecha', $inicial->fecha->toDateString())
            ->assertJsonPath('data.1.fecha', $nueva);

        // Agregar y quitar una parcialidad sin cobros.
        $nuevaId = $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/parcialidades", [
            'nombre' => 'Pago extra', 'fecha' => now()->addYear()->toDateString(), 'monto' => 250,
        ])->assertCreated()->assertJsonPath('data.orden', 3)->json('data.id');

        $this->conToken($yo)->deleteJson("/api/v1/ventas/{$venta->id}/parcialidades/{$nuevaId}")
            ->assertOk()->assertJsonPath('message', 'Parcialidad eliminada.');

        $this->assertDatabaseMissing('venta_pagos', ['id' => $nuevaId]);
        $this->assertSame(3, VentaPago::where('venta_id', $venta->id)->count());
    }

    public function test_absorber_un_abono_suelto(): void
    {
        $yo = $this->usuarioApi(['cobranza.ajustar']);
        $venta = $this->ventaFinanciada($yo);

        // Un cobro viejo que nunca se aplicó al calendario.
        Cobro::create([
            'venta_id' => $venta->id, 'folio' => Cobro::siguienteFolio(), 'fecha' => now()->toDateString(),
            'monto' => 1200, 'metodo' => 'efectivo',
        ]);

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/parcialidades/absorber-excedente")
            ->assertOk()
            ->assertJsonPath('message', 'Se aplicaron $1,200.00 a 2 parcialidad(es) pendiente(s).')
            ->assertJsonPath('data.0.estado', 'pagado')
            ->assertJsonPath('data.1.cobrado', 200);

        $this->conToken($yo)->postJson("/api/v1/ventas/{$venta->id}/parcialidades/absorber-excedente")
            ->assertOk()->assertJsonPath('message', 'No hay excedente por repartir.');
    }
}
