<?php

namespace Tests\Feature\Api\V1\Administracion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Ojo: una migración siembra reportes de ejemplo (julio–agosto 2026); las
 * pruebas usan octubre y filtran por fecha para no contarlos.
 */
class ReportesTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function reporte(array $extra = []): array
    {
        return $extra + [
            'employee_name' => 'Ana López',
            'area' => 'Ventas',
            'type' => 'falta',
            'status' => 'pendiente',
            'start_date' => '2026-10-01',
            'detail' => 'No se presentó',
        ];
    }

    public function test_registrar_es_solo_de_admin(): void
    {
        $user = $this->usuarioApi();
        $admin = $this->usuarioApi(admin: true);

        $this->conToken($user)->postJson('/api/v1/reportes', $this->reporte())->assertForbidden();

        $this->conToken($admin)->postJson('/api/v1/reportes', $this->reporte())
            ->assertCreated()
            ->assertJsonPath('data.type', 'falta')
            ->assertJsonPath('data.label', 'Falta')
            ->assertJsonPath('data.employee_initials', 'AL')
            ->assertJsonPath('data.status_label', 'Pendiente')
            ->assertJsonPath('data.start_date', '2026-10-01')
            ->assertJsonPath('message', 'Reporte generado correctamente.');

        $this->conToken($admin)->postJson('/api/v1/reportes', $this->reporte(['type' => 'otro', 'end_date' => '2026-09-01']))
            ->assertStatus(422)->assertJsonStructure(['errors' => ['type', 'end_date']]);
    }

    public function test_listar_filtrar_y_resumen(): void
    {
        $admin = $this->usuarioApi(admin: true);
        $this->conToken($admin)->postJson('/api/v1/reportes', $this->reporte())->assertCreated();
        $this->conToken($admin)->postJson('/api/v1/reportes', $this->reporte([
            'employee_name' => 'Luis Pérez', 'area' => 'Almacén', 'type' => 'vacaciones', 'status' => 'aprobada',
        ]))->assertCreated();

        // Consultar no pide permiso aparte, igual que la pantalla.
        $user = $this->usuarioApi();
        $peticion = $this->conToken($user);
        $octubre = 'start_date=2026-10-01&end_date=2026-10-31';

        $peticion->getJson("/api/v1/reportes?{$octubre}")->assertOk()->assertJsonCount(2, 'data');
        $peticion->getJson("/api/v1/reportes?{$octubre}&person=Luis")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.employee_name', 'Luis Pérez');
        $peticion->getJson("/api/v1/reportes?{$octubre}&area=Ventas")
            ->assertOk()->assertJsonCount(1, 'data');

        $res = $peticion->getJson("/api/v1/reportes/resumen?{$octubre}")->assertOk()
            ->assertJsonPath('data.total', 2);

        $this->assertContains('Almacén', $res->json('data.areas'));
        $this->assertSame(1, collect($res->json('data.indicadores'))->firstWhere('type', 'absence')['value']);
        $this->assertSame(1, collect($res->json('data.pendientes'))->firstWhere('type', 'absence')['count']);
        $this->assertCount(2, $res->json('data.colaboradores'));
    }
}
