<?php

namespace Tests\Feature\Api\V1\Cuenta;

use App\Models\TokenApi;
use App\Support\DashboardWidgets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class TableroTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    public function test_tablero_de_fabrica_con_sus_datos(): void
    {
        $user = $this->usuarioApi(admin: true);

        $res = $this->conToken($user)->getJson('/api/v1/tablero')
            ->assertOk()
            ->assertJsonCount(count(DashboardWidgets::porOmision()), 'data')
            ->assertJsonPath('data.0.id', 'clientes')
            ->assertJsonPath('data.0.disponible', true)
            ->assertJsonPath('meta.personalizado', false);

        $this->assertArrayHasKey('total', $res->json('data.0.datos'));
        $ultimas = collect($res->json('data'))->firstWhere('id', 'ultimas_cotizaciones');
        $this->assertSame([], $ultimas['datos']['filas']);
    }

    public function test_una_llave_recortada_no_lee_tarjetas_de_otros_modulos(): void
    {
        $user = $this->usuarioApi(['clientes.ver', 'ventas.ver']);

        $tarjetas = collect($this->conToken($user, ['clientes.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/tablero')->assertOk()->json('data'))->keyBy('id');

        $this->assertTrue($tarjetas['clientes']['disponible']);
        $this->assertFalse($tarjetas['ventas_mes']['disponible']);
        $this->assertNull($tarjetas['ventas_mes']['datos']);
        // Sin módulo detrás: siempre disponible.
        $this->assertTrue($tarjetas['accesos_rapidos']['disponible']);
    }

    public function test_guardar_y_restablecer_mi_acomodo(): void
    {
        $user = $this->usuarioApi(admin: true);

        $this->conToken($user)->putJson('/api/v1/tablero/tarjetas', ['widgets' => [
            ['id' => 'ventas_grafica', 'w' => 9, 'h' => 1],
            ['id' => 'no_existe', 'w' => 1, 'h' => 2],
            ['id' => 'ventas_grafica', 'w' => 2, 'h' => 4],
            ['id' => 'mis_tareas', 'w' => 2, 'h' => 4],
        ]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 'ventas_grafica')
            // Recortado a sus límites (máx. 4 de ancho, mín. 3 de alto).
            ->assertJsonPath('data.0.w', 4)
            ->assertJsonPath('data.0.h', 3)
            ->assertJsonPath('message', 'Tablero actualizado.');

        $this->assertCount(2, $user->fresh()->dashboard_widgets);

        $this->conToken($user)->getJson('/api/v1/tablero')->assertJsonPath('meta.personalizado', true);

        $this->conToken($user)->deleteJson('/api/v1/tablero/tarjetas')
            ->assertOk()->assertJsonCount(count(DashboardWidgets::porOmision()), 'data');
        $this->assertNull($user->fresh()->dashboard_widgets);

        $this->conToken($user)->putJson('/api/v1/tablero/tarjetas', ['widgets' => [['id' => 'clientes']]])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['widgets.0.w', 'widgets.0.h']]);
    }

    public function test_catalogo_de_tarjetas(): void
    {
        $user = $this->usuarioApi();

        $res = $this->conToken($user)->getJson('/api/v1/tablero/catalogo')->assertOk();

        $this->assertCount(count(DashboardWidgets::catalogo()), $res->json('data'));
        $clientes = collect($res->json('data'))->firstWhere('id', 'clientes');
        $this->assertFalse($clientes['disponible']);
        $this->assertSame(1, $clientes['w_min']);
    }
}
