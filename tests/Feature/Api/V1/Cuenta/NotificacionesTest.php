<?php

namespace Tests\Feature\Api\V1\Cuenta;

use App\Models\AgendaEvent;
use App\Models\User;
use App\Notifications\AgendaEventAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function avisar(User $user, string $titulo): void
    {
        $event = new AgendaEvent();
        $event->forceFill([
            'title' => $titulo, 'event_type' => 'meeting', 'start_date' => '2026-10-20', 'end_date' => '2026-10-20',
            'start_time' => '10:00', 'visibility' => 'publico', 'status' => 'programado', 'created_by' => $user->id,
        ])->save();

        $user->notify(new AgendaEventAssigned($event));
    }

    public function test_listar_marcar_una_y_todas(): void
    {
        $user = $this->usuarioApi();
        $otro = $this->usuarioApi();
        $this->avisar($user, 'Junta');
        $this->avisar($user, 'Entrega');
        $this->avisar($otro, 'Ajena');

        $res = $this->conToken($user)->getJson('/api/v1/notificaciones')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.no_leidas', 2)
            ->assertJsonPath('data.0.leida', false)
            ->assertJsonPath('data.0.tipo', 'AgendaEventAssigned');

        $this->assertNotNull($res->json('data.0.titulo'));
        $this->assertStringContainsString('/admin/agenda', $res->json('data.0.url'));

        $id = $res->json('data.0.id');
        $this->conToken($user)->postJson("/api/v1/notificaciones/{$id}/leer")
            ->assertOk()->assertJsonPath('data.leida', true)->assertJsonPath('meta.no_leidas', 1);

        $this->conToken($user)->getJson('/api/v1/notificaciones?no_leidas=1')
            ->assertOk()->assertJsonCount(1, 'data');

        // La de otro usuario no existe para mí.
        $ajena = $otro->notifications()->first()->id;
        $this->conToken($user)->postJson("/api/v1/notificaciones/{$ajena}/leer")->assertNotFound();

        $this->conToken($user)->postJson('/api/v1/notificaciones/leer-todas')
            ->assertOk()->assertJsonPath('marcadas', 1);
        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(1, $otro->unreadNotifications()->count());
    }
}
