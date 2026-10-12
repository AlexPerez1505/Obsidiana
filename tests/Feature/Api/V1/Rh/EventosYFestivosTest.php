<?php

namespace Tests\Feature\Api\V1\Rh;

use App\Models\EmployeeAttendance;
use App\Models\Holiday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class EventosYFestivosTest extends TestCase
{
    use AyudasRh, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ahoraLocal('2026-10-14 12:00:00');
    }

    private function estado(int $userId, string $fecha): ?string
    {
        return EmployeeAttendance::where('user_id', $userId)->whereDate('attendance_date', $fecha)->value('status');
    }

    /** Un empleado que faltó el martes 13 (día ya cerrado) y la API de RH. */
    private function conFalta(): array
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $emp = $this->empleado();
        $this->checada($this->empleado(), '2026-10-12 09:00:00');
        $api = $this->conToken($rh);
        $api->postJson('/api/v1/rh/asistencia/cerrar-dia', ['fecha' => '2026-10-13'])->assertOk();
        $this->assertSame(EmployeeAttendance::FALTA, $this->estado($emp->id, '2026-10-13'));

        return [$api, $emp, $rh];
    }

    public function test_un_descanso_del_equipo_justifica_los_dias_pasados_y_al_borrarlo_regresa_la_falta(): void
    {
        [$api, $emp, $rh] = $this->conFalta();

        $id = $api->postJson('/api/v1/rh/eventos', [
            'name' => 'Puente de octubre', 'type' => 'descanso', 'date_from' => '2026-10-13', 'date_to' => '2026-10-16', 'scope' => 'todos',
        ])->assertCreated()
            ->assertJsonPath('message', 'Evento «Puente de octubre» registrado.')
            ->assertJsonPath('data.scope', 'todos')
            ->assertJsonPath('data.user_ids', null)
            ->assertJsonPath('data.creador.id', $rh->id)
            ->json('data.id');

        $this->assertSame(EmployeeAttendance::JUSTIFICADA, $this->estado($emp->id, '2026-10-13'));

        $api->getJson('/api/v1/rh/eventos?periodo=proximos')->assertOk()->assertJsonCount(1, 'data');
        $api->getJson('/api/v1/rh/eventos?periodo=pasados')->assertOk()->assertJsonCount(0, 'data');
        $api->getJson('/api/v1/rh/eventos/'.$id)->assertOk()->assertJsonPath('data.tipo_label', 'Descanso / vacaciones del equipo');

        $api->deleteJson('/api/v1/rh/eventos/'.$id)->assertOk()->assertJsonPath('message', 'Evento eliminado y días recalculados.');
        $this->assertSame(EmployeeAttendance::FALTA, $this->estado($emp->id, '2026-10-13'));
    }

    public function test_horario_especial_pide_horas_y_seleccion_pide_personas(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $emp = $this->empleado();
        $api = $this->conToken($rh);

        $api->postJson('/api/v1/rh/eventos', [
            'name' => 'Inventario', 'type' => 'horario_especial', 'date_from' => '2026-10-17', 'date_to' => '2026-10-17', 'scope' => 'seleccion',
        ])->assertStatus(422)
            ->assertJsonPath('errors.time_in.0', 'Indica la hora de entrada del horario especial.')
            ->assertJsonPath('errors.user_ids.0', 'Marca a quién aplica el evento.');

        $api->postJson('/api/v1/rh/eventos', [
            'name' => 'Inventario', 'type' => 'horario_especial', 'date_from' => '2026-10-17', 'date_to' => '2026-10-17',
            'scope' => 'seleccion', 'user_ids' => [$emp->id], 'time_in' => '08:00', 'time_out' => '13:00', 'tolerance_minutes' => 0,
        ])->assertCreated()
            ->assertJsonPath('data.time_in', '08:00')
            ->assertJsonPath('data.time_out', '13:00')
            ->assertJsonPath('data.user_ids', [$emp->id]);
    }

    public function test_un_festivo_quita_la_falta_automatica_y_al_borrarlo_regresa(): void
    {
        [$api, $emp] = $this->conFalta();

        $id = $api->postJson('/api/v1/rh/festivos', ['date' => '2026-10-13', 'name' => 'Día de la empresa'])
            ->assertCreated()
            ->assertJsonPath('message', 'Festivo agregado.')
            ->assertJsonPath('data.date', '2026-10-13')
            ->json('data.id');

        $this->assertNull($this->estado($emp->id, '2026-10-13'));

        $api->postJson('/api/v1/rh/festivos', ['date' => '2026-10-13', 'name' => 'Otra vez'])
            ->assertStatus(422)->assertJsonPath('errors.date.0', 'Ese día ya está como festivo.');

        $api->deleteJson('/api/v1/rh/festivos/'.$id)->assertOk()->assertJsonPath('message', 'Festivo eliminado.');
        $this->assertSame(EmployeeAttendance::FALTA, $this->estado($emp->id, '2026-10-13'));
    }

    public function test_cargar_los_festivos_oficiales_solo_agrega_los_que_faltan(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $api = $this->conToken($rh);

        $api->postJson('/api/v1/rh/festivos/oficiales', ['anio' => 2026])
            ->assertOk()
            ->assertJsonPath('nuevos', 7)
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('data.1.date', '2026-02-02')   // 1.er lunes de febrero
            ->assertJsonPath('message', 'Se agregaron 7 festivo(s) oficiales de 2026. Ya aparecen en la Agenda.');

        $api->postJson('/api/v1/rh/festivos/oficiales', ['anio' => 2026])
            ->assertOk()->assertJsonPath('nuevos', 0)
            ->assertJsonPath('message', 'Los festivos oficiales de 2026 ya estaban cargados.');

        // 2030 cambia el Ejecutivo: también 1 de octubre
        $api->postJson('/api/v1/rh/festivos/oficiales', ['anio' => 2030])->assertOk()->assertJsonPath('nuevos', 8);

        $api->getJson('/api/v1/rh/festivos?anio=2026')->assertOk()->assertJsonCount(7, 'data');
        $api->getJson('/api/v1/rh/festivos')->assertOk()->assertJsonCount(15, 'data');
        $api->postJson('/api/v1/rh/festivos/oficiales', ['anio' => 1990])->assertStatus(422);

        $this->assertSame(15, Holiday::count());
    }

    public function test_eventos_y_festivos_piden_asistencia_gestionar(): void
    {
        $api = $this->conToken($this->usuarioApi(['asistencia.ver']));

        $api->getJson('/api/v1/rh/eventos')->assertForbidden();
        $api->getJson('/api/v1/rh/festivos')->assertForbidden();
        $api->postJson('/api/v1/rh/festivos', ['date' => '2026-12-24', 'name' => 'Nochebuena'])->assertForbidden();
    }
}
