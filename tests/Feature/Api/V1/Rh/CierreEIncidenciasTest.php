<?php

namespace Tests\Feature\Api\V1\Rh;

use App\Models\EmployeeAbsence;
use App\Models\EmployeeAttendance;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class CierreEIncidenciasTest extends TestCase
{
    use AyudasRh, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ahoraLocal('2026-10-14 12:00:00');   // miércoles
    }

    private function estado(int $userId, string $fecha): ?string
    {
        return EmployeeAttendance::where('user_id', $userId)->whereDate('attendance_date', $fecha)->value('status');
    }

    public function test_cerrar_dia_genera_falta_a_quien_no_checo(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $falto = $this->empleado();
        $vino = $this->empleado();
        // El checador "existe" desde su primera checada válida
        $this->checada($vino, '2026-10-13 09:00:00');

        $this->conToken($rh)->postJson('/api/v1/rh/asistencia/cerrar-dia', ['fecha' => '2026-10-13'])
            ->assertOk()
            ->assertJsonPath('data.fecha', '2026-10-13')
            ->assertJsonPath('data.faltas', 1)
            ->assertJsonPath('data.revisados', 2)
            ->assertJsonPath('message', 'Día cerrado: 1 falta(s), 0 justificada(s), 2 empleado(s) revisados.');

        $this->assertSame(EmployeeAttendance::FALTA, $this->estado($falto->id, '2026-10-13'));
        $this->assertSame(EmployeeAttendance::PUNTUAL, $this->estado($vino->id, '2026-10-13'));
        $this->assertDatabaseHas('employee_absences', ['user_id' => $falto->id, 'reason' => ServicioDeAsistencia::FALTA_AUTO]);
    }

    public function test_no_se_cierra_el_dia_en_curso_aunque_en_utc_ya_sea_manana(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        // 20:00 en México = 02:00 del día siguiente en UTC
        $this->ahoraLocal('2026-10-14 20:00:00');

        $this->conToken($rh)->postJson('/api/v1/rh/asistencia/cerrar-dia', ['fecha' => '2026-10-14'])
            ->assertStatus(422)
            ->assertJsonPath('errors.fecha.0', 'Ese día todavía no termina: solo se cierran días pasados.');

        $this->conToken($rh)->postJson('/api/v1/rh/asistencia/cerrar-dia', [])->assertStatus(422)->assertJsonStructure(['errors' => ['fecha']]);
    }

    public function test_una_incidencia_justifica_la_falta_y_al_borrarla_regresa(): void
    {
        $rh = $this->usuarioApi(['asistencia.ver', 'asistencia.gestionar']);
        $emp = $this->empleado(['name' => 'Luis Mora']);
        $otro = $this->empleado();
        $this->checada($otro, '2026-10-12 09:00:00');
        $api = $this->conToken($rh);

        $api->postJson('/api/v1/rh/asistencia/cerrar-dia', ['fecha' => '2026-10-13'])->assertOk()->assertJsonPath('data.faltas', 2);
        $this->assertSame(EmployeeAttendance::FALTA, $this->estado($emp->id, '2026-10-13'));

        $id = $api->postJson('/api/v1/rh/incidencias', [
            'user_id' => $emp->id, 'type' => 'comision', 'date_from' => '2026-10-13', 'date_to' => '2026-10-13',
            'destination' => 'Cliente en Puebla', 'time_start' => '08:00',
        ])->assertCreated()
            ->assertJsonPath('message', 'Incidencia registrada para Luis Mora.')
            ->assertJsonPath('data.type', 'comision')
            ->assertJsonPath('data.tipo_label', 'Comisión')
            ->assertJsonPath('data.time_start', '08:00')
            ->assertJsonPath('data.date_from', '2026-10-13')
            ->assertJsonPath('data.created_by', $rh->id)
            ->json('data.id');

        $this->assertSame(EmployeeAttendance::JUSTIFICADA, $this->estado($emp->id, '2026-10-13'));
        $this->assertDatabaseMissing('employee_absences', ['user_id' => $emp->id, 'deleted_at' => null]);

        // Cerrar otra vez el mismo día la cuenta como justificada
        $api->postJson('/api/v1/rh/asistencia/cerrar-dia', ['fecha' => '2026-10-13'])->assertOk()
            ->assertJsonPath('data.faltas', 1)->assertJsonPath('data.justificadas', 1);

        $api->getJson('/api/v1/rh/incidencias?mes=2026-10&empleado='.$emp->id)->assertOk()->assertJsonCount(1, 'data');
        $api->getJson('/api/v1/rh/incidencias?mes=2026-09')->assertOk()->assertJsonCount(0, 'data');
        $api->getJson('/api/v1/rh/incidencias/'.$id)->assertOk()->assertJsonPath('data.empleado.id', $emp->id);

        $api->deleteJson('/api/v1/rh/incidencias/'.$id)->assertOk()
            ->assertJsonPath('message', 'Incidencia eliminada y días recalculados.');
        $this->assertSame(EmployeeAttendance::FALTA, $this->estado($emp->id, '2026-10-13'));
        $this->assertTrue(EmployeeAbsence::where('user_id', $emp->id)->exists());
    }

    public function test_incidencia_valida_tipo_y_fechas(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $emp = $this->empleado();

        $this->conToken($rh)->postJson('/api/v1/rh/incidencias', [
            'user_id' => $emp->id, 'type' => 'fiesta', 'date_from' => '2026-10-13', 'date_to' => '2026-10-12',
        ])->assertStatus(422)
            ->assertJsonPath('errors.date_to.0', 'La fecha final no puede ser anterior a la inicial.')
            ->assertJsonStructure(['errors' => ['type']]);
    }

    public function test_ver_incidencias_pide_asistencia_ver_y_crearlas_asistencia_gestionar(): void
    {
        $soloVer = $this->usuarioApi(['asistencia.ver']);
        $emp = $this->empleado();
        $api = $this->conToken($soloVer);

        $api->getJson('/api/v1/rh/incidencias')->assertOk();
        $api->postJson('/api/v1/rh/incidencias', ['user_id' => $emp->id, 'type' => 'campo', 'date_from' => '2026-10-14', 'date_to' => '2026-10-14'])
            ->assertForbidden();
        $api->postJson('/api/v1/rh/asistencia/cerrar-dia', ['fecha' => '2026-10-13'])->assertForbidden();

        $this->conToken($this->usuarioApi())->getJson('/api/v1/rh/incidencias')->assertForbidden();
    }
}
