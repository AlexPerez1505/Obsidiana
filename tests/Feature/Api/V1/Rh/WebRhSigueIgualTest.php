<?php

namespace Tests\Feature\Api\V1\Rh;

use App\Models\AttendanceModality;
use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\CompanyEvent;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\PayrollConcept;
use App\Models\PayrollRule;
use App\Models\PayrollRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * La lógica de las pantallas de RH se movió a servicios para compartirla
 * con la API. Estas pruebas cuidan que la web siga respondiendo igual
 * (mismos mensajes, mismos errores, mismos efectos).
 */
class WebRhSigueIgualTest extends TestCase
{
    use AyudasRh, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->ahoraLocal('2026-10-14 12:00:00');
    }

    private function rh(): \App\Models\User
    {
        $u = $this->usuarioApi(['asistencia.ver', 'asistencia.gestionar', 'asistencia.biometria', 'asistencia.nomina']);
        $this->actingAs($u);

        return $u;
    }

    public function test_checada_manual_desde_la_web(): void
    {
        $this->rh();
        $emp = $this->empleado(['name' => 'Ana Web']);

        $this->from('/rh/asistencia/checadas')->post(route('asistencia.checadas.manual'), [
            'user_id' => $emp->id, 'fecha' => '2026-10-14', 'hora' => '09:20', 'motivo' => 'Olvido',
        ])->assertRedirect('/rh/asistencia/checadas')->assertSessionHas('status', 'Checada manual registrada para Ana Web.');
        $this->assertSame(EmployeeAttendance::RETARDO, EmployeeAttendance::where('user_id', $emp->id)->value('status'));

        $this->from('/rh/asistencia/checadas')->post(route('asistencia.checadas.manual'), [
            'user_id' => $emp->id, 'fecha' => '2026-10-14', 'hora' => '15:00', 'motivo' => 'Olvido',
        ])->assertRedirect('/rh/asistencia/checadas')
            ->assertSessionHasErrors(['hora' => 'No se puede registrar una checada en el futuro.'])
            ->assertSessionHasInput('motivo', 'Olvido');

        $this->get(route('asistencia.empleado', $emp))->assertOk()->assertViewHas('dias', fn ($d) => count($d) === 31)
            ->assertViewHas('resumen', fn ($r) => $r['retardos'] === 1);
    }

    public function test_horarios_desde_la_web(): void
    {
        $this->rh();
        $emp = $this->usuarioApi(atributos: ['name' => 'Beto Web']);
        $dias = $this->semanaNormal();
        $dias[6] = ['on' => '1', 'in' => '09:00', 'out' => '14:00'];

        $this->from('/rh/asistencia/horarios')->put(route('asistencia.horarios.guardar', $emp), [
            'days' => $dias, 'tolerance_minutes' => '10', 'active' => '1', 'checador_id' => 'W-1',
        ])->assertRedirect('/rh/asistencia/horarios')->assertSessionHas('status', 'Horario de Beto Web guardado.');

        $h = EmployeeSchedule::where('user_id', $emp->id)->first();
        $this->assertSame([1, 2, 3, 4, 5, 6], $h->weekdays);
        $this->assertTrue($h->active);
        $this->assertSame('W-1', $emp->fresh()->checador_id);

        $dias[3] = ['on' => '1', 'in' => '10:00', 'out' => '09:00'];
        $this->from('/rh/asistencia/horarios')->put(route('asistencia.horarios.guardar', $emp), ['days' => $dias, 'tolerance_minutes' => '10'])
            ->assertSessionHasErrors(['days.3' => 'En Mié la salida debe ser después de la entrada.']);

        $this->post(route('asistencia.horarios.masivo'), ['alcance' => 'seleccion', 'days' => $this->semanaNormal(), 'tolerance_minutes' => 5])
            ->assertSessionHasErrors(['user_ids' => 'Marca al menos una persona.']);
        $this->post(route('asistencia.horarios.masivo'), ['alcance' => 'todos', 'days' => $this->semanaNormal(), 'tolerance_minutes' => 5])
            ->assertSessionHas('status', 'Horario aplicado a 2 persona(s).');
        $this->post(route('asistencia.horarios.base'))->assertSessionHas('status', 'Todos ya tienen horario.');

        $this->get(route('asistencia.horarios'))->assertOk();
    }

    public function test_eventos_festivos_e_incidencias_desde_la_web(): void
    {
        $this->rh();
        $emp = $this->empleado(['name' => 'Caro Web']);

        $this->post(route('asistencia.eventos.store'), [
            'name' => 'Comida', 'type' => 'sin_checar', 'date_from' => '2026-10-20', 'date_to' => '2026-10-20', 'scope' => 'todos',
        ])->assertSessionHas('status', 'Evento «Comida» registrado.');
        $this->post(route('asistencia.eventos.store'), [
            'name' => 'X', 'type' => 'horario_especial', 'date_from' => '2026-10-20', 'date_to' => '2026-10-19', 'scope' => 'todos',
        ])->assertSessionHasErrors(['date_to' => 'La fecha final no puede ser anterior a la inicial.', 'time_in' => 'Indica la hora de entrada del horario especial.']);
        $this->delete(route('asistencia.eventos.destroy', CompanyEvent::first()))->assertSessionHas('status', 'Evento eliminado y días recalculados.');

        $this->post(route('asistencia.festivos.store'), ['date' => '2026-12-24', 'name' => 'Nochebuena'])->assertSessionHas('status', 'Festivo agregado.');
        $this->post(route('asistencia.festivos.store'), ['date' => '2026-12-24', 'name' => 'Otra'])->assertSessionHasErrors(['date' => 'Ese día ya está como festivo.']);
        $this->post(route('asistencia.festivos.oficiales'), ['anio' => 2026])
            ->assertSessionHas('status', 'Se agregaron 7 festivo(s) oficiales de 2026. Ya aparecen en la Agenda.');
        $this->delete(route('asistencia.festivos.destroy', Holiday::whereDate('date', '2026-12-24')->first()))->assertSessionHas('status', 'Festivo eliminado.');

        $this->post(route('asistencia.modalidades.store'), [
            'user_id' => $emp->id, 'type' => 'campo', 'date_from' => '2026-10-14', 'date_to' => '2026-10-15', 'requires_fingerprint' => '1',
        ])->assertSessionHas('status', 'Incidencia registrada para Caro Web.');
        $m = AttendanceModality::first();
        $this->assertTrue($m->requires_fingerprint);
        $this->delete(route('asistencia.modalidades.destroy', $m))->assertSessionHas('status', 'Incidencia eliminada y días recalculados.');

        $this->get(route('asistencia.eventos'))->assertOk();
        $this->get(route('asistencia.modalidades'))->assertOk();
    }

    public function test_biometria_desde_la_web(): void
    {
        $this->rh();
        $emp = $this->usuarioApi(atributos: ['name' => 'Dani Web']);

        $this->post(route('asistencia.biometria.dispositivos.store'), ['name' => 'Recepción', 'code' => 'REC-01'])
            ->assertSessionHas('status', 'Lector «Recepción» creado.')
            ->assertSessionHas('llave_nueva', fn ($l) => str_starts_with($l['llave'], 'bk_') && $l['code'] === 'REC-01');
        $d = BiometricDevice::first();

        $this->post(route('asistencia.biometria.registrar', $emp), ['device_id' => $d->id, 'finger_index' => 2])
            ->assertSessionHas('status', 'Solicitud enviada al lector. Dani Web ya puede colocar el dedo.');
        $this->post(route('asistencia.biometria.registrar', $emp), ['device_id' => $d->id, 'finger_index' => 3]);
        $this->assertSame(['cancelled', 'pending'], BiometricEnrollment::orderBy('id')->pluck('status')->all());

        $this->post(route('asistencia.biometria.inscripciones.cancelar', BiometricEnrollment::latest('id')->first()))
            ->assertSessionHas('status', 'Solicitud cancelada.');
        $this->assertSame(['cancelled', 'cancelled'], BiometricEnrollment::orderBy('id')->pluck('status')->all());

        $this->get(route('asistencia.biometria.index'))->assertOk();
    }

    public function test_nomina_desde_la_web(): void
    {
        $this->ahoraLocal('2026-10-20 12:00:00');
        $this->rh();
        $emp = $this->empleado(['name' => 'Eva Web', 'sueldo_quincenal' => 1500]);
        EmployeeAttendance::create(['user_id' => $emp->id, 'attendance_date' => '2026-10-06', 'status' => EmployeeAttendance::FALTA]);

        $this->get(route('nomina.index', ['desde' => '2026-10-01']))->assertOk()
            ->assertViewHas('filas', fn ($f) => $f->first()['descuentos'] == 100 && $f->first()['congelada'] === false)
            ->assertViewHas('concluida', true)
            ->assertViewHas('enCurso', false);

        $this->post(route('nomina.concepto-unico', $emp), ['desde' => '2026-10-01', 'kind' => 'bono', 'name' => 'Extra', 'amount' => 30])
            ->assertSessionHas('status', 'Bono agregado a Eva Web solo para esta quincena.')
            ->assertSessionHas('abrir', 'u'.$emp->id);
        $this->post(route('nomina.conceptos.store', $emp), ['kind' => 'descuento', 'name' => 'Préstamo', 'amount' => 10])
            ->assertSessionHas('status', 'Descuento fijo agregado a Eva Web.');
        $this->assertSame(2, PayrollConcept::count());

        $this->post(route('nomina.pagada'), ['desde' => '2026-10-01'])->assertSessionHasErrors(['periodo' => 'Primero cierra la quincena.']);
        $this->post(route('nomina.cerrar'), ['desde' => '2026-10-16'])
            ->assertSessionHasErrors(['periodo' => 'La quincena todavía no termina: espera al último día para cerrarla.']);
        $this->post(route('nomina.cerrar'), ['desde' => '2026-10-01'])
            ->assertSessionHas('status', 'Quincena cerrada: 1 empleados, neto $1,420.00. Los montos quedaron congelados.');
        $this->post(route('nomina.cerrar'), ['desde' => '2026-10-01'])->assertSessionHasErrors(['periodo' => 'Esta quincena ya está cerrada.']);
        $this->post(route('nomina.concepto-unico', $emp), ['desde' => '2026-10-01'])
            ->assertSessionHasErrors(['periodo' => 'La quincena está cerrada: reábrela para agregar conceptos.']);
        $this->post(route('nomina.pagada'), ['desde' => '2026-10-01'])->assertSessionHas('status', 'Quincena marcada como pagada.');

        $csv = $this->get(route('nomina.exportar', ['desde' => '2026-10-01']))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Eva Web",11,0,1,0,1500.00,30.00,110.00,1420.00', $csv);

        $this->post(route('nomina.reabrir'), ['desde' => '2026-10-01'])
            ->assertSessionHas('status', 'Quincena reabierta: vuelve a calcularse con la asistencia y las reglas actuales.');
        $this->assertSame(0, PayrollRun::count());
        $this->get(route('nomina.historial'))->assertOk();
    }

    public function test_reglas_de_nomina_desde_la_web(): void
    {
        $this->rh();
        $datos = [
            'name' => 'Bono mes', 'kind' => 'bono', 'repeat_mode' => 'una_vez', 'calc' => 'monto', 'amount' => 100,
            'timing' => 'periodo', 'scope' => 'todos', 'active' => '1',
            'conditions' => [['metric' => 'retardos', 'scope' => 'mes', 'op' => '=', 'value' => 0]],
        ];

        $this->post(route('nomina.reglas.store'), $datos)->assertRedirect(route('nomina.reglas'))
            ->assertSessionHas('status', 'Regla «Bono mes» creada. Como mira el mes completo, se aplica al cerrar el mes.');
        $r = PayrollRule::where('name', 'Bono mes')->first();
        $this->assertSame('cierre_mes', $r->timing);

        $this->put(route('nomina.reglas.update', $r), ['name' => 'Bono mes 2'] + $datos)
            ->assertSessionHas('status', 'Regla «Bono mes 2» actualizada. Como mira el mes completo, se aplica al cerrar el mes.');
        $this->post(route('nomina.reglas.store'), ['calc' => 'pct_sueldo', 'amount' => 101] + $datos)
            ->assertSessionHasErrors(['amount' => 'Un porcentaje no puede pasar de 100.']);

        $this->get(route('nomina.reglas'))->assertOk();
        $this->get(route('nomina.empleados'))->assertOk();
    }
}
