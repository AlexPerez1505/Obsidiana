<?php

namespace Tests\Feature\Api\V1\Rh;

use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class MiAsistenciaTest extends TestCase
{
    use AyudasRh, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ahoraLocal('2026-10-14 12:00:00');
    }

    public function test_cada_quien_ve_solo_lo_suyo_sin_permisos(): void
    {
        $yo = $this->empleado(['name' => 'Pedro Luna', 'sueldo_quincenal' => 9000]);
        $otro = $this->empleado();
        $mia = $this->checada($yo, '2026-10-14 08:59:00');
        $this->checada($yo, '2026-10-02 09:00:00');
        $ajena = $this->checada($otro, '2026-10-14 09:10:00');

        $api = $this->conToken($yo);

        $res = $api->getJson('/api/v1/rh/mi-asistencia')->assertOk()
            ->assertJsonPath('data.empleado.id', $yo->id)
            ->assertJsonPath('data.mes', '2026-10')
            ->assertJsonPath('data.hoy.fecha', '2026-10-14')
            ->assertJsonPath('data.hoy.esperado', true)
            ->assertJsonPath('data.hoy.horario.in', '09:00')
            ->assertJsonCount(1, 'data.hoy.checadas')
            ->assertJsonPath('data.hoy.checadas.0.id', $mia->id)
            ->assertJsonPath('data.empleado.horario.resumen', 'Lun–Vie 09:00–18:00')
            ->assertJsonCount(31, 'data.dias')
            ->assertJsonPath('data.dias.13.fecha', '2026-10-14');
        // Sin sueldos aunque sea lo propio: eso es de nómina
        $this->assertStringNotContainsString('sueldo', $res->getContent());

        $ids = $api->getJson('/api/v1/rh/mi-asistencia/checadas')->assertOk()->assertJsonCount(2, 'data')->json('data.*.id');
        $this->assertNotContains($ajena->id, $ids);
        $api->getJson('/api/v1/rh/mi-asistencia/checadas?desde=2026-10-10')->assertOk()->assertJsonCount(1, 'data');
        $api->getJson('/api/v1/rh/mi-asistencia?mes=2026-09')->assertOk()->assertJsonCount(30, 'data.dias');

        // Lo de los demás sigue pidiendo permiso
        $api->getJson('/api/v1/rh/asistencia/checadas')->assertForbidden();
        $api->getJson('/api/v1/rh/asistencia/empleados/'.$otro->id)->assertForbidden();
    }

    public function test_es_de_solo_lectura(): void
    {
        $yo = $this->empleado();

        $this->conToken($yo)->postJson('/api/v1/rh/mi-asistencia', [])->assertStatus(405);
        $this->conToken($yo)->postJson('/api/v1/rh/mi-asistencia/checadas', ['hora' => '09:00'])->assertStatus(405);
        $this->assertDatabaseCount('attendance_punches', 0);
    }

    public function test_una_llave_de_integracion_recortada_no_entra(): void
    {
        $yo = $this->empleado();

        $this->conToken($yo, ['clientes.ver'], TokenApi::TIPO_INTEGRACION)->getJson('/api/v1/rh/mi-asistencia')
            ->assertForbidden()
            ->assertJsonPath('message', 'Esta llave de integración no tiene acceso a la asistencia de su dueño.');

        $this->conToken($yo, ['*'], TokenApi::TIPO_INTEGRACION)->getJson('/api/v1/rh/mi-asistencia/checadas')->assertOk();
    }

    public function test_sin_horario_tambien_responde(): void
    {
        $yo = $this->usuarioApi();

        $this->conToken($yo)->getJson('/api/v1/rh/mi-asistencia')->assertOk()
            ->assertJsonPath('data.empleado.horario', null)
            ->assertJsonPath('data.hoy.esperado', false)
            ->assertJsonPath('data.hoy.horario', null);
    }
}
