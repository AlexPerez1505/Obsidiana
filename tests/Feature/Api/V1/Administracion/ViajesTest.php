<?php

namespace Tests\Feature\Api\V1\Administracion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Las tablas trips y trip_expenses no tienen migración todavía (la web de
 * viajes también truena). Mientras falten, la API responde 503 claro; el
 * flujo completo se prueba en cuanto existan.
 */
class ViajesTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function hayTablas(): bool
    {
        return Schema::hasTable('trips') && Schema::hasTable('trip_expenses');
    }

    public function test_solo_administradores(): void
    {
        $user = $this->usuarioApi(['usuarios.ver']);

        $this->conToken($user)->getJson('/api/v1/viajes')->assertForbidden();
    }

    public function test_sin_tablas_avisa_con_503(): void
    {
        if ($this->hayTablas()) {
            $this->markTestSkipped('Las tablas de viajes ya existen.');
        }

        $admin = $this->usuarioApi(admin: true);

        $this->conToken($admin)->postJson('/api/v1/viajes', ['place' => 'León'])
            ->assertStatus(503)
            ->assertJsonPath('message', 'Los viajes todavía no están disponibles: faltan sus tablas en la base.');
    }

    public function test_iniciar_cargar_gastos_y_finalizar(): void
    {
        if (! $this->hayTablas()) {
            $this->markTestSkipped('Faltan las tablas trips y trip_expenses (sin migración).');
        }

        $admin = $this->usuarioApi(admin: true);
        $peticion = $this->conToken($admin);

        $id = $peticion->postJson('/api/v1/viajes', ['place' => 'León'])
            ->assertCreated()->assertJsonPath('data.status', 'in_progress')->json('data.id');

        $gasto = $peticion->postJson("/api/v1/viajes/{$id}/gastos", ['type' => 'fuel', 'amount' => 500])
            ->assertCreated()->assertJsonPath('meta.total', 500)->json('data.id');

        $peticion->patchJson("/api/v1/viajes/{$id}/gastos/{$gasto}", ['type' => 'fuel', 'amount' => 650])
            ->assertOk()->assertJsonPath('meta.total', 650);

        $peticion->postJson("/api/v1/viajes/{$id}/finalizar")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('message', 'Viaje finalizado. Total: $650.00');
    }
}
