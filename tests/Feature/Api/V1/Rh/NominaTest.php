<?php

namespace Tests\Feature\Api\V1\Rh;

use App\Models\EmployeeAttendance;
use App\Models\PayrollRule;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class NominaTest extends TestCase
{
    use AyudasRh, RefreshDatabase, UsaApi;

    private User $emp;

    protected function setUp(): void
    {
        parent::setUp();
        // La primera quincena de octubre ya terminó; la segunda está en curso
        $this->ahoraLocal('2026-10-20 12:00:00');

        // Sueldo 1,500: un día de sueldo = 1,500 ÷ 15 = 100
        $this->emp = $this->empleado(['name' => 'Marta Peña', 'sueldo_quincenal' => 1500]);

        // 1–15 oct: 11 días laborales; faltó el martes 6
        for ($d = Carbon::parse('2026-10-01'); $d->lte(Carbon::parse('2026-10-15')); $d->addDay()) {
            if ($d->isWeekday()) {
                EmployeeAttendance::create([
                    'user_id' => $this->emp->id, 'attendance_date' => $d->toDateString(),
                    'status' => $d->day === 6 ? EmployeeAttendance::FALTA : EmployeeAttendance::PUNTUAL,
                    'check_in' => $d->day === 6 ? null : '08:58:00', 'punches_count' => $d->day === 6 ? 0 : 2, 'worked_minutes' => 540,
                ]);
            }
        }
    }

    private function nomina(): static
    {
        return $this->conToken($this->usuarioApi(['asistencia.nomina']));
    }

    public function test_la_quincena_se_calcula_con_la_asistencia_y_las_reglas(): void
    {
        $res = $this->nomina()->getJson('/api/v1/rh/nomina/quincena?desde=2026-10-05');

        $res->assertOk()
            ->assertJsonPath('data.desde', '2026-10-01')
            ->assertJsonPath('data.hasta', '2026-10-15')
            ->assertJsonPath('data.estado', 'abierta')
            ->assertJsonPath('data.concluida', true)
            ->assertJsonPath('data.en_curso', false)
            ->assertJsonPath('data.siguiente.desde', '2026-10-16')
            ->assertJsonPath('data.anterior.desde', '2026-09-16')
            ->assertJsonPath('data.filas.0.nombre', 'Marta Peña')
            ->assertJsonPath('data.filas.0.sueldo', 1500)
            ->assertJsonPath('data.filas.0.descuentos', 100)
            ->assertJsonPath('data.filas.0.neto', 1400)
            ->assertJsonPath('data.filas.0.metricas.laborales', 11)
            ->assertJsonPath('data.filas.0.metricas.faltas', 1)
            ->assertJsonPath('data.filas.0.lineas.0.nombre', 'Descuento por falta')
            // El bono de puntualidad mira el mes: queda "en juego" hasta la segunda quincena
            ->assertJsonPath('data.filas.0.en_juego.0.nombre', 'Bono de puntualidad')
            ->assertJsonPath('data.totales.neto', 1400)
            ->assertJsonPath('data.totales.empleados', 1);

        // Sin fecha es la quincena de hoy (16–31), en curso
        $this->nomina()->getJson('/api/v1/rh/nomina/quincena')->assertOk()
            ->assertJsonPath('data.desde', '2026-10-16')->assertJsonPath('data.en_curso', true);
    }

    public function test_conceptos_fijos_y_de_una_sola_vez_cambian_el_neto(): void
    {
        $api = $this->nomina();

        $api->postJson("/api/v1/rh/nomina/empleados/{$this->emp->id}/conceptos", ['kind' => 'bono', 'name' => 'Transporte', 'amount' => 200])
            ->assertCreated()
            ->assertJsonPath('message', 'Bono fijo agregado a Marta Peña.')
            ->assertJsonPath('data.recurrence', 'cada_periodo')
            ->assertJsonPath('data.amount', 200);

        $unico = $api->postJson("/api/v1/rh/nomina/empleados/{$this->emp->id}/conceptos-unicos", ['desde' => '2026-10-01', 'kind' => 'descuento', 'name' => 'Anticipo', 'amount' => 50])
            ->assertCreated()
            ->assertJsonPath('message', 'Descuento agregado a Marta Peña solo para esta quincena.')
            ->assertJsonPath('data.recurrence', 'unica')
            ->assertJsonPath('data.period_start', '2026-10-01')
            ->json('data.id');

        $api->getJson('/api/v1/rh/nomina/quincena?desde=2026-10-01')->assertJsonPath('data.filas.0.neto', 1550);
        // El único no toca la quincena siguiente
        $api->getJson('/api/v1/rh/nomina/quincena?desde=2026-10-16')->assertJsonPath('data.filas.0.descuentos', 0);

        $api->postJson("/api/v1/rh/nomina/empleados/{$this->emp->id}/conceptos", ['kind' => 'bono', 'amount' => 0])
            ->assertStatus(422)->assertJsonPath('errors.name.0', 'Ponle un nombre al concepto.');

        $api->deleteJson('/api/v1/rh/nomina/conceptos/'.$unico)->assertOk()->assertJsonPath('message', 'Concepto eliminado.');
        $api->getJson('/api/v1/rh/nomina/quincena?desde=2026-10-01')->assertJsonPath('data.filas.0.neto', 1600);
    }

    public function test_cerrar_pagar_y_reabrir_la_quincena(): void
    {
        $api = $this->nomina();

        $api->postJson('/api/v1/rh/nomina/quincena/pagada', ['desde' => '2026-10-01'])
            ->assertStatus(409)->assertJsonPath('message', 'Primero cierra la quincena.');

        $api->postJson('/api/v1/rh/nomina/quincena/cerrar', ['desde' => '2026-10-01'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cerrada')
            ->assertJsonPath('data.total_neto', 1400)
            ->assertJsonPath('data.employees', 1)
            ->assertJsonPath('message', 'Quincena cerrada: 1 empleados, neto $1,400.00. Los montos quedaron congelados.');

        $api->postJson('/api/v1/rh/nomina/quincena/cerrar', ['desde' => '2026-10-01'])
            ->assertStatus(409)->assertJsonPath('message', 'Esta quincena ya está cerrada.');

        // Congelada: un sueldo nuevo no la cambia, y no acepta conceptos
        $this->emp->forceFill(['sueldo_quincenal' => 3000])->save();
        $api->getJson('/api/v1/rh/nomina/quincena?desde=2026-10-01')->assertOk()
            ->assertJsonPath('data.estado', 'cerrada')
            ->assertJsonPath('data.filas.0.congelada', true)
            ->assertJsonPath('data.filas.0.neto', 1400);
        $api->postJson("/api/v1/rh/nomina/empleados/{$this->emp->id}/conceptos-unicos", ['desde' => '2026-10-01', 'kind' => 'bono', 'name' => 'X', 'amount' => 10])
            ->assertStatus(409)->assertJsonPath('message', 'La quincena está cerrada: reábrela para agregar conceptos.');

        $api->postJson('/api/v1/rh/nomina/quincena/pagada', ['desde' => '2026-10-01'])
            ->assertOk()->assertJsonPath('data.status', 'pagada')->assertJsonPath('message', 'Quincena marcada como pagada.');
        $this->assertNotNull(PayrollRun::first()->paid_at);
        $api->postJson('/api/v1/rh/nomina/quincena/pagada', ['desde' => '2026-10-01'])
            ->assertOk()->assertJsonPath('data.status', 'cerrada');

        $api->getJson('/api/v1/rh/nomina/historial')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.period_start', '2026-10-01');

        $api->postJson('/api/v1/rh/nomina/quincena/reabrir', ['desde' => '2026-10-01'])
            ->assertOk()->assertJsonPath('data.estado', 'abierta');
        $api->postJson('/api/v1/rh/nomina/quincena/reabrir', ['desde' => '2026-10-01'])->assertStatus(409);
        $api->getJson('/api/v1/rh/nomina/quincena?desde=2026-10-01')->assertJsonPath('data.filas.0.neto', 2800);
    }

    public function test_no_se_cierra_una_quincena_que_no_ha_terminado(): void
    {
        // Último día de la quincena, de noche en México (ya es el 16 en UTC)
        $this->ahoraLocal('2026-10-15 20:00:00');

        $this->nomina()->postJson('/api/v1/rh/nomina/quincena/cerrar', ['desde' => '2026-10-15'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'La quincena todavía no termina: espera al último día para cerrarla.');

        $this->assertDatabaseCount('payroll_runs', 0);
    }

    public function test_exportar_da_el_mismo_csv_que_la_web(): void
    {
        $res = $this->nomina()->get('/api/v1/rh/nomina/quincena/exportar?desde=2026-10-01', ['Accept' => 'text/csv']);

        $res->assertOk();
        $this->assertStringStartsWith('text/csv', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('nomina_20261001_20261015.csv', $res->headers->get('Content-Disposition'));

        $csv = $res->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Neto a pagar', $csv);
        $this->assertStringContainsString('"Marta Peña",11,10,1,0,1500.00,0.00,100.00,1400.00', $csv);
    }

    public function test_reglas_crear_editar_alternar_y_borrar(): void
    {
        $api = $this->nomina();

        $base = [
            'name' => 'Bono asistencia', 'kind' => 'bono', 'repeat_mode' => 'una_vez', 'calc' => 'monto', 'amount' => 250,
            'timing' => 'periodo', 'scope' => 'todos', 'active' => true,
            'conditions' => [['metric' => 'faltas', 'scope' => 'mes', 'op' => '=', 'value' => 0]],
        ];

        $id = $api->postJson('/api/v1/rh/nomina/reglas', $base)
            ->assertCreated()
            ->assertJsonPath('data.timing', 'cierre_mes')   // mira el mes: se mueve al cierre
            ->assertJsonPath('data.amount', 250)
            ->assertJsonPath('message', 'Regla «Bono asistencia» creada. Como mira el mes completo, se aplica al cerrar el mes.')
            ->json('data.id');

        $api->putJson('/api/v1/rh/nomina/reglas/'.$id, ['name' => 'Bono sin faltas', 'conditions' => [['metric' => 'faltas', 'scope' => 'periodo', 'op' => '=', 'value' => 0]]] + $base)
            ->assertOk()
            ->assertJsonPath('data.name', 'Bono sin faltas')
            ->assertJsonPath('data.timing', 'periodo')
            ->assertJsonPath('message', 'Regla «Bono sin faltas» actualizada.');

        $api->postJson("/api/v1/rh/nomina/reglas/{$id}/alternar")->assertOk()
            ->assertJsonPath('data.active', false)->assertJsonPath('message', '«Bono sin faltas» desactivada.');

        $api->postJson('/api/v1/rh/nomina/reglas', ['calc' => 'pct_sueldo', 'amount' => 150] + $base)
            ->assertStatus(422)->assertJsonPath('errors.amount.0', 'Un porcentaje no puede pasar de 100.');
        $api->postJson('/api/v1/rh/nomina/reglas', ['conditions' => []] + $base)
            ->assertStatus(422)->assertJsonPath('errors.conditions.0', 'Agrega al menos una condición.');

        $api->getJson('/api/v1/rh/nomina/reglas')->assertOk()
            ->assertJsonCount(4, 'data')   // 3 de arranque + la nueva
            ->assertJsonPath('catalogos.dias_sueldo', 15)
            ->assertJsonPath('catalogos.metricas.faltas.label', 'Faltas');

        $api->deleteJson('/api/v1/rh/nomina/reglas/'.$id)->assertOk()->assertJsonPath('message', 'Regla «Bono sin faltas» eliminada.');
        $this->assertNull(PayrollRule::find($id));
    }

    public function test_ajustes_cambian_el_valor_del_dia_de_sueldo(): void
    {
        $api = $this->nomina();

        $api->putJson('/api/v1/rh/nomina/ajustes', ['dias_sueldo' => 30])->assertOk()
            ->assertJsonPath('data.dias_sueldo', 30)
            ->assertJsonPath('message', 'Un día de sueldo ahora es el sueldo de la quincena ÷ 30.');
        $api->getJson('/api/v1/rh/nomina/ajustes')->assertJsonPath('data.dias_sueldo', 30);
        $api->getJson('/api/v1/rh/nomina/quincena?desde=2026-10-01')->assertJsonPath('data.filas.0.descuentos', 50);

        $api->putJson('/api/v1/rh/nomina/ajustes', ['dias_sueldo' => 0])->assertStatus(422);
    }

    public function test_sueldos_solo_con_el_permiso_de_nomina(): void
    {
        $rh = $this->usuarioApi(['asistencia.ver', 'asistencia.gestionar', 'asistencia.biometria']);
        $sin = $this->conToken($rh);

        $sin->getJson('/api/v1/rh/nomina/quincena')->assertForbidden();
        $sin->getJson('/api/v1/rh/nomina/empleados')->assertForbidden();
        $sin->getJson('/api/v1/rh/nomina/quincena/exportar')->assertForbidden();
        $sin->putJson("/api/v1/rh/nomina/empleados/{$this->emp->id}/sueldo", ['sueldo_quincenal' => 1])->assertForbidden();
        $sin->getJson('/api/v1/rh/nomina/reglas')->assertForbidden();
        // Lo que sí puede ver no trae el sueldo
        $this->assertStringNotContainsString('sueldo', $sin->getJson('/api/v1/rh/asistencia/empleados/'.$this->emp->id)->assertOk()->getContent());
        $this->assertStringNotContainsString('sueldo', $sin->getJson('/api/v1/rh/asistencia/dia')->assertOk()->getContent());

        $api = $this->nomina();
        $api->getJson('/api/v1/rh/nomina/empleados')->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('sin_sueldo', 2);   // los dos de RH no tienen sueldo
        $api->getJson('/api/v1/rh/nomina/empleados?buscar=Marta')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sueldo_quincenal', 1500)
            ->assertJsonPath('data.0.toma_asistencia', true)
            ->assertJsonPath('data.0.conceptos', []);
        $api->getJson('/api/v1/rh/nomina/empleados?sin_sueldo=1')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('data.0.sueldo_quincenal', null);

        $api->putJson("/api/v1/rh/nomina/empleados/{$this->emp->id}/sueldo", ['sueldo_quincenal' => 1800.5])->assertOk()
            ->assertJsonPath('data.sueldo_quincenal', 1800.5)
            ->assertJsonPath('message', 'Sueldo de Marta Peña guardado.');
        $api->putJson("/api/v1/rh/nomina/empleados/{$this->emp->id}/sueldo", ['sueldo_quincenal' => -1])->assertStatus(422);
    }
}
