<?php

namespace Tests\Feature\Api\V1\Rh;

use App\Models\AttendancePunch;
use App\Models\EmployeeAttendance;
use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class ChecadasTest extends TestCase
{
    use AyudasRh, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();
        // Miércoles 14 de octubre de 2026, mediodía en México (18:00 UTC)
        $this->ahoraLocal('2026-10-14 12:00:00');
    }

    public function test_checada_manual_se_registra_y_calcula_el_retardo(): void
    {
        $rh = $this->usuarioApi(['asistencia.ver', 'asistencia.gestionar']);
        $emp = $this->empleado(['name' => 'Ana Torres']);

        $res = $this->conToken($rh)->postJson('/api/v1/rh/asistencia/checadas', [
            'user_id' => $emp->id, 'fecha' => '2026-10-14', 'hora' => '09:20', 'motivo' => 'Falló el lector',
        ]);

        $res->assertCreated()
            ->assertJsonPath('message', 'Checada manual registrada para Ana Torres.')
            ->assertJsonPath('data.method', 'manual')
            ->assertJsonPath('data.kind', 'entrada')
            ->assertJsonPath('data.hora_local', '09:20:00')
            ->assertJsonPath('data.work_date', '2026-10-14')
            ->assertJsonPath('data.created_by', $rh->id)
            ->assertJsonPath('data.empleado.id', $emp->id);

        $dia = EmployeeAttendance::where('user_id', $emp->id)->whereDate('attendance_date', '2026-10-14')->first();
        $this->assertSame(EmployeeAttendance::RETARDO, $dia->status);
        $this->assertSame(15, (int) $dia->late_minutes);   // límite 09:05
    }

    public function test_checada_manual_fuera_de_orden_renumera_el_dia(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $emp = $this->empleado();
        $api = $this->conToken($rh);

        $api->postJson('/api/v1/rh/asistencia/checadas', ['user_id' => $emp->id, 'fecha' => '2026-10-13', 'hora' => '18:05', 'motivo' => 'x'])->assertCreated();
        $api->postJson('/api/v1/rh/asistencia/checadas', ['user_id' => $emp->id, 'fecha' => '2026-10-13', 'hora' => '08:55', 'motivo' => 'y'])
            ->assertCreated()->assertJsonPath('data.kind', 'entrada')->assertJsonPath('data.seq', 1);

        $this->assertSame(['entrada', 'salida'], AttendancePunch::where('user_id', $emp->id)->orderBy('punched_at')->pluck('kind')->all());
        $dia = EmployeeAttendance::where('user_id', $emp->id)->whereDate('attendance_date', '2026-10-13')->first();
        $this->assertSame(EmployeeAttendance::PUNTUAL, $dia->status);
        $this->assertSame('08:55:00', $dia->check_in);
        $this->assertSame('18:05:00', $dia->check_out);
    }

    public function test_checada_en_el_futuro_o_sin_motivo_da_422(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $emp = $this->empleado();
        $api = $this->conToken($rh);

        $api->postJson('/api/v1/rh/asistencia/checadas', ['user_id' => $emp->id, 'fecha' => '2026-10-14', 'hora' => '13:00', 'motivo' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('errors.hora.0', 'No se puede registrar una checada en el futuro.');

        $api->postJson('/api/v1/rh/asistencia/checadas', ['user_id' => $emp->id, 'fecha' => '2026-10-14', 'hora' => '09:00'])
            ->assertStatus(422)
            ->assertJsonPath('errors.motivo.0', 'Escribe el motivo de la checada manual: queda como evidencia.');

        $this->assertDatabaseCount('attendance_punches', 0);
    }

    public function test_anular_una_checada_recalcula_el_dia_y_no_se_anula_dos_veces(): void
    {
        $rh = $this->usuarioApi(['asistencia.gestionar']);
        $emp = $this->empleado();
        $api = $this->conToken($rh);

        $primera = $api->postJson('/api/v1/rh/asistencia/checadas', ['user_id' => $emp->id, 'fecha' => '2026-10-14', 'hora' => '08:50', 'motivo' => 'a'])->json('data.id');
        $api->postJson('/api/v1/rh/asistencia/checadas', ['user_id' => $emp->id, 'fecha' => '2026-10-14', 'hora' => '09:30', 'motivo' => 'b'])->assertCreated();

        $api->postJson("/api/v1/rh/asistencia/checadas/{$primera}/anular", [])
            ->assertStatus(422)->assertJsonPath('errors.motivo.0', 'Escribe por qué se anula la checada.');

        $api->postJson("/api/v1/rh/asistencia/checadas/{$primera}/anular", ['motivo' => 'Se registró dos veces'])
            ->assertOk()
            ->assertJsonPath('message', 'Checada anulada. Queda en el historial con su motivo.')
            ->assertJsonPath('data.anulada', true)
            ->assertJsonPath('data.void_reason', 'Se registró dos veces')
            ->assertJsonPath('data.anulada_por.id', $rh->id);

        // La que queda pasa a ser la entrada: ahora es retardo
        $dia = EmployeeAttendance::where('user_id', $emp->id)->whereDate('attendance_date', '2026-10-14')->first();
        $this->assertSame(EmployeeAttendance::RETARDO, $dia->status);
        $this->assertSame('09:30:00', $dia->check_in);

        $api->postJson("/api/v1/rh/asistencia/checadas/{$primera}/anular", ['motivo' => 'otra vez'])
            ->assertStatus(409)->assertJsonPath('codigo', 'conflicto');
    }

    public function test_listado_filtra_por_dia_empleado_y_anuladas(): void
    {
        $rh = $this->usuarioApi(['asistencia.ver']);
        $a = $this->empleado();
        $b = $this->empleado();
        $this->checada($a, '2026-10-14 09:00:00');
        $this->checada($b, '2026-10-14 09:01:00');
        $this->checada($a, '2026-10-13 09:00:00', ['voided_at' => now(), 'void_reason' => 'x']);

        $api = $this->conToken($rh);

        // Sin fecha: hoy (local)
        $api->getJson('/api/v1/rh/asistencia/checadas')->assertOk()->assertJsonCount(2, 'data');
        $api->getJson('/api/v1/rh/asistencia/checadas?empleado='.$a->id)->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', $a->id);
        $api->getJson('/api/v1/rh/asistencia/checadas?fecha=2026-10-13')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.anulada', true);
        $api->getJson('/api/v1/rh/asistencia/checadas?desde=2026-10-13&hasta=2026-10-14&anuladas=excluir')->assertOk()->assertJsonCount(2, 'data');
        $api->getJson('/api/v1/rh/asistencia/checadas?desde=2026-10-13&anuladas=solo')->assertOk()->assertJsonCount(1, 'data');
        $api->getJson('/api/v1/rh/asistencia/checadas?fecha=no-es-fecha')->assertStatus(422);
    }

    public function test_sin_permiso_de_gestion_no_registra_ni_anula(): void
    {
        $soloVer = $this->usuarioApi(['asistencia.ver']);
        $emp = $this->empleado();
        $p = $this->checada($emp, '2026-10-14 09:00:00');

        $api = $this->conToken($soloVer);
        $api->getJson('/api/v1/rh/asistencia/checadas/'.$p->id)->assertOk()->assertJsonPath('data.id', $p->id);
        $api->postJson('/api/v1/rh/asistencia/checadas', ['user_id' => $emp->id, 'fecha' => '2026-10-14', 'hora' => '09:00', 'motivo' => 'x'])
            ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
        $api->postJson("/api/v1/rh/asistencia/checadas/{$p->id}/anular", ['motivo' => 'x'])->assertForbidden();

        $nadie = $this->usuarioApi();
        $this->conToken($nadie)->getJson('/api/v1/rh/asistencia/checadas')->assertForbidden();
    }

    public function test_una_llave_recortada_solo_puede_lo_que_se_le_marco(): void
    {
        $admin = $this->usuarioApi(admin: true);
        $emp = $this->empleado();

        $llave = $this->conToken($admin, ['asistencia.ver'], TokenApi::TIPO_INTEGRACION);
        $llave->getJson('/api/v1/rh/asistencia/checadas')->assertOk();
        $llave->postJson('/api/v1/rh/asistencia/checadas', ['user_id' => $emp->id, 'fecha' => '2026-10-14', 'hora' => '09:00', 'motivo' => 'x'])
            ->assertForbidden();
        $llave->getJson('/api/v1/rh/nomina/quincena')->assertForbidden();
    }
}
