<?php

namespace Tests\Feature\Api\V1\Administracion;

use App\Models\AgendaEvent;
use App\Models\User;
use App\Notifications\AgendaEventAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function cita(User $creador, array $extra = []): AgendaEvent
    {
        $event = new AgendaEvent();
        $event->forceFill($extra + [
            'title' => 'Capacitación',
            'event_type' => 'training',
            'start_date' => now()->startOfMonth()->addDays(3)->toDateString(),
            'end_date' => now()->startOfMonth()->addDays(3)->toDateString(),
            'start_time' => '10:00',
            'duration_minutes' => 60,
            'visibility' => 'publico',
            'participants' => [],
            'status' => 'programado',
            'created_by' => $creador->id,
        ])->save();

        return $event;
    }

    public function test_registrar_cita_avisa_a_los_participantes(): void
    {
        Notification::fake();
        $yo = $this->usuarioApi();
        $invitado = $this->usuarioApi();

        $this->conToken($yo)->postJson('/api/v1/agenda', [
            'title' => 'Instalación en hospital',
            'type' => 'install',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-21',
            'time' => '09:30',
            'visibility' => 'participantes',
            'participants' => [$invitado->id, $yo->id],
        ])
            ->assertCreated()
            ->assertJsonPath('data.start_time', '09:30')
            ->assertJsonPath('data.participants', [$invitado->id, $yo->id])
            ->assertJsonPath('data.puede_modificar', true)
            ->assertJsonPath('message', 'Cita registrada para el 20/10/2026.');

        $event = AgendaEvent::firstOrFail();
        $this->assertSame(2, $event->days()->count());
        Notification::assertSentTo($invitado, AgendaEventAssigned::class);
        Notification::assertNotSentTo($yo, AgendaEventAssigned::class);
    }

    public function test_cada_quien_ve_lo_publico_lo_suyo_y_donde_participa(): void
    {
        $yo = $this->usuarioApi();
        $otro = $this->usuarioApi();

        $publica = $this->cita($otro);
        $privadaAjena = $this->cita($otro, ['visibility' => 'privado', 'title' => 'Privada']);
        $participo = $this->cita($otro, ['visibility' => 'participantes', 'participants' => [$yo->id]]);
        $mia = $this->cita($yo, ['visibility' => 'privado']);

        $ids = collect($this->conToken($yo)->getJson('/api/v1/agenda')->assertOk()->json('data'))->pluck('id');

        $this->assertEqualsCanonicalizing([$publica->id, $participo->id, $mia->id], $ids->all());

        $this->conToken($yo)->getJson("/api/v1/agenda/{$privadaAjena->id}")->assertNotFound();
        $this->conToken($yo)->getJson("/api/v1/agenda/{$participo->id}")->assertOk()
            ->assertJsonPath('data.puede_modificar', false);
    }

    public function test_el_rango_por_omision_es_el_mes_en_curso(): void
    {
        $yo = $this->usuarioApi();
        $siguiente = now()->startOfMonth()->addMonth()->addDays(9)->toDateString();
        $event = $this->cita($yo, ['start_date' => $siguiente, 'end_date' => $siguiente]);

        $this->conToken($yo)->getJson('/api/v1/agenda')->assertOk()->assertJsonCount(0, 'data');

        $this->conToken($yo)->getJson("/api/v1/agenda?hasta={$siguiente}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $event->id)
            ->assertJsonPath('meta.desde', now()->startOfMonth()->addMonth()->toDateString())
            ->assertJsonStructure(['meta' => ['calendario_empresa']]);
    }

    public function test_solo_el_creador_o_un_admin_la_cambian(): void
    {
        $creador = $this->usuarioApi();
        $otro = $this->usuarioApi();
        $admin = $this->usuarioApi(admin: true);
        $event = $this->cita($creador);

        $datos = [
            'title' => 'Reprogramada', 'type' => 'meeting', 'start_date' => '2026-11-03',
            'end_date' => '2026-11-03', 'time' => '16:00', 'visibility' => 'publico',
        ];

        $this->conToken($otro)->putJson("/api/v1/agenda/{$event->id}", $datos)->assertForbidden();
        $this->conToken($otro)->deleteJson("/api/v1/agenda/{$event->id}")->assertForbidden();

        $this->conToken($creador)->putJson("/api/v1/agenda/{$event->id}", $datos)
            ->assertOk()->assertJsonPath('data.title', 'Reprogramada')
            ->assertJsonPath('message', 'Cita reprogramada para el 03/11/2026.');

        $this->conToken($admin)->deleteJson("/api/v1/agenda/{$event->id}")
            ->assertOk()->assertJsonPath('message', 'La cita "Reprogramada" fue eliminada.');
        $this->assertSoftDeleted('agenda_events', ['id' => $event->id]);
    }

    public function test_valida_fechas_y_hora(): void
    {
        $yo = $this->usuarioApi();

        $this->conToken($yo)->postJson('/api/v1/agenda', [
            'title' => 'X', 'type' => 'meeting', 'start_date' => '2026-10-20',
            'end_date' => '2026-10-19', 'time' => '25:00', 'visibility' => 'todos',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['end_date', 'time', 'visibility']]);
    }
}
