<?php

namespace Tests\Feature\Api\V1\Ventas;

use App\Models\Cobro;
use App\Models\ComisionPago;
use App\Models\TokenApi;
use App\Models\Venta;
use App\Services\CalendarioPagos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Ventas\Concerns\DatosDeVentas;
use Tests\TestCase;

class CobranzaComisionesApiTest extends TestCase
{
    use DatosDeVentas, RefreshDatabase, UsaApi;

    private function cobrar(Venta $venta, float $monto, ?string $fecha = null): Cobro
    {
        $cobro = Cobro::create([
            'venta_id' => $venta->id,
            'folio' => Cobro::siguienteFolio(),
            'fecha' => $fecha ?? now()->toDateString(),
            'monto' => $monto,
            'metodo' => 'efectivo',
        ]);

        app(CalendarioPagos::class)->aplicar($venta->refresh(), $cobro);

        return $cobro;
    }

    public function test_quien_debe_con_conteos_y_alcance(): void
    {
        $yo = $this->usuarioApi(['cobranza.ver']);
        $debe = $this->ventaFinanciada($yo, $this->cliente(['nombre' => 'Clínica', 'apellido' => 'Norte']));
        $pagada = $this->ventaDe($yo, ['customer_id' => $this->cliente()->id, 'items' => [$this->renglonEquipo(500)]]);
        $this->cobrar($pagada, 500);
        $this->ventaFinanciada($this->usuarioApi()); // de otro asesor: no aparece

        $this->conToken($yo)->getJson('/api/v1/cobranza')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.venta_id', $debe->id)
            ->assertJsonPath('data.0.saldo', 3000)
            ->assertJsonPath('data.0.cliente', 'Clínica Norte')
            ->assertJsonPath('conteos', ['todas' => 2, 'deben' => 1, 'atrasadas' => 0, 'pagadas' => 1])
            ->assertJsonPath('resumen.por_cobrar', 3000)
            ->assertJsonPath('resumen.cobrado_mes', 500)
            ->assertJsonPath('meta.total', 1);

        $this->conToken($yo)->getJson('/api/v1/cobranza?estado=pagadas')
            ->assertOk()->assertJsonPath('data.0.venta_id', $pagada->id);

        $this->conToken($yo)->getJson('/api/v1/cobranza?buscar=norte&estado=todas')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->conToken($yo)->getJson('/api/v1/cobranza?estado=raro')->assertStatus(422);
    }

    public function test_panorama_de_cobranza(): void
    {
        $yo = $this->usuarioApi(['cobranza.ver']);
        $venta = $this->ventaFinanciada($yo);
        $this->cobrar($venta, 1000);

        $this->conToken($yo)->getJson('/api/v1/cobranza/panorama')
            ->assertOk()
            ->assertJsonCount(12, 'data.por_mes.meses')
            ->assertJsonPath('data.asesores.filas.0.seller_id', $yo->id)
            ->assertJsonPath('data.asesores.filas.0.cobrado', 1000)
            ->assertJsonPath('data.lista_asesores', [['id' => $yo->id, 'name' => $yo->name]])
            ->assertJsonPath('data.resumen.por_cobrar', 2000);

        $this->conToken($this->usuarioApi(['ventas.ver']))->getJson('/api/v1/cobranza/panorama')->assertForbidden();
    }

    public function test_comisiones_del_mes(): void
    {
        $jefe = $this->usuarioApi(['comisiones.ver', 'comisiones.gestionar']);
        $asesor = $this->usuarioApi();
        $asesor->forceFill(['porcentaje_comision' => 10])->save();

        $venta = $this->ventaFinanciada($asesor);
        $this->cobrar($venta, 2000);

        $res = $this->conToken($jefe)->getJson('/api/v1/comisiones?periodo='.now()->format('Y-m'))
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $asesor->id)
            ->assertJsonPath('data.0.vendido', 3000)
            ->assertJsonPath('data.0.cobrado', 2000)
            ->assertJsonPath('data.0.comision', 200)
            ->assertJsonPath('data.0.pendiente', 200)
            ->assertJsonPath('base', 'cobrado')
            ->assertJsonPath('puede_gestionar', true);

        $this->assertSame(now()->format('Y-m'), $res->json('periodo'));

        $this->conToken($jefe)->getJson('/api/v1/comisiones?base=vendido')
            ->assertOk()->assertJsonPath('data.0.comision', 300);
    }

    public function test_porcentaje_y_pagos_de_comision(): void
    {
        $jefe = $this->usuarioApi(['comisiones.ver', 'comisiones.gestionar']);
        $soloVer = $this->usuarioApi(['comisiones.ver']);
        $asesor = $this->usuarioApi();

        $this->conToken($soloVer)->putJson("/api/v1/comisiones/asesores/{$asesor->id}/porcentaje", ['porcentaje' => 5])
            ->assertForbidden();

        $this->conToken($jefe)->putJson("/api/v1/comisiones/asesores/{$asesor->id}/porcentaje", ['porcentaje' => 7.5])
            ->assertOk()->assertJsonPath('data.porcentaje_comision', 7.5);
        $this->assertSame(7.5, (float) $asesor->refresh()->porcentaje_comision);

        $this->conToken($jefe)->putJson("/api/v1/comisiones/asesores/{$asesor->id}/porcentaje", ['porcentaje' => 150])
            ->assertStatus(422);

        $this->conToken($jefe)->postJson('/api/v1/comisiones/pagos', [
            'user_id' => $asesor->id, 'periodo' => '2026-13', 'monto' => 0, 'fecha' => now()->toDateString(), 'base' => 'cobrado',
        ])->assertStatus(422)->assertJsonPath('errors.monto.0', 'El monto del pago debe ser mayor a cero.')
            ->assertJsonStructure(['errors' => ['periodo']]);

        $pagoId = $this->conToken($jefe)->postJson('/api/v1/comisiones/pagos', [
            'user_id' => $asesor->id, 'periodo' => now()->format('Y-m'), 'monto' => 150, 'fecha' => now()->toDateString(), 'base' => 'cobrado',
        ])->assertCreated()
            ->assertJsonPath('data.asesor.id', $asesor->id)
            ->assertJsonPath('data.registrado_por', $jefe->id)
            ->assertJsonPath('data.monto', 150)
            ->json('data.id');

        $this->conToken($jefe)->getJson('/api/v1/comisiones')
            ->assertOk()->assertJsonPath('pagos.0.id', $pagoId)->assertJsonPath('data.0.pagado', 150);

        // Llave recortada a ver: no borra.
        $admin = $this->usuarioApi([], admin: true);
        $this->conToken($admin, ['comisiones.ver'], TokenApi::TIPO_INTEGRACION)
            ->deleteJson("/api/v1/comisiones/pagos/{$pagoId}")->assertForbidden();

        $this->conToken($jefe)->deleteJson("/api/v1/comisiones/pagos/{$pagoId}")
            ->assertOk()->assertJsonPath('message', 'Pago de comisión eliminado.');
        $this->assertSame(0, ComisionPago::count());
    }
}
