<?php

namespace Tests\Feature\Api\V1\Administracion;

use App\Mail\AccountStatusMail;
use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\TokenApi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    public function test_listar_pide_usuarios_ver(): void
    {
        $sin = $this->usuarioApi();
        $this->conToken($sin)->getJson('/api/v1/usuarios')
            ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');

        $con = $this->usuarioApi(['usuarios.ver']);
        $pendiente = $this->usuarioApi();
        $pendiente->forceFill(['status' => User::STATUS_PENDING])->save();

        $this->conToken($con)->getJson('/api/v1/usuarios?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendiente->id)
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'roles', 'login_logs_count', 'sesiones_activas']], 'links', 'meta']);
    }

    public function test_el_detalle_no_trae_secretos_y_si_sus_documentos(): void
    {
        Storage::fake('public');
        $admin = $this->usuarioApi(['usuarios.ver']);
        $otro = $this->usuarioApi();
        $otro->forceFill(['sueldo_quincenal' => 9999, 'verification_code' => 'x', 'approval_pin_hash' => 'y'])->save();
        EmployeeDocument::create(['user_id' => $otro->id, 'name' => 'INE', 'file_path' => 'employee_documents/1/ine.pdf', 'file_type' => 'application/pdf', 'file_size' => 10]);

        $res = $this->conToken($admin)->getJson("/api/v1/usuarios/{$otro->id}")->assertOk();

        $res->assertJsonPath('data.documentos.0.name', 'INE')
            ->assertJsonPath('data.documentos.0.file_url', Storage::disk('public')->url('employee_documents/1/ine.pdf'));

        foreach (['password', 'remember_token', 'verification_code', 'approval_pin_hash', 'sueldo_quincenal', 'porcentaje_comision'] as $campo) {
            $res->assertJsonMissingPath("data.{$campo}");
        }
    }

    public function test_aprobar_da_acceso_y_avisa_por_correo(): void
    {
        Mail::fake();
        $quien = $this->usuarioApi(['usuarios.aprobar']);
        $nuevo = $this->usuarioApi();
        $nuevo->forceFill(['status' => User::STATUS_PENDING, 'approved_at' => null])->save();

        $this->conToken($quien)->postJson("/api/v1/usuarios/{$nuevo->id}/aprobar")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('message', "Aprobaste el acceso de {$nuevo->name}. Se le notificó por correo.");

        $this->assertNotNull($nuevo->fresh()->approved_at);
        Mail::assertSent(AccountStatusMail::class, fn (AccountStatusMail $m) => $m->type === 'approved' && $m->hasTo($nuevo->email));
    }

    public function test_banear_cierra_sesiones_web_y_tokens_y_avisa(): void
    {
        Mail::fake();
        $quien = $this->usuarioApi(['usuarios.aprobar']);
        $victima = $this->usuarioApi();
        TokenApi::emitir($victima, 'su celular', TokenApi::TIPO_APP);
        DB::table('sessions')->insert([
            'id' => 'sesion-web-1', 'user_id' => $victima->id, 'ip_address' => '1.1.1.1',
            'user_agent' => 'x', 'payload' => '', 'last_activity' => now()->timestamp,
        ]);

        $this->conToken($quien)->postJson("/api/v1/usuarios/{$victima->id}/banear", ['banned_reason' => 'Ya no trabaja aquí'])
            ->assertOk()
            ->assertJsonPath('data.status', 'banned')
            ->assertJsonPath('data.banned_reason', 'Ya no trabaja aquí');

        $this->assertSame(0, DB::table('sessions')->where('user_id', $victima->id)->count());
        $this->assertSame(0, $victima->tokens()->count());
        Mail::assertSent(AccountStatusMail::class, fn (AccountStatusMail $m) => $m->type === 'banned' && $m->reason === 'Ya no trabaja aquí');

        $this->conToken($quien)->postJson("/api/v1/usuarios/{$victima->id}/desbanear")
            ->assertOk()->assertJsonPath('data.status', 'approved');
        Mail::assertSent(AccountStatusMail::class, fn (AccountStatusMail $m) => $m->type === 'reactivated');
    }

    public function test_nadie_se_desactiva_a_si_mismo(): void
    {
        Mail::fake();
        $quien = $this->usuarioApi(['usuarios.aprobar']);

        $this->conToken($quien)->postJson("/api/v1/usuarios/{$quien->id}/banear")
            ->assertForbidden()
            ->assertJsonPath('message', 'No puedes desactivar tu propia cuenta.');

        $this->assertTrue($quien->fresh()->isApproved());
        Mail::assertNothingSent();
    }

    public function test_banear_valida_el_motivo(): void
    {
        $quien = $this->usuarioApi(['usuarios.aprobar']);
        $otro = $this->usuarioApi();

        $this->conToken($quien)->postJson("/api/v1/usuarios/{$otro->id}/banear", ['banned_reason' => str_repeat('a', 300)])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['banned_reason']]);
    }

    public function test_dar_admin_es_solo_de_admin_con_token_total(): void
    {
        $admin = $this->usuarioApi(admin: true);
        $otro = $this->usuarioApi();
        $conPermisos = $this->usuarioApi(['usuarios.aprobar', 'usuarios.editar']);

        // Sin ser admin, aunque tenga permisos de usuarios.
        $this->conToken($conPermisos)->postJson("/api/v1/usuarios/{$otro->id}/admin")->assertForbidden();

        // Admin, pero con una llave recortada.
        $this->conToken($admin, ['usuarios.aprobar'], TokenApi::TIPO_INTEGRACION)
            ->postJson("/api/v1/usuarios/{$otro->id}/admin")->assertForbidden();
        $this->assertFalse($otro->fresh()->is_admin);

        // Admin con acceso total: sí.
        $this->conToken($admin)->postJson("/api/v1/usuarios/{$otro->id}/admin")
            ->assertOk()->assertJsonPath('data.is_admin', true);

        // Pero no a sí mismo.
        $this->conToken($admin)->postJson("/api/v1/usuarios/{$admin->id}/admin")
            ->assertForbidden()->assertJsonPath('message', 'No puedes cambiar tu propio rol de administrador.');
        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_un_token_recortado_no_ve_usuarios_aunque_el_dueno_pueda(): void
    {
        $user = $this->usuarioApi(['usuarios.ver', 'clientes.ver']);

        $this->conToken($user, ['clientes.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/usuarios')->assertForbidden();

        $this->conToken($user, ['usuarios.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/usuarios')->assertOk();
    }

    public function test_editar_rh_solo_cambia_lo_que_llega(): void
    {
        $editor = $this->usuarioApi(['usuarios.editar']);
        $otro = $this->usuarioApi(atributos: ['phone' => '5550001111', 'position' => 'Chofer']);

        $this->conToken($editor)->patchJson("/api/v1/usuarios/{$otro->id}", ['phone' => '3312345678', 'fecha_ingreso' => '2026-01-15'])
            ->assertOk()
            ->assertJsonPath('data.phone', '3312345678')
            ->assertJsonPath('data.position', 'Chofer')
            ->assertJsonPath('data.fecha_ingreso', '2026-01-15');

        $this->conToken($editor)->patchJson("/api/v1/usuarios/{$otro->id}", ['curp' => str_repeat('X', 30)])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['curp']]);
    }

    public function test_el_rol_admin_solo_lo_da_un_admin(): void
    {
        $editor = $this->usuarioApi(['usuarios.editar']);
        $otro = $this->usuarioApi();
        $rolAdmin = Role::where('name', 'admin')->firstOrFail();
        $supervisor = Role::where('name', 'supervisor')->firstOrFail();

        // Quien solo edita usuarios no puede hacer admin a nadie (ni a sí mismo).
        $this->conToken($editor)->patchJson("/api/v1/usuarios/{$otro->id}", ['roles' => [$rolAdmin->id]])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['roles']]);
        $this->conToken($editor)->patchJson("/api/v1/usuarios/{$editor->id}", ['roles' => [$rolAdmin->id]])
            ->assertStatus(422);
        $this->assertFalse($otro->fresh()->isAdmin());
        $this->assertFalse($editor->fresh()->isAdmin());

        // Otros roles sí.
        $this->conToken($editor)->patchJson("/api/v1/usuarios/{$otro->id}", ['roles' => [$supervisor->id]])
            ->assertOk()->assertJsonPath('data.roles.0.name', 'supervisor');

        // Un admin con acceso total sí puede.
        $admin = $this->usuarioApi(admin: true);
        $this->conToken($admin)->patchJson("/api/v1/usuarios/{$otro->id}", ['roles' => [$supervisor->id, $rolAdmin->id]])
            ->assertOk();
        $this->assertTrue($otro->fresh()->isAdmin());
    }

    public function test_subir_documentos_del_expediente(): void
    {
        Storage::fake('public');
        $editor = $this->usuarioApi(['usuarios.editar']);
        $otro = $this->usuarioApi();

        $res = $this->conToken($editor)->post("/api/v1/usuarios/{$otro->id}/documentos", [
            'ine_file' => UploadedFile::fake()->create('ine.pdf', 100, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('data.0.name', 'INE');

        $doc = EmployeeDocument::where('user_id', $otro->id)->firstOrFail();
        Storage::disk('public')->assertExists($doc->file_path);
        $this->assertSame(Storage::disk('public')->url($doc->file_path), $res->json('data.0.file_url'));

        $this->conToken($editor)->postJson("/api/v1/usuarios/{$otro->id}/documentos", [])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['archivo']]);
    }

    public function test_permisos_directos_se_filtran_contra_el_catalogo(): void
    {
        $this->artisan('permisos:sincronizar');
        $editor = $this->usuarioApi(['usuarios.editar']);
        $otro = $this->usuarioApi();

        $this->conToken($editor)->putJson("/api/v1/usuarios/{$otro->id}/permisos", [
            'permisos' => ['clientes.ver', 'inventado.borrar_todo', 'ventas.ver'],
        ])
            ->assertOk()
            ->assertJsonPath('data.directos', ['clientes.ver', 'ventas.ver']);

        $this->assertDatabaseMissing('permissions', ['name' => 'inventado.borrar_todo']);
        $this->assertTrue($otro->fresh()->can('clientes.ver'));

        $this->conToken($editor)->getJson("/api/v1/usuarios/{$otro->id}/permisos")
            ->assertOk()
            ->assertJsonPath('data.efectivos', ['clientes.ver', 'ventas.ver'])
            ->assertJsonStructure(['data' => ['grupos' => [['clave', 'titulo', 'permisos']]]]);

        $this->conToken($editor)->putJson("/api/v1/usuarios/{$otro->id}/permisos", ['permisos' => []])
            ->assertOk()->assertJsonPath('data.directos', []);
    }

    public function test_conexiones_del_usuario(): void
    {
        $quien = $this->usuarioApi(['usuarios.ver']);
        $otro = $this->usuarioApi();
        $otro->loginLogs()->create(['ip_address' => '10.0.0.1', 'browser' => 'Chrome', 'platform' => 'Windows', 'logged_at' => now()]);

        $this->conToken($quien)->getJson("/api/v1/usuarios/{$otro->id}/conexiones")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ip_address', '10.0.0.1');
    }
}
