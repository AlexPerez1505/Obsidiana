<?php

namespace Tests\Feature\Api\V1\Rh;

use App\Models\EmployeeSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class HorariosTest extends TestCase
{
    use AyudasRh, RefreshDatabase, UsaApi;

    public function test_guardar_horario_con_un_dia_distinto(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $emp = $this->usuarioApi(atributos: ['name' => 'Rosa Díaz']);

        $dias = $this->semanaNormal();
        $dias[6] = ['on' => true, 'in' => '09:00', 'out' => '14:00'];

        $res = $this->conToken($rh)->putJson("/api/v1/rh/horarios/{$emp->id}", [
            'days' => $dias, 'tolerance_minutes' => 10, 'active' => true, 'checador_id' => 'CH-07',
        ]);

        $res->assertOk()
            ->assertJsonPath('message', 'Horario de Rosa Díaz guardado.')
            ->assertJsonPath('data.checador_id', 'CH-07')
            ->assertJsonPath('data.horario.weekdays', [1, 2, 3, 4, 5, 6])
            ->assertJsonPath('data.horario.time_in', '09:00')
            ->assertJsonPath('data.horario.tolerance_minutes', 10)
            ->assertJsonPath('data.horario.days.6.out', '14:00')
            ->assertJsonPath('data.horario.day_hours', ['6' => ['in' => '09:00', 'out' => '14:00']])
            ->assertJsonPath('data.horario.days.7.on', false)
            ->assertJsonPath('data.horario.resumen', 'Lun–Vie 09:00–18:00 · Sáb 09:00–14:00');

        $h = EmployeeSchedule::where('user_id', $emp->id)->first();
        // Solo el día que difiere del horario general se guarda aparte
        $this->assertSame([6 => ['in' => '09:00', 'out' => '14:00']], $h->day_hours);
        $this->assertSame('CH-07', $emp->fresh()->checador_id);
    }

    public function test_horario_invalido_da_422_por_dia(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $emp = $this->usuarioApi();
        $this->usuarioApi(atributos: ['checador_id' => 'CH-01']);   // ya tiene ese número
        $api = $this->conToken($rh);

        $dias = $this->semanaNormal();
        $dias[2] = ['on' => true, 'in' => '18:00', 'out' => '09:00'];

        $api->putJson("/api/v1/rh/horarios/{$emp->id}", ['days' => $dias, 'tolerance_minutes' => 5])
            ->assertStatus(422)
            ->assertJsonPath('errors', ['days.2' => ['En Mar la salida debe ser después de la entrada.']]);

        $api->putJson("/api/v1/rh/horarios/{$emp->id}", ['days' => [1 => ['in' => '09:00']], 'tolerance_minutes' => 5])
            ->assertStatus(422)->assertJsonPath('errors.days.0', 'Marca al menos un día laboral.');

        $api->putJson("/api/v1/rh/horarios/{$emp->id}", ['days' => $this->semanaNormal(), 'tolerance_minutes' => 5, 'checador_id' => 'CH-01'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['checador_id']]);

        $this->assertSame(0, EmployeeSchedule::count());
    }

    public function test_aplicar_a_varios_y_horario_base(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $a = $this->usuarioApi();
        $b = $this->usuarioApi();
        $c = $this->usuarioApi();
        $api = $this->conToken($rh);

        $api->postJson('/api/v1/rh/horarios/masivo', ['alcance' => 'seleccion', 'days' => $this->semanaNormal(), 'tolerance_minutes' => 5, 'active' => true])
            ->assertStatus(422)->assertJsonPath('errors.user_ids.0', 'Marca al menos una persona.');

        $api->postJson('/api/v1/rh/horarios/masivo', [
            'alcance' => 'seleccion', 'user_ids' => [$a->id, $b->id], 'days' => $this->semanaNormal(), 'tolerance_minutes' => 5, 'active' => true,
        ])->assertOk()->assertJsonPath('data.aplicados', 2)->assertJsonPath('message', 'Horario aplicado a 2 persona(s).');

        // RH y $c no tenían: el base se les aplica a ellos
        $api->postJson('/api/v1/rh/horarios/base')->assertOk()->assertJsonPath('data.aplicados', 2);
        $api->postJson('/api/v1/rh/horarios/base')->assertOk()->assertJsonPath('message', 'Todos ya tienen horario.');

        $this->assertTrue(EmployeeSchedule::where('user_id', $c->id)->where('time_in', '09:00:00')->exists());
    }

    public function test_listado_de_horarios_no_trae_sueldos(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $this->empleado(['sueldo_quincenal' => 7777.77]);
        $this->usuarioApi();

        $res = $this->conToken($rh)->getJson('/api/v1/rh/horarios');
        $res->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('dias.1', 'Lun')->assertJsonPath('horario_base.time_in', '09:00');
        $this->assertStringNotContainsString('sueldo', $res->getContent());

        $this->conToken($rh)->getJson('/api/v1/rh/horarios?con_horario=0')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_horarios_piden_asistencia_gestionar(): void
    {
        $emp = $this->usuarioApi();
        $api = $this->conToken($this->usuarioApi(['asistencia.ver']));

        $api->getJson('/api/v1/rh/horarios')->assertForbidden();
        $api->putJson("/api/v1/rh/horarios/{$emp->id}", ['days' => $this->semanaNormal(), 'tolerance_minutes' => 5])->assertForbidden();
    }
}
