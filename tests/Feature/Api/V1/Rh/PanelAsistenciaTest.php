<?php

namespace Tests\Feature\Api\V1\Rh;

use App\Models\AttendanceModality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class PanelAsistenciaTest extends TestCase
{
    use AyudasRh, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ahoraLocal('2026-10-14 12:00:00');
    }

    public function test_el_dia_cuenta_presentes_retardos_incidencias_y_pendientes(): void
    {
        $rh = $this->usuarioApi(['asistencia.ver', 'asistencia.gestionar']);
        $puntual = $this->empleado(['name' => 'A Puntual']);
        $tarde = $this->empleado(['name' => 'B Tarde']);
        $casa = $this->empleado(['name' => 'C Casa']);
        $this->empleado(['name' => 'D Nada']);
        $api = $this->conToken($rh);

        $api->postJson('/api/v1/rh/asistencia/checadas', ['user_id' => $puntual->id, 'fecha' => '2026-10-14', 'hora' => '09:00', 'motivo' => 'x'])->assertCreated();
        $api->postJson('/api/v1/rh/asistencia/checadas', ['user_id' => $tarde->id, 'fecha' => '2026-10-14', 'hora' => '09:30', 'motivo' => 'x'])->assertCreated();
        AttendanceModality::create(['user_id' => $casa->id, 'type' => 'home_office', 'date_from' => '2026-10-14', 'date_to' => '2026-10-14']);

        $res = $api->getJson('/api/v1/rh/asistencia/dia')->assertOk()
            ->assertJsonPath('data.fecha', '2026-10-14')
            ->assertJsonPath('data.es_hoy', true)
            ->assertJsonPath('data.resumen.esperados', 4)
            ->assertJsonPath('data.resumen.presentes', 1)
            ->assertJsonPath('data.resumen.retardos', 1)
            ->assertJsonPath('data.resumen.home_office', 1)
            ->assertJsonPath('data.resumen.sin_checar', 1)
            ->assertJsonPath('data.sin_horario', 1)   // el de RH
            ->assertJsonCount(2, 'data.ultimas_checadas');

        $estados = collect($res->json('data.empleados'))->mapWithKeys(fn ($e) => [$e['empleado']['name'] => $e['estado']])->all();
        $this->assertSame(['A Puntual' => 'puntual', 'B Tarde' => 'retardo', 'C Casa' => 'home_office', 'D Nada' => 'sin_checar'], $estados);

        // Un domingo nadie es esperado
        $api->getJson('/api/v1/rh/asistencia/dia?fecha=2026-10-11')->assertOk()
            ->assertJsonPath('data.es_hoy', false)
            ->assertJsonPath('data.resumen.esperados', 0)
            ->assertJsonPath('data.resumen.descanso', 4);

        $api->getJson('/api/v1/rh/asistencia/dia?fecha=ayer-no')->assertStatus(422)->assertJsonStructure(['errors' => ['fecha']]);
    }

    public function test_indicadores_catalogos_y_empleados(): void
    {
        $rh = $this->usuarioApi(['asistencia.ver']);
        $emp = $this->empleado(['name' => 'Elena Paz', 'checador_id' => 'E-1']);
        $this->checada($emp, '2026-10-13 09:00:00');
        $api = $this->conToken($rh);

        $api->getJson('/api/v1/rh/asistencia/indicadores?mes=2026-10')->assertOk()
            ->assertJsonPath('data.mes', '2026-10')
            ->assertJsonStructure(['data' => ['kpis' => ['puntualidad', 'promedio_llegada', 'retardos', 'faltas'], 'serie', 'hora_promedio', 'por_puesto']])
            ->assertJsonCount(6, 'data.serie');
        $api->getJson('/api/v1/rh/asistencia/indicadores?mes=octubre')->assertStatus(422);

        $api->getJson('/api/v1/rh/asistencia/catalogos')->assertOk()
            ->assertJsonPath('data.zona_horaria', 'America/Mexico_City')
            ->assertJsonPath('data.dias.6', 'Sáb')
            ->assertJsonPath('data.tipos_incidencia.home_office', 'Home office')
            ->assertJsonPath('data.dedos.1', 'Índice derecho');

        $api->getJson('/api/v1/rh/asistencia/empleados?buscar=E-1')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Elena Paz');

        $api->getJson('/api/v1/rh/asistencia/empleados/'.$emp->id.'?mes=2026-10')->assertOk()
            ->assertJsonPath('data.empleado.checador_id', 'E-1')
            ->assertJsonPath('data.resumen.puntuales', 0)   // la checada directa no generó resumen
            ->assertJsonCount(31, 'data.dias')
            ->assertJsonPath('data.dias.12.fecha', '2026-10-13')
            ->assertJsonPath('data.dias.12.esperado', true)
            ->assertJsonCount(1, 'data.dias.12.checadas')
            ->assertJsonPath('data.dias.10.esperado', false);   // domingo 11

        $api->getJson('/api/v1/rh/asistencia/empleados/999999')->assertNotFound()->assertJsonPath('codigo', 'no_encontrado');
    }

    public function test_el_panel_pide_asistencia_ver(): void
    {
        $api = $this->conToken($this->usuarioApi(['asistencia.nomina']));

        $api->getJson('/api/v1/rh/asistencia/dia')->assertForbidden();
        $api->getJson('/api/v1/rh/asistencia/indicadores')->assertForbidden();
        $api->getJson('/api/v1/rh/asistencia/empleados')->assertForbidden();
    }
}
