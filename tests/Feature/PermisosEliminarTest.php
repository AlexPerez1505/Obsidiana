<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\TokenApi;
use App\Models\User;
use App\Support\CatalogoPermisos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PermisosEliminarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('permisos:sincronizar');
    }

    private function usuario(bool $admin = false): User
    {
        $user = User::factory()->create([
            'is_admin' => $admin,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
        $user->setAttribute('ficha_completada', true);

        return $user;
    }

    public function test_configuracion_asigna_y_retira_eliminar_solo_en_el_modulo_elegido(): void
    {
        $admin = $this->usuario(true);
        $destino = $this->usuario();
        $pagina = route('configuracion.usuarios.permisos', $destino);
        $guardar = route('configuracion.usuarios.permisos.update', $destino);

        $this->actingAs($admin)->get(route('configuracion.roles.index'))
            ->assertOk()->assertSee('Permisos por cuenta');
        $this->get($pagina)->assertOk()->assertSee('inventario.catalogo_eliminar')
            ->assertSee('servicios.eliminar')->assertSee('name="permisos[]"', false);
        $this->from($pagina)->post($guardar, [
            'permisos' => ['inventario.ver', 'inventario.catalogo_eliminar', 'permiso.inventado'],
            'is_admin' => true,
        ])->assertRedirect($pagina);

        $destino = $destino->fresh();
        $this->assertTrue(Gate::forUser($destino)->allows('inventario.catalogo_eliminar'));
        $this->assertFalse(Gate::forUser($destino)->allows('clientes.eliminar'));
        $this->assertFalse($destino->isAdmin());
        $this->assertSame(2, $destino->permissions()->count());
        $this->assertDatabaseMissing('permissions', ['name' => 'permiso.inventado']);

        $this->post($guardar, ['permisos' => ['inventario.ver']])->assertRedirect();
        $destino = $destino->fresh();
        $this->assertFalse(Gate::forUser($destino)->allows('inventario.catalogo_eliminar'));
        $this->assertTrue(Gate::forUser($destino)->allows('inventario.ver'));
    }

    public function test_editar_catalogo_no_permite_eliminar_hasta_recibir_el_permiso(): void
    {
        $admin = $this->usuario(true);
        $user = $this->usuario();
        $user->permissions()->attach(Permission::where('name', 'inventario.catalogo')->firstOrFail());
        $categoria = \App\Models\Category::create(['nombre' => 'PRUEBA PERMISO '.uniqid()]);

        $this->actingAs($user)->delete(route('configuracion.categorias.destroy', $categoria))->assertForbidden();
        $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);

        $this->actingAs($admin)->post(route('configuracion.usuarios.permisos.update', $user), [
            'permisos' => ['inventario.catalogo', 'inventario.catalogo_eliminar'],
        ])->assertRedirect();
        $user = $user->fresh();
        $user->setAttribute('ficha_completada', true);
        $this->actingAs($user)->delete(route('configuracion.categorias.destroy', $categoria))->assertRedirect();
        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_editar_usuarios_no_permite_concederse_permisos(): void
    {
        $user = $this->usuario();
        $user->permissions()->attach(Permission::where('name', 'usuarios.editar')->firstOrFail());
        $this->actingAs($user)->post(route('configuracion.usuarios.permisos.update', $user), [
            'permisos' => ['roles.gestionar', 'usuarios.eliminar'],
        ])->assertForbidden();
        $this->post(route('admin.users.permissions.update', $user), [
            'permisos' => ['roles.gestionar', 'usuarios.eliminar'],
        ])->assertForbidden();
        $this->assertFalse($user->fresh()->hasPermission('roles.gestionar'));
    }

    public function test_permisos_por_rol_no_se_pierden_al_guardar_los_directos(): void
    {
        $admin = $this->usuario(true);
        $user = $this->usuario();
        $rol = Role::create(['name' => 'prueba_eliminar_'.uniqid(), 'label' => 'Prueba eliminar', 'is_active' => true]);
        $rol->permissions()->attach(Permission::where('name', 'servicios.eliminar')->firstOrFail());
        $user->roles()->attach($rol);

        $this->actingAs($admin)->get(route('configuracion.usuarios.permisos', $user))
            ->assertOk()->assertSee('También concedido por rol');
        $this->post(route('configuracion.usuarios.permisos.update', $user), ['permisos' => []])->assertRedirect();
        $this->assertTrue($user->fresh()->hasPermission('servicios.eliminar'));
        $this->assertSame(0, $user->permissions()->count());
        $this->assertSame(1, $user->roles()->count());
    }

    public function test_administrador_sigue_con_acceso_total_y_token_limitado_no_lo_hereda(): void
    {
        $admin = $this->usuario(true);
        foreach (CatalogoPermisos::llaves() as $permiso) {
            $this->assertTrue(Gate::forUser($admin)->allows($permiso), $permiso);
        }
        $this->actingAs($admin)->post(route('configuracion.usuarios.permisos.update', $admin), [
            'permisos' => [],
        ])->assertForbidden();
        $this->assertTrue($admin->fresh()->isAdmin());

        $admin->withAccessToken(new TokenApi(['abilities' => ['inventario.ver']]));
        $this->assertFalse(Gate::forUser($admin)->allows('inventario.catalogo_eliminar'));
    }

    public function test_rutas_web_y_api_exigen_el_permiso_de_eliminacion_del_modulo(): void
    {
        $prefijos = [
            'configuracion.categorias.destroy' => 'inventario.catalogo_eliminar',
            'configuracion.catalogo_equipo.' => 'inventario.catalogo_eliminar',
            'configuracion.roles.destroy' => 'roles.eliminar',
            'gestion.servicios.' => 'servicios.eliminar',
            'marketing.' => 'marketing.eliminar',
            'commercial.clientes.' => 'clientes.eliminar',
            'commercial.ventas.cobros.' => 'cobranza.eliminar',
            'commercial.comisiones.' => 'comisiones.eliminar',
            'commercial.facturas.' => 'facturacion.eliminar',
            'inventory.congresos.' => 'congresos.eliminar',
            'inventory.salidas.' => 'salidas.eliminar',
            'logistica.flota.' => 'rutas.flota_eliminar',
            'asistencia.biometria.' => 'asistencia.biometria_eliminar',
            'asistencia.' => 'asistencia.eliminar',
            'nomina.' => 'asistencia.nomina_eliminar',
            'admin.agenda.' => 'agenda.eliminar',
            'admin.viatics.' => 'viaticos.eliminar',
            'admin.trips.' => 'viaticos.eliminar',
            'api.v1.clientes.' => 'clientes.eliminar',
            'api.v1.ventas.cobros.' => 'cobranza.eliminar',
            'api.v1.ventas.parcialidades.' => 'cobranza.eliminar',
            'api.v1.comisiones.' => 'comisiones.eliminar',
            'api.v1.facturas.' => 'facturacion.eliminar',
            'api.v1.congresos.' => 'congresos.eliminar',
            'api.v1.ordenes-salida.' => 'salidas.eliminar',
            'api.v1.catalogo.' => 'inventario.catalogo_eliminar',
            'api.v1.flota.' => 'rutas.flota_eliminar',
            'api.v1.rh.biometria.' => 'asistencia.biometria_eliminar',
            'api.v1.rh.nomina.' => 'asistencia.nomina_eliminar',
            'api.v1.rh.' => 'asistencia.eliminar',
            'api.v1.roles.' => 'roles.eliminar',
            'api.v1.agenda.' => 'agenda.eliminar',
            'api.v1.viaticos.' => 'viaticos.eliminar',
            'api.v1.viajes.' => 'viaticos.eliminar',
            'api.v1.marketing.' => 'marketing.eliminar',
        ];
        $verificadas = 0;
        foreach (app('router')->getRoutes() as $ruta) {
            if (!in_array('DELETE', $ruta->methods(), true)) {
                continue;
            }
            foreach ($prefijos as $prefijo => $permiso) {
                if (!str_starts_with($ruta->getName() ?? '', $prefijo)) {
                    continue;
                }
                $middleware = app('router')->gatherRouteMiddleware($ruta);
                $this->assertTrue(collect($middleware)->contains(fn ($m) => is_string($m) && str_ends_with($m, ':'.$permiso)), $ruta->getName());
                $this->assertTrue(CatalogoPermisos::existe($permiso));
                $verificadas++;
                break;
            }
        }
        $this->assertGreaterThan(50, $verificadas);
    }
}
