<?php

namespace Tests\Feature\Api\V1\Cuenta;

use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class CuentaTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function sesionWeb(int $userId, string $id): void
    {
        DB::table('sessions')->insert([
            'id' => $id, 'user_id' => $userId, 'ip_address' => '1.2.3.4',
            'user_agent' => 'Firefox', 'payload' => '', 'last_activity' => now()->timestamp,
        ]);
    }

    public function test_mi_cuenta_trae_conexiones_y_sesiones_web(): void
    {
        $user = $this->usuarioApi();
        $user->loginLogs()->create(['ip_address' => '10.1.1.1', 'logged_at' => now()]);
        $this->sesionWeb($user->id, 'web-a');

        $this->conToken($user)->getJson('/api/v1/cuenta')
            ->assertOk()
            ->assertJsonCount(1, 'data.conexiones')
            ->assertJsonPath('data.conexiones.0.ip_address', '10.1.1.1')
            ->assertJsonCount(1, 'data.sesiones_web')
            ->assertJsonMissingPath('data.sesiones_web.0.id');
    }

    public function test_cerrar_sesiones_web_pide_contrasena(): void
    {
        $user = $this->usuarioApi();
        $otro = $this->usuarioApi();
        $this->sesionWeb($user->id, 'web-a');
        $this->sesionWeb($user->id, 'web-b');
        $this->sesionWeb($otro->id, 'web-c');

        $this->conToken($user)->postJson('/api/v1/cuenta/cerrar-sesiones-web', ['password' => 'mala'])
            ->assertStatus(422);

        $this->conToken($user)->postJson('/api/v1/cuenta/cerrar-sesiones-web', ['password' => 'password'])
            ->assertOk()->assertJsonPath('cerradas', 2);

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $otro->id)->count());
    }

    public function test_eliminar_mi_cuenta_pide_contrasena_y_escribir_eliminar(): void
    {
        $user = $this->usuarioApi();
        TokenApi::emitir($user, 'otra app', TokenApi::TIPO_APP);

        $this->conToken($user)->deleteJson('/api/v1/cuenta', ['password' => 'password', 'confirm' => 'eliminar'])
            ->assertStatus(422)
            ->assertJsonPath('errors.confirm.0', 'Debes escribir ELIMINAR para confirmar.');

        $this->conToken($user)->deleteJson('/api/v1/cuenta', ['password' => 'mala', 'confirm' => 'ELIMINAR'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['password']]);

        $this->conToken($user)->deleteJson('/api/v1/cuenta', ['password' => 'password', 'confirm' => 'ELIMINAR'])
            ->assertOk()->assertJsonPath('message', 'Tu cuenta fue eliminada.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
    }
}
