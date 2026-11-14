<?php

namespace Tests\Feature;

use App\Models\AgendaActividad;
use App\Models\AgendaRecordatorio;
use App\Models\ClienteSeguimiento;
use App\Models\Customer;
use App\Models\User;
use App\Services\Agenda\Actividades;
use App\Services\Agenda\Recordatorios;
use App\Services\Clientes\SeguimientosCliente;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Reglas críticas de la agenda: los seguimientos nacen en Clientes y son la
 * misma fila en ambos módulos, la reasignación los mueve de agenda, no se
 * duplican, la privacidad se respeta en el servidor y los recordatorios se
 * cancelan o mueven cuando la actividad cambia.
 */
class AgendaTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private function usuario(array $permisos = [], bool $admin = false): User
    {
        $u = $this->usuarioApi(array_merge(['agenda.ver', 'clientes.ver', 'clientes.editar'], $permisos), admin: $admin);
        $u->setAttribute('ficha_completada', true);

        return $u;
    }

    private function fecha(int $dias): string
    {
        return CarbonImmutable::now(config('agenda.timezone'))->addDays($dias)->toDateString();
    }

    private function prospecto(User $asesor): Customer
    {
        return Customer::create(['nombre' => 'HOSPITAL SAN JOSÉ '.Str::random(4), 'apellido' => null, 'telefono' => '55'.random_int(10000000, 99999999),
            'direccion' => 'x', 'etapa' => 'prospecto', 'activo' => true, 'asesor_id' => $asesor->id]);
    }

    private function datos(User $u, string $desde, string $hasta, array $extra = []): array
    {
        return $this->actingAs($u)->getJson(route('agenda.datos', ['desde' => $desde, 'hasta' => $hasta] + $extra))->assertOk()->json('items');
    }

    /** Ejemplo obligatorio: Hospital San José, usuario A, 10:00, torre de endoscopia. */
    public function test_seguimiento_de_clientes_aparece_en_la_agenda_y_se_sincroniza_en_ambos_sentidos(): void
    {
        $admin = $this->usuario(['clientes.ver_todos', 'agenda.reasignar', 'agenda.ver_todos']);
        $a = $this->usuario();
        $b = $this->usuario();
        $cliente = $this->prospecto($a);
        $dia = $this->fecha(6);

        // 1. Se programa en Clientes, a nombre de A.
        $this->actingAs($admin)->post(route('commercial.clientes.seguimientos.store', $cliente), [
            'tipo' => 'llamada', 'fecha' => $dia, 'hora' => '10:00', 'nota' => 'Confirmar propuesta de torre de endoscopia', 'user_id' => $a->id,
        ])->assertRedirect();
        $s = ClienteSeguimiento::where('customer_id', $cliente->id)->sole();

        $items = $this->datos($a, $dia, $dia);
        $this->assertSame([$s->llaveAgenda()], array_column($items, 'key'));
        $this->assertSame('10:00', $items[0]['hora']);
        $this->assertSame('Confirmar propuesta de torre de endoscopia', $items[0]['motivo']);
        // 3. Expediente directo.
        $this->assertStringContainsString('/gestion-comercial/clientes/'.$cliente->id, $items[0]['cliente']['url']);

        // 2. Recordatorio configurado (15 min antes por omisión) + aviso de vencido.
        $rec = AgendaRecordatorio::where('item_type', 'seguimiento')->where('item_id', $s->id)->where('tipo', 'recordatorio')->sole();
        $this->assertSame($dia.' 09:45', $rec->enviar_en->setTimezone(config('agenda.timezone'))->format('Y-m-d H:i'));

        // 4. Se cambia la fecha en Clientes: misma actividad.
        $otro = $this->fecha(8);
        $this->actingAs($a)->post(route('commercial.clientes.seguimientos.reprogramar', [$cliente, $s]), ['fecha' => $otro, 'hora' => '11:30'])->assertRedirect();
        $this->assertSame(1, ClienteSeguimiento::where('customer_id', $cliente->id)->count());
        $this->assertSame([$s->llaveAgenda()], array_column($this->datos($a, $otro, $otro), 'key'));
        $this->assertSame($otro.' 11:15', $rec->fresh()->enviar_en->setTimezone(config('agenda.timezone'))->format('Y-m-d H:i'));

        // 5. Se reprograma desde la Agenda: se refleja en Clientes, con historial.
        $tercero = $this->fecha(9);
        $this->actingAs($a)->postJson(route('agenda.mover', $s->llaveAgenda()), ['fecha' => $tercero, 'hora' => '12:00', 'motivo' => 'Lo pidió el cliente'])->assertOk();
        $this->assertSame($tercero, $s->fresh()->fecha->toDateString());
        $this->assertSame('12:00', $s->fresh()->horaCorta());
        $this->assertDatabaseHas('agenda_historial', ['item_type' => 'seguimiento', 'item_id' => $s->id, 'accion' => 'reprogramado', 'motivo' => 'Lo pidió el cliente']);

        // 6. Reasignación a B: aparece en su agenda y deja la de A.
        $this->actingAs($admin)->postJson(route('agenda.reasignar', $s->llaveAgenda()), ['user_id' => $b->id])->assertOk();
        $this->assertSame([], $this->datos($a, $tercero, $tercero));
        $this->assertSame([$s->llaveAgenda()], array_column($this->datos($b, $tercero, $tercero), 'key'));
        $this->assertSame(0, AgendaRecordatorio::where('item_id', $s->id)->where('item_type', 'seguimiento')->where('user_id', $a->id)->where('estado', 'pendiente')->count());
        $this->assertTrue(AgendaRecordatorio::where('item_id', $s->id)->where('item_type', 'seguimiento')->where('user_id', $b->id)->where('estado', 'pendiente')->exists());
        $this->assertTrue(DB::table('notifications')->where('notifiable_id', $b->id)->where('data->agenda_tipo', 'asignada')->exists());

        // 7. Completar con resultado y siguiente contacto: son dos actividades distintas.
        $sig = $this->fecha(20);
        $cuerpo = ['estado' => 'completado', 'resultado' => 'Pidió ajustar precio', 'siguiente_fecha' => $sig, 'siguiente_tipo' => 'cotizacion'];
        $this->actingAs($b)->postJson(route('agenda.estado', $s->llaveAgenda()), $cuerpo)->assertOk();
        $this->actingAs($b)->postJson(route('agenda.estado', $s->llaveAgenda()), $cuerpo)->assertOk(); // reintento
        $this->assertSame('completado', $s->fresh()->estado);
        $this->assertSame(2, ClienteSeguimiento::where('customer_id', $cliente->id)->count());
        $siguiente = ClienteSeguimiento::where('anterior_id', $s->id)->sole();
        $this->assertSame($b->id, (int) $siguiente->user_id);
        $this->assertSame(0, AgendaRecordatorio::where('item_id', $s->id)->where('item_type', 'seguimiento')->where('estado', 'pendiente')->count());

        // 8. Solo usuarios autorizados: alguien ajeno no lo ve ni por URL.
        $ajeno = $this->usuario();
        $this->actingAs($ajeno)->getJson(route('agenda.item', $siguiente->llaveAgenda()))->assertNotFound();
        $this->assertSame([], $this->datos($ajeno, $sig, $sig));
        // Y en la agenda de la empresa nunca salen los seguimientos.
        $this->assertSame([], array_values(array_filter($this->datos($b, $sig, $sig, ['ambito' => 'empresa']), fn ($i) => $i['fuente'] === 'seguimiento')));
    }

    public function test_sin_hora_queda_pendiente_del_dia_y_avisa_a_la_hora_configurada(): void
    {
        $a = $this->usuario();
        $cliente = $this->prospecto($a);
        $dia = $this->fecha(3);
        $s = app(SeguimientosCliente::class)->programar($cliente, ['tipo' => 'visita', 'fecha' => $dia], $a);

        $item = $this->datos($a, $dia, $dia)[0];
        $this->assertNull($item['hora']);
        $this->assertTrue($item['todo_el_dia']);
        $rec = AgendaRecordatorio::where('item_id', $s->id)->where('item_type', 'seguimiento')->where('tipo', 'recordatorio')->sole();
        $this->assertSame($dia.' '.config('agenda.hora_aviso_sin_hora'), $rec->enviar_en->setTimezone(config('agenda.timezone'))->format('Y-m-d H:i'));
    }

    public function test_recordatorios_no_se_duplican_y_se_cancelan_al_completar(): void
    {
        $a = $this->usuario();
        $s = app(SeguimientosCliente::class)->programar($this->prospecto($a), ['tipo' => 'llamada', 'fecha' => $this->fecha(1), 'hora' => '09:00'], $a);
        $r = app(Recordatorios::class);

        $r->programar($s);
        $r->programar($s);
        $this->assertSame(1, AgendaRecordatorio::where('item_id', $s->id)->where('item_type', 'seguimiento')->where('tipo', 'recordatorio')->count());

        AgendaRecordatorio::where('item_id', $s->id)->where('item_type', 'seguimiento')->where('tipo', 'recordatorio')->update(['enviar_en' => now()->subMinute()]);
        $this->assertSame(1, $r->despachar());
        $this->assertSame(0, $r->despachar());
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $a->id)->where('data->agenda_tipo', 'recordatorio')->count());

        app(SeguimientosCliente::class)->marcarHecho($s, 'Listo', $a);
        $this->assertSame(0, AgendaRecordatorio::where('item_id', $s->id)->where('item_type', 'seguimiento')->where('estado', 'pendiente')->count());
    }

    public function test_evento_de_empresa_es_uno_solo_para_todos_y_avisa_cambios(): void
    {
        $organizador = $this->usuario(['agenda.empresa']);
        $alguien = $this->usuario();
        $dia = $this->fecha(5);

        $this->actingAs($organizador)->postJson(route('agenda.store'), [
            'titulo' => 'Capacitación de seguridad', 'tipo' => 'capacitacion', 'fecha_inicio' => $dia, 'hora_inicio' => '09:00', 'hora_fin' => '11:00', 'visibilidad' => 'empresa',
        ])->assertCreated();
        $evento = AgendaActividad::where('titulo', 'Capacitación de seguridad')->sole();

        $this->assertContains($evento->llaveAgenda(), array_column($this->datos($alguien, $dia, $dia), 'key'));
        $this->assertContains($evento->llaveAgenda(), array_column($this->datos($alguien, $dia, $dia, ['ambito' => 'empresa']), 'key'));
        $this->assertTrue(DB::table('notifications')->where('notifiable_id', $alguien->id)->where('data->agenda_tipo', 'evento_empresa')->exists());

        // Quien solo es destinatario no lo puede modificar.
        $this->actingAs($alguien)->putJson(route('agenda.update', $evento->llaveAgenda()), ['titulo' => 'x', 'tipo' => 'capacitacion', 'fecha_inicio' => $dia, 'visibilidad' => 'empresa'])->assertForbidden();

        $this->actingAs($organizador)->putJson(route('agenda.update', $evento->llaveAgenda()), [
            'titulo' => 'Capacitación de seguridad', 'tipo' => 'capacitacion', 'fecha_inicio' => $dia, 'hora_inicio' => '12:00', 'hora_fin' => '13:00', 'visibilidad' => 'empresa',
        ])->assertOk();
        $this->assertSame(1, AgendaActividad::where('titulo', 'Capacitación de seguridad')->count());
        $this->assertSame('12:00', collect($this->datos($alguien, $dia, $dia))->firstWhere('key', $evento->llaveAgenda())['hora']);
        $this->assertTrue(DB::table('notifications')->where('notifiable_id', $alguien->id)->where('data->agenda_tipo', 'reprogramada')->exists());

        // Sin permiso no se publica para la empresa.
        $this->actingAs($alguien)->postJson(route('agenda.store'), ['titulo' => 'x', 'tipo' => 'evento_empresa', 'fecha_inicio' => $dia, 'visibilidad' => 'empresa'])->assertStatus(422);
    }

    public function test_invitado_responde_sin_poder_editar_y_privado_sale_como_ocupado(): void
    {
        $a = $this->usuario();
        $b = $this->usuario();
        $supervisor = $this->usuario(['agenda.ver_todos']);
        $dia = $this->fecha(4);

        $this->actingAs($a)->postJson(route('agenda.store'), ['titulo' => 'Revisión de propuesta', 'tipo' => 'reunion', 'fecha_inicio' => $dia, 'hora_inicio' => '10:00', 'participantes' => [$b->id]])->assertCreated();
        $reunion = AgendaActividad::where('titulo', 'Revisión de propuesta')->sole();

        $this->actingAs($b)->postJson(route('agenda.responder', $reunion->llaveAgenda()), ['respuesta' => 'aceptada'])->assertOk();
        $this->assertSame('aceptada', $reunion->participantes()->first()->pivot->respuesta);
        $this->actingAs($b)->postJson(route('agenda.cancelar', $reunion->llaveAgenda()))->assertForbidden();

        $this->actingAs($a)->postJson(route('agenda.store'), ['titulo' => 'Cita médica', 'tipo' => 'bloqueo', 'fecha_inicio' => $dia, 'hora_inicio' => '16:00', 'visibilidad' => 'privado'])->assertCreated();
        $privado = AgendaActividad::where('titulo', 'Cita médica')->sole();

        $visto = collect($this->datos($supervisor, $dia, $dia, ['usuario' => $a->id]))->firstWhere('key', 'o-'.$privado->id);
        $this->assertSame('Ocupado', $visto['titulo']);
        $this->assertNull($visto['cliente']);
        $this->actingAs($supervisor)->getJson(route('agenda.item', $privado->llaveAgenda()))->assertNotFound();
        $this->actingAs($b)->getJson(route('agenda.item', $privado->llaveAgenda()))->assertNotFound();
        // Ver la agenda de alguien sin ser su supervisor: prohibido.
        $this->actingAs($b)->getJson(route('agenda.datos', ['desde' => $dia, 'hasta' => $dia, 'usuario' => $a->id]))->assertForbidden();
        // El ICS tampoco expone lo privado.
        $ics = $this->actingAs($supervisor)->get(route('agenda.ics', ['desde' => $dia, 'hasta' => $dia]))->assertOk()->getContent();
        $this->assertStringNotContainsString('Cita médica', $ics);
    }

    public function test_choque_de_horario_pide_confirmacion(): void
    {
        $a = $this->usuario();
        $dia = $this->fecha(2);
        $base = ['tipo' => 'reunion', 'fecha_inicio' => $dia, 'hora_inicio' => '10:00', 'hora_fin' => '11:00'];

        $this->actingAs($a)->postJson(route('agenda.store'), $base + ['titulo' => 'Primera'])->assertCreated();
        $this->actingAs($a)->postJson(route('agenda.store'), ['titulo' => 'Segunda', 'hora_inicio' => '10:30'] + $base)->assertStatus(409)->assertJsonStructure(['conflictos']);
        $this->actingAs($a)->postJson(route('agenda.store'), ['titulo' => 'Segunda', 'hora_inicio' => '10:30', 'confirmar_conflictos' => true] + $base)->assertCreated();
        // Un evento informativo no bloquea.
        $this->actingAs($a)->postJson(route('agenda.store'), ['titulo' => 'Aviso', 'tipo' => 'tarea_admin', 'fecha_inicio' => $dia, 'hora_inicio' => '10:15'])->assertCreated();
    }

    public function test_recurrencia_edita_solo_esta_o_las_siguientes(): void
    {
        $a = $this->usuario();
        $serie = app(Actividades::class)->crear(['titulo' => 'Junta semanal', 'tipo' => 'reunion', 'fecha_inicio' => $this->fecha(1), 'hora_inicio' => '08:00', 'recurrencia' => ['freq' => 'semanal', 'veces' => 4]], $a);
        $this->assertCount(4, $serie);
        $this->assertSame(1, $serie->pluck('serie_id')->unique()->count());

        $this->actingAs($a)->putJson(route('agenda.update', $serie[1]->llaveAgenda()), [
            'titulo' => 'Junta semanal', 'tipo' => 'reunion', 'fecha_inicio' => $serie[1]->fecha_inicio->toDateString(), 'hora_inicio' => '09:00', 'alcance' => 'futuras', 'confirmar_conflictos' => true,
        ])->assertOk();
        $this->assertSame(['08:00', '09:00', '09:00', '09:00'], AgendaActividad::where('serie_id', $serie[0]->serie_id)->orderBy('fecha_inicio')->get()->map(fn ($o) => substr($o->hora_inicio, 0, 5))->all());

        $this->actingAs($a)->postJson(route('agenda.cancelar', $serie[3]->llaveAgenda()), ['alcance' => 'una'])->assertOk();
        $this->assertSame('cancelado', $serie[3]->fresh()->estado);
        $this->assertSame('programado', $serie[2]->fresh()->estado);
        $this->assertSame(0, AgendaRecordatorio::where('item_type', 'actividad')->where('item_id', $serie[3]->id)->where('estado', 'pendiente')->count());
    }

    public function test_vencido_solo_aplica_a_tareas_no_a_reuniones_pasadas(): void
    {
        $a = $this->usuario();
        $ayer = $this->fecha(-1);
        $act = app(Actividades::class);
        $reunion = $act->crear(['titulo' => 'Reunión pasada', 'tipo' => 'reunion', 'fecha_inicio' => $ayer, 'hora_inicio' => '10:00'], $a)->first();
        $tarea = $act->crear(['titulo' => 'Tarea pasada', 'tipo' => 'tarea_admin', 'fecha_inicio' => $ayer], $a)->first();

        $this->assertFalse($reunion->vencida());
        $this->assertTrue($tarea->vencida());
        $vencidos = $this->actingAs($a)->getJson(route('agenda.paneles'))->assertOk()->json('vencidos');
        $this->assertSame([$tarea->llaveAgenda()], array_column($vencidos, 'key'));
    }

    public function test_archivar_cliente_cancela_sus_seguimientos_y_conserva_el_historial(): void
    {
        $a = $this->usuario(admin: true);
        $cliente = $this->prospecto($a);
        $s = app(SeguimientosCliente::class)->programar($cliente, ['tipo' => 'llamada', 'fecha' => $this->fecha(2)], $a);

        $this->actingAs($a)->patch(route('commercial.clientes.toggleActivo', $cliente))->assertRedirect();
        $this->assertSame('cancelado', $s->fresh()->estado);
        $this->assertDatabaseHas('agenda_historial', ['item_type' => 'seguimiento', 'item_id' => $s->id, 'accion' => 'cancelado']);
        $this->assertSame(0, AgendaRecordatorio::where('item_id', $s->id)->where('item_type', 'seguimiento')->where('estado', 'pendiente')->count());
    }

    public function test_prospecto_sin_seguimiento_aparece_en_su_seccion_sin_fecha_inventada(): void
    {
        $a = $this->usuario();
        $cliente = $this->prospecto($a);

        $sin = $this->actingAs($a)->getJson(route('agenda.paneles'))->assertOk()->json('sin_seguimiento');
        $this->assertContains($cliente->id, array_column($sin['lista'], 'id'));
        $this->assertSame(0, ClienteSeguimiento::where('customer_id', $cliente->id)->count());
    }

    public function test_seguimiento_creado_desde_la_agenda_vive_en_clientes(): void
    {
        $a = $this->usuario();
        $cliente = $this->prospecto($a);

        $this->actingAs($a)->postJson(route('agenda.store'), [
            'tipo' => 'seguimiento', 'customer_id' => $cliente->id, 'subtipo' => 'demo', 'fecha_inicio' => $this->fecha(3), 'descripcion' => 'Demostración en sitio',
        ])->assertCreated();

        $s = ClienteSeguimiento::where('customer_id', $cliente->id)->sole();
        $this->assertSame('agenda', $s->origen);
        $this->assertSame(0, AgendaActividad::count() - AgendaActividad::where('tipo', '!=', 'seguimiento')->count());
        $this->actingAs($a)->get(route('commercial.clientes.show', $cliente))->assertOk()->assertSee('Demostración en sitio');
    }

    public function test_incorporar_existentes_no_duplica(): void
    {
        $a = $this->usuario();
        $cliente = $this->prospecto($a);
        $s = $cliente->seguimientos()->create(['user_id' => $a->id, 'tipo' => 'llamada', 'fecha' => $this->fecha(2)]);

        $this->artisan('agenda:sincronizar')->assertSuccessful();
        $this->artisan('agenda:sincronizar')->assertSuccessful();

        $this->assertSame(1, $s->historialAgenda()->where('accion', 'importado')->count());
        $this->assertSame(1, AgendaRecordatorio::where('item_id', $s->id)->where('item_type', 'seguimiento')->where('tipo', 'recordatorio')->count());
    }
}
