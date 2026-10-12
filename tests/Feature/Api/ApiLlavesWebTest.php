<?php

namespace Tests\Feature\Api;

use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Configuración → API e integraciones: emitir y revocar llaves.
 */
class ApiLlavesWebTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    public function test_sin_permiso_no_entra(): void
    {
        $user = $this->usuarioApi(['clientes.ver']);

        $this->actingAs($user)->get('/configuracion/api')->assertForbidden();
    }

    public function test_la_pantalla_carga_con_llaves_y_sesiones(): void
    {
        $admin = $this->usuarioApi(admin: true);
        TokenApi::emitir($admin, 'Jureto', TokenApi::TIPO_INTEGRACION, ['clientes.ver']);
        TokenApi::emitir($admin, 'iPhone', TokenApi::TIPO_APP);

        $this->actingAs($admin)->get('/configuracion/api')
            ->assertOk()
            ->assertSee('Jureto')
            ->assertSee('iPhone')
            ->assertSee(url('/api/v1'));
    }

    public function test_admin_emite_a_nombre_de_otro_y_la_llave_solo_puede_lo_que_ese_tiene(): void
    {
        $admin = $this->usuarioApi(admin: true);
        $vendedor = $this->usuarioApi(['clientes.ver', 'cotizaciones.ver']);

        $res = $this->actingAs($admin)->post('/configuracion/api/llaves', [
            'name' => 'Página web',
            'user_id' => $vendedor->id,
            'acceso' => 'limitado',
            // ventas.ver no lo tiene el vendedor: no debe entrar en la llave.
            'permisos' => ['clientes.ver', 'ventas.ver'],
            'ips' => '200.1.2.3, 10.0.0.5',
        ]);

        $res->assertRedirect('/configuracion/api')->assertSessionHas('llave_nueva');

        $llave = TokenApi::where('name', 'Página web')->firstOrFail();
        $this->assertSame($vendedor->id, (int) $llave->tokenable_id);
        $this->assertSame(TokenApi::TIPO_INTEGRACION, $llave->tipo);
        $this->assertSame(['clientes.ver'], $llave->abilities);
        $this->assertSame(['200.1.2.3', '10.0.0.5'], $llave->ip_permitidas);
        $this->assertSame($admin->id, (int) $llave->creado_por_id);
        $this->assertStringStartsWith(config('api.prefijo_llave'), explode('|', session('llave_nueva')['texto'], 2)[1]);
        $this->assertStringContainsString('1 permiso(s)', session('status'));
    }

    public function test_quien_no_es_admin_solo_emite_a_su_nombre(): void
    {
        $user = $this->usuarioApi(['api.llaves', 'clientes.ver']);
        $otro = $this->usuarioApi(['clientes.ver']);

        $this->actingAs($user)->post('/configuracion/api/llaves', [
            'name' => 'Mi script',
            'user_id' => $otro->id,
            'acceso' => 'total',
        ])->assertRedirect();

        $this->assertSame($user->id, (int) TokenApi::where('name', 'Mi script')->value('tokenable_id'));
    }

    public function test_la_llave_emitida_funciona_en_la_api(): void
    {
        $admin = $this->usuarioApi(admin: true);

        $this->actingAs($admin)->post('/configuracion/api/llaves', [
            'name' => 'n8n', 'acceso' => 'limitado', 'permisos' => ['clientes.ver'],
        ]);

        $texto = session('llave_nueva')['texto'];
        $this->app['auth']->forgetGuards();

        $this->withToken($texto)->getJson('/api/v1/auth/yo')
            ->assertOk()
            ->assertJsonPath('data.token.tipo', 'integracion')
            ->assertJsonPath('data.permisos', ['clientes.ver']);
    }

    public function test_valida_permisos_e_ip(): void
    {
        $admin = $this->usuarioApi(admin: true);

        $this->actingAs($admin)->post('/configuracion/api/llaves', [
            'name' => 'Sin nada', 'acceso' => 'limitado', 'permisos' => [],
        ])->assertSessionHasErrors('permisos');

        $this->actingAs($admin)->post('/configuracion/api/llaves', [
            'name' => 'IP mala', 'acceso' => 'total', 'ips' => '999.1.1.1',
        ])->assertSessionHasErrors('ips');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_revocar_solo_lo_propio_salvo_admin(): void
    {
        $user = $this->usuarioApi(['api.llaves']);
        $otro = $this->usuarioApi(['api.llaves']);
        $ajena = TokenApi::emitir($otro, 'ajena', TokenApi::TIPO_INTEGRACION)->accessToken;
        $propia = TokenApi::emitir($user, 'propia', TokenApi::TIPO_INTEGRACION)->accessToken;

        $this->actingAs($user)->delete('/configuracion/api/tokens/'.$ajena->id)->assertForbidden();
        $this->actingAs($user)->delete('/configuracion/api/tokens/'.$propia->id)->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $propia->id]);

        $admin = $this->usuarioApi(admin: true);
        $this->actingAs($admin)->delete('/configuracion/api/tokens/'.$ajena->id)->assertRedirect();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_banear_al_usuario_revoca_sus_tokens(): void
    {
        $admin = $this->usuarioApi(admin: true);
        $user = $this->usuarioApi();
        TokenApi::emitir($user, 'app', TokenApi::TIPO_APP);
        TokenApi::emitir($user, 'llave', TokenApi::TIPO_INTEGRACION);

        $this->actingAs($admin)->post('/admin/usuarios/'.$user->id.'/banear')->assertRedirect();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
