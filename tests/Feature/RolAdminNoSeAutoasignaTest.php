<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * El modal de perfil RH asigna roles. Con solo `usuarios.editar` no debe
 * poder dar el rol de administrador (ni dárselo a sí mismo): eso es
 * exclusivo de un administrador.
 */
class RolAdminNoSeAutoasignaTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function rolAdmin(): Role
    {
        return Role::firstOrCreate(['name' => User::ROL_ADMIN], ['label' => 'Administrador', 'is_active' => true]);
    }

    public function test_quien_solo_edita_usuarios_no_se_hace_admin(): void
    {
        $editor = $this->usuarioApi(['usuarios.editar']);

        $this->actingAs($editor)
            ->post('/admin/usuarios/perfil-rh', [
                'user_id' => $editor->id,
                'position' => 'Ventas',
                'roles' => [$this->rolAdmin()->id],
            ])
            ->assertSessionHasErrors('roles');

        $this->assertFalse($editor->fresh()->isAdmin());
        // Todo o nada: tampoco se guardó el resto del formulario.
        $this->assertNull($editor->fresh()->position);
    }

    public function test_quien_solo_edita_usuarios_no_hace_admin_a_otro(): void
    {
        $editor = $this->usuarioApi(['usuarios.editar']);
        $otro = $this->usuarioApi();

        $this->actingAs($editor)
            ->post('/admin/usuarios/perfil-rh', ['user_id' => $otro->id, 'roles' => [$this->rolAdmin()->id]])
            ->assertSessionHasErrors('roles');

        $this->assertFalse($otro->fresh()->isAdmin());
    }

    public function test_un_admin_si_puede_dar_el_rol_y_editar_lo_demas(): void
    {
        $admin = $this->usuarioApi(admin: true);
        $otro = $this->usuarioApi();
        $vendedor = Role::firstOrCreate(['name' => 'ventas_prueba'], ['label' => 'Ventas', 'is_active' => true]);

        $this->actingAs($admin)
            ->post('/admin/usuarios/perfil-rh', ['user_id' => $otro->id, 'position' => 'Gerente', 'roles' => [$this->rolAdmin()->id, $vendedor->id]])
            ->assertSessionHasNoErrors();

        $this->assertTrue($otro->fresh()->isAdmin());
        $this->assertSame('Gerente', $otro->fresh()->position);

        // Un editor sí puede mover roles comunes de alguien sin tocar el de admin.
        $editor = $this->usuarioApi(['usuarios.editar']);
        $tercero = $this->usuarioApi();

        $this->actingAs($editor)
            ->post('/admin/usuarios/perfil-rh', ['user_id' => $tercero->id, 'roles' => [$vendedor->id]])
            ->assertSessionHasNoErrors();

        $this->assertTrue($tercero->fresh()->hasRole('ventas_prueba'));
    }
}
