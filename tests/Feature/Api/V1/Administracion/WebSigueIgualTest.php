<?php

namespace Tests\Feature\Api\V1\Administracion;

use App\Mail\AccountStatusMail;
use App\Models\AgendaEvent;
use App\Models\MaterialRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Las pantallas de Administración ahora llaman a los mismos servicios que
 * la API. Esto cuida que en la web todo siga respondiendo igual: mismos
 * mensajes, mismas redirecciones, mismos correos.
 */
class WebSigueIgualTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    public function test_usuarios_aprobar_banear_y_admin_desde_la_web(): void
    {
        Mail::fake();
        $admin = $this->usuarioApi(admin: true);
        $nuevo = $this->usuarioApi();
        $nuevo->forceFill(['status' => User::STATUS_PENDING])->save();

        $this->actingAs($admin)->from('/admin/usuarios')->post(route('admin.users.approve', $nuevo))
            ->assertRedirect('/admin/usuarios')
            ->assertSessionHas('status', "Aprobaste el acceso de {$nuevo->name}. Se le notificó por correo.");
        Mail::assertSent(AccountStatusMail::class, fn ($m) => $m->type === 'approved');

        $this->actingAs($admin)->post(route('admin.users.ban', $admin))
            ->assertSessionHas('status', 'No puedes desactivar tu propia cuenta.');

        $this->actingAs($admin)->post(route('admin.users.ban', $nuevo), ['banned_reason' => 'Baja'])
            ->assertSessionHas('status', "Desactivaste la cuenta de {$nuevo->name}. Se le notificó por correo.");
        $this->assertTrue($nuevo->fresh()->isBanned());

        $this->actingAs($admin)->post(route('admin.users.toggleAdmin', $admin))
            ->assertSessionHas('status', 'No puedes cambiar tu propio rol de administrador.');
    }

    public function test_perfil_rh_desde_la_web_limpia_lo_que_no_viene(): void
    {
        $admin = $this->usuarioApi(admin: true);
        $otro = $this->usuarioApi(atributos: ['phone' => '555', 'position' => 'Chofer']);
        $supervisor = Role::where('name', 'supervisor')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.users.hrProfile.update'), [
            'user_id' => $otro->id, 'position' => 'Almacenista', 'roles' => [$supervisor->id],
        ])->assertSessionHas('status', "Datos de RH actualizados para {$otro->name}.");

        $otro->refresh();
        $this->assertSame('Almacenista', $otro->position);
        $this->assertNull($otro->phone);
        $this->assertSame(0, $otro->vacaciones_disponibles);
        $this->assertTrue($otro->hasRole('supervisor'));
    }

    public function test_roles_desde_la_web(): void
    {
        $this->artisan('permisos:sincronizar');
        $user = $this->usuarioApi(['roles.gestionar']);

        $this->actingAs($user)->post(route('configuracion.roles.store'), ['name' => 'bodega', 'label' => 'Bodega'])
            ->assertSessionHas('status', 'Rol «Bodega» creado. Ahora elige qué puede hacer.');
        $rol = Role::where('name', 'bodega')->firstOrFail();

        $this->actingAs($user)->put(route('configuracion.roles.update', $rol), [
            'label' => 'Bodega', 'permisos' => ['inventario.ver', 'no.existe'],
        ])->assertRedirect(route('configuracion.roles.index'))
            ->assertSessionHas('status', 'Se guardó lo que puede hacer «Bodega»: 1 permiso(s).');

        $admin = Role::where('name', 'admin')->firstOrFail();
        $this->actingAs($user)->put(route('configuracion.roles.update', $admin), ['label' => 'X'])->assertForbidden();
    }

    public function test_agenda_materiales_reportes_y_actividad_desde_la_web(): void
    {
        $admin = $this->usuarioApi(admin: true);

        $this->actingAs($admin)->post(route('admin.agenda.store'), [
            'title' => 'Junta', 'type' => 'meeting', 'start_date' => '2026-10-20', 'end_date' => '2026-10-20',
            'time' => '10:00', 'visibility' => 'publico',
        ])->assertRedirect(route('admin.agenda.index'))
            ->assertSessionHas('status', 'Cita registrada para el 20/10/2026.');
        $this->assertSame(1, AgendaEvent::count());

        $this->actingAs($admin)->post(route('admin.materials.store'), [
            'category' => 'Limpieza', 'material_name' => 'Cloro', 'quantity' => 2, 'unit' => 'Litro', 'urgency' => 'Normal',
        ])->assertSessionHas('status', 'Solicitud enviada a revision.');
        $solicitud = MaterialRequest::where('material_name', 'Cloro')->firstOrFail();
        $this->assertSame('admin_materiales_form', $solicitud->metadata['source']);

        $this->actingAs($admin)->patch(route('admin.materials.review', $solicitud), ['decision' => 'reject'])
            ->assertSessionHas('status', 'Solicitud rechazada.');

        $this->actingAs($admin)->post(route('admin.reports.store'), [
            'employee_name' => 'Ana', 'area' => 'Ventas', 'type' => 'falta', 'status' => 'pendiente',
            'start_date' => '2026-10-01', 'detail' => 'Falta',
        ])->assertSessionHas('status', 'Reporte generado correctamente.');

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.actividad.index', ['desde' => '2026-10-10', 'hasta' => '2026-01-01']))->assertOk();
    }

    public function test_perfil_desde_la_web(): void
    {
        $user = $this->usuarioApi();

        $this->actingAs($user)->from('/perfil')->patch(route('profile.update'), ['name' => 'Otro', 'email' => $user->email])
            ->assertRedirect('/perfil')->assertSessionHas('status', 'Perfil actualizado.');

        $this->actingAs($user)->from('/perfil')->post(route('profile.firma.store'), ['firma' => 'mala'])
            ->assertSessionHasErrors(['firma' => 'La firma no es válida. Vuelve a trazarla e intenta de nuevo.']);

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password', 'confirm' => 'no'])
            ->assertSessionHasErrors(['confirm' => 'Debes escribir ELIMINAR para confirmar.']);
    }
}
