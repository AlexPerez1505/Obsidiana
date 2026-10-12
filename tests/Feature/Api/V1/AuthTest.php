<?php

namespace Tests\Feature\Api\V1;

use App\Models\TokenApi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    public function test_login_devuelve_un_token_que_sirve(): void
    {
        $user = $this->usuarioApi(['clientes.ver']);

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'dispositivo' => 'iPhone de pruebas',
        ]);

        $res->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('usuario.id', $user->id)
            ->assertJsonPath('usuario.permisos', ['clientes.ver']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'iPhone de pruebas',
            'tipo' => TokenApi::TIPO_APP,
        ]);
        $this->assertDatabaseCount('login_logs', 1);

        $this->app['auth']->forgetGuards();
        $this->withToken($res->json('token'))
            ->getJson('/api/v1/auth/yo')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.token.tipo', 'app');
    }

    public function test_credenciales_malas_dan_422_en_espanol(): void
    {
        $user = $this->usuarioApi();

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'otra'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Las credenciales no coinciden con nuestros registros.')
            ->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_cuentas_sin_acceso_reciben_403_con_codigo(): void
    {
        $pendiente = $this->usuarioApi();
        $pendiente->forceFill(['status' => User::STATUS_PENDING])->save();

        $baneado = $this->usuarioApi();
        $baneado->forceFill(['status' => User::STATUS_BANNED])->save();

        $this->postJson('/api/v1/auth/login', ['email' => $pendiente->email, 'password' => 'password'])
            ->assertForbidden()->assertJsonPath('codigo', 'cuenta_pendiente');

        $this->postJson('/api/v1/auth/login', ['email' => $baneado->email, 'password' => 'password'])
            ->assertForbidden()->assertJsonPath('codigo', 'cuenta_desactivada');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_un_token_deja_de_servir_si_banean_a_su_dueno(): void
    {
        $user = $this->usuarioApi();
        $peticion = $this->conToken($user);

        $user->forceFill(['status' => User::STATUS_BANNED])->save();

        $peticion->getJson('/api/v1/auth/yo')
            ->assertForbidden()->assertJsonPath('codigo', 'cuenta_desactivada');
    }

    public function test_sin_token_o_con_token_falso_da_401(): void
    {
        $this->getJson('/api/v1/auth/yo')
            ->assertUnauthorized()->assertJsonPath('codigo', 'no_autenticado');

        $this->withToken('1|falso')->getJson('/api/v1/auth/yo')->assertUnauthorized();
    }

    public function test_token_vencido_da_401(): void
    {
        $user = $this->usuarioApi();
        $peticion = $this->conToken($user);
        TokenApi::query()->update(['expires_at' => now()->subMinute()]);

        $peticion->getJson('/api/v1/auth/yo')->assertUnauthorized();
    }

    public function test_logout_revoca_el_token(): void
    {
        $user = $this->usuarioApi();

        $this->conToken($user)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_sesiones_lista_y_cierra_solo_las_propias(): void
    {
        $user = $this->usuarioApi();
        $otro = $this->usuarioApi();
        $ajena = TokenApi::emitir($otro, 'ajena', TokenApi::TIPO_APP)->accessToken;
        $vieja = TokenApi::emitir($user, 'tablet', TokenApi::TIPO_APP)->accessToken;

        $peticion = $this->conToken($user);

        $peticion->getJson('/api/v1/auth/sesiones')
            ->assertOk()->assertJsonCount(2, 'data');

        $peticion->deleteJson('/api/v1/auth/sesiones/'.$ajena->id)->assertNotFound();
        $peticion->deleteJson('/api/v1/auth/sesiones/'.$vieja->id)->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $vieja->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $ajena->id]);
    }

    public function test_la_llave_recorta_los_permisos_aunque_el_dueno_sea_admin(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'api.cuenta', 'can:clientes.eliminar'])
            ->get('/api/v1/_prueba-permiso', fn () => ['ok' => true]);

        $admin = $this->usuarioApi(admin: true);

        $this->conToken($admin, ['*'])->getJson('/api/v1/_prueba-permiso')->assertOk();

        $this->conToken($admin, ['clientes.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/_prueba-permiso')
            ->assertForbidden()
            ->assertJsonPath('message', 'No tienes permiso para hacer esto.');

        $this->conToken($admin, ['clientes.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/auth/yo')
            ->assertJsonPath('data.permisos', ['clientes.ver']);
    }

    public function test_la_llave_tampoco_da_mas_de_lo_que_puede_el_dueno(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'api.cuenta', 'can:clientes.eliminar'])
            ->get('/api/v1/_prueba-permiso', fn () => ['ok' => true]);

        $user = $this->usuarioApi(['clientes.ver']);

        $this->conToken($user, ['clientes.eliminar'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/_prueba-permiso')
            ->assertForbidden();
    }

    public function test_lo_de_admin_exige_llave_con_acceso_total(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'api.cuenta', 'admin'])
            ->get('/api/v1/_prueba-admin', fn () => ['ok' => true]);

        $admin = $this->usuarioApi(admin: true);

        $this->conToken($admin)->getJson('/api/v1/_prueba-admin')->assertOk();
        $this->conToken($admin, ['usuarios.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/_prueba-admin')->assertForbidden();
    }

    public function test_llave_con_lista_de_ip_rechaza_otras_ip(): void
    {
        $user = $this->usuarioApi();

        $this->conToken($user, ['*'], TokenApi::TIPO_INTEGRACION, ['10.0.0.9'])
            ->getJson('/api/v1/auth/yo')
            ->assertForbidden()->assertJsonPath('codigo', 'ip_no_permitida');

        $this->conToken($user, ['*'], TokenApi::TIPO_INTEGRACION, ['127.0.0.1'])
            ->getJson('/api/v1/auth/yo')->assertOk();
    }

    public function test_rutas_inexistentes_y_registros_inexistentes_responden_json(): void
    {
        $user = $this->usuarioApi();

        $this->conToken($user)->getJson('/api/v1/no-existe')
            ->assertNotFound()->assertJsonPath('codigo', 'ruta_no_encontrada');

        // Un abort(404, '…') del código conserva su mensaje.
        Route::middleware(['api', 'auth:sanctum', 'api.cuenta'])
            ->get('/api/v1/_prueba-404', fn () => abort(404, 'Esa pieza no está en esta venta.'));

        $this->conToken($user)->getJson('/api/v1/_prueba-404')
            ->assertNotFound()
            ->assertJsonPath('message', 'Esa pieza no está en esta venta.')
            ->assertJsonPath('codigo', 'no_encontrado');
    }
}
