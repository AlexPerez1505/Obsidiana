<?php

namespace Tests\Feature\Api\V1\Administracion;

use App\Models\Role;
use App\Models\TokenApi;
use App\Support\CatalogoPermisos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class RolesYPermisosTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permisos:sincronizar');
    }

    public function test_todo_pide_roles_gestionar(): void
    {
        $sin = $this->usuarioApi(['usuarios.editar']);
        $peticion = $this->conToken($sin);

        $peticion->getJson('/api/v1/roles')->assertForbidden();
        $peticion->getJson('/api/v1/permisos')->assertForbidden();
        $peticion->postJson('/api/v1/roles', ['name' => 'x', 'label' => 'X'])->assertForbidden();
    }

    public function test_el_catalogo_es_el_del_codigo(): void
    {
        $user = $this->usuarioApi(['roles.gestionar']);

        $res = $this->conToken($user)->getJson('/api/v1/permisos')->assertOk()
            ->assertJsonPath('total', count(CatalogoPermisos::llaves()));

        $llaves = collect($res->json('data'))->flatMap(fn ($g) => array_column($g['permisos'], 'llave'))->all();
        $this->assertSame(CatalogoPermisos::llaves(), $llaves);
    }

    public function test_crear_y_editar_rol_filtra_permisos_contra_el_catalogo(): void
    {
        $user = $this->usuarioApi(['roles.gestionar']);
        $peticion = $this->conToken($user);

        $id = $peticion->postJson('/api/v1/roles', ['name' => 'jefe_almacen', 'label' => 'Jefe de almacén'])
            ->assertCreated()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.permisos', [])
            ->json('data.id');

        $peticion->putJson("/api/v1/roles/{$id}", [
            'label' => 'Jefe de almacén',
            'description' => 'Recibe y entrega',
            'permisos' => ['inventario.ver', 'salidas.preparar', 'algo.que_no_existe'],
        ])
            ->assertOk()
            ->assertJsonPath('data.permisos', ['inventario.ver', 'salidas.preparar'])
            ->assertJsonPath('message', 'Se guardó lo que puede hacer «Jefe de almacén»: 2 permiso(s).');

        $this->assertDatabaseMissing('permissions', ['name' => 'algo.que_no_existe']);

        $peticion->getJson("/api/v1/roles/{$id}")->assertOk()->assertJsonPath('data.users_count', 0);
    }

    public function test_la_clave_del_rol_se_valida(): void
    {
        $user = $this->usuarioApi(['roles.gestionar']);

        $this->conToken($user)->postJson('/api/v1/roles', ['name' => 'Jefe Almacén', 'label' => 'X'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'La clave va en minúsculas, sin espacios ni acentos (ej. jefe_almacen).');

        $this->conToken($user)->postJson('/api/v1/roles', ['name' => 'supervisor', 'label' => 'X'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Ya existe un rol con esa clave.');
    }

    public function test_el_rol_admin_es_intocable(): void
    {
        $user = $this->usuarioApi(['roles.gestionar']);
        $admin = Role::where('name', 'admin')->firstOrFail();

        $this->conToken($user)->putJson("/api/v1/roles/{$admin->id}", ['label' => 'Otro', 'permisos' => []])
            ->assertForbidden()->assertJsonPath('message', 'El administrador siempre puede todo.');

        $this->conToken($user)->deleteJson("/api/v1/roles/{$admin->id}")
            ->assertForbidden()->assertJsonPath('message', 'El administrador no se puede eliminar.');

        $this->assertDatabaseHas('roles', ['id' => $admin->id, 'label' => 'Administrador']);
    }

    public function test_no_se_borra_un_rol_que_alguien_tiene(): void
    {
        $user = $this->usuarioApi(['roles.gestionar']);
        $rol = Role::create(['name' => 'temporal', 'label' => 'Temporal', 'is_active' => true]);
        $alguien = $this->usuarioApi();
        $alguien->roles()->attach($rol);

        $this->conToken($user)->deleteJson("/api/v1/roles/{$rol->id}")
            ->assertStatus(409)->assertJsonPath('codigo', 'conflicto');

        $alguien->roles()->detach();

        $this->conToken($user)->deleteJson("/api/v1/roles/{$rol->id}")
            ->assertOk()->assertJsonPath('message', 'Rol «Temporal» eliminado.');
        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
    }

    public function test_desactivar_un_rol_le_quita_lo_que_otorgaba(): void
    {
        $user = $this->usuarioApi(['roles.gestionar']);
        $rol = Role::create(['name' => 'ventas', 'label' => 'Ventas', 'is_active' => true]);
        $vendedor = $this->usuarioApi();
        $vendedor->roles()->attach($rol);

        $this->conToken($user)->putJson("/api/v1/roles/{$rol->id}", ['label' => 'Ventas', 'permisos' => ['ventas.ver']])->assertOk();
        $this->assertTrue($vendedor->fresh()->can('ventas.ver'));

        $this->conToken($user)->putJson("/api/v1/roles/{$rol->id}", ['label' => 'Ventas', 'is_active' => false, 'permisos' => ['ventas.ver']])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertFalse($vendedor->fresh()->can('ventas.ver'));
    }

    public function test_llave_recortada_sin_roles_gestionar_no_entra(): void
    {
        $admin = $this->usuarioApi(admin: true);

        $this->conToken($admin, ['clientes.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/roles')->assertForbidden();

        $this->conToken($admin, ['roles.gestionar'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/roles')->assertOk();
    }
}
