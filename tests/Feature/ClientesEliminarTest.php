<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class ClientesEliminarTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private function usuario(array $permisos = [], bool $admin = false): User
    {
        $user = $this->usuarioApi($permisos, admin: $admin);
        $user->setAttribute('ficha_completada', true);
        return $user;
    }

    private function cliente(User $user): Customer
    {
        return Customer::create(['nombre' => 'PRUEBA '.Str::uuid(), 'apellido' => 'CLIENTE', 'asesor_id' => $user->id, 'activo' => true]);
    }

    public function test_administrador_desactiva_y_reactiva_sin_perder_seguimientos(): void
    {
        $user = $this->usuario(admin: true);
        $cliente = $this->cliente($user);
        $seguimiento = $cliente->seguimientos()->create(['user_id' => $user->id, 'tipo' => 'llamada', 'fecha' => now()->addDay()]);
        $url = route('commercial.clientes.toggleActivo', $cliente);
        $this->actingAs($user)->patch($url)->assertRedirect();
        $this->assertFalse($cliente->fresh()->activo);
        $this->assertDatabaseHas('cliente_seguimientos', ['id' => $seguimiento->id]);
        $this->patch($url)->assertRedirect();
        $this->assertTrue($cliente->fresh()->activo);
    }

    public function test_eliminar_exige_permiso_distinto_de_editar(): void
    {
        $user = $this->usuario(['clientes.ver', 'clientes.editar']);
        $cliente = $this->cliente($user);
        $this->actingAs($user)->delete(route('commercial.clientes.destroy', $cliente))->assertForbidden();
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
        $permiso = \App\Models\Permission::firstOrCreate(['name' => 'clientes.eliminar'], ['label' => 'Eliminar clientes']);
        $user->permissions()->syncWithoutDetaching([$permiso->id]);
        $user->olvidarPermisos();
        $this->actingAs($user)->delete(route('commercial.clientes.destroy', $cliente))->assertRedirect(route('commercial.clientes.index'));
        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_no_permite_actuar_sobre_clientes_de_otra_cuenta(): void
    {
        $user = $this->usuario(['clientes.ver', 'clientes.editar', 'clientes.eliminar']);
        $otro = $this->usuario(admin: true);
        $cliente = $this->cliente($otro);
        $this->actingAs($user)->patch(route('commercial.clientes.toggleActivo', $cliente))->assertForbidden();
        $this->delete(route('commercial.clientes.destroy', $cliente))->assertForbidden();
        $this->assertTrue($cliente->fresh()->activo);
    }

    public function test_impide_eliminar_con_cualquiera_de_los_seis_tipos_de_historial(): void
    {
        $user = $this->usuario(admin: true);
        $this->actingAs($user);
        foreach (['ventas', 'cotizaciones', 'servicios', 'facturas', 'planes de pago', 'pagos'] as $tipo) {
            $cliente = $this->cliente($user);
            $datos = ['customer_id' => $cliente->id, 'folio' => 'PRUEBA-'.Str::uuid()];
            if ($tipo === 'ventas') \App\Models\Venta::create($datos);
            if ($tipo === 'cotizaciones') \App\Models\Cotizacion::create($datos);
            if ($tipo === 'servicios') \App\Models\Service::create(['customer_id' => $cliente->id, 'service_type' => 'interno', 'registered_by' => $user->id]);
            if ($tipo === 'facturas') \App\Models\Factura::create($datos);
            if (in_array($tipo, ['planes de pago', 'pagos'], true)) {
                $cotizacion = \App\Models\Cotizacion::create($datos);
                $plan = \App\Models\PlanPago::create(['nombre' => 'PRUEBA', 'no_pago' => 1, 'cliente_id' => $cliente->id, 'cotizacion_id' => $cotizacion->id, 'plazo_pagar' => now()->addDay()]);
                if ($tipo === 'pagos') \App\Models\Pago::create(['plan_pago_id' => $plan->id, 'cliente_id' => $cliente->id]);
            }
            $this->delete(route('commercial.clientes.destroy', $cliente))->assertSessionHasErrors('eliminar');
            $this->assertStringContainsString($tipo, session('errors')->first('eliminar'));
            $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
            $this->patch(route('commercial.clientes.toggleActivo', $cliente))->assertRedirect();
            $this->assertFalse($cliente->fresh()->activo);
        }
    }

    public function test_eliminar_cliente_sin_historial_quita_solo_sus_seguimientos(): void
    {
        $user = $this->usuario(admin: true);
        $a = $this->cliente($user);
        $b = $this->cliente($user);
        $sa = $a->seguimientos()->create(['user_id' => $user->id, 'tipo' => 'llamada', 'fecha' => now()->addDay()]);
        $sb = $b->seguimientos()->create(['user_id' => $user->id, 'tipo' => 'llamada', 'fecha' => now()->addDay()]);
        $this->actingAs($user)->delete(route('commercial.clientes.destroy', $a))->assertRedirect();
        $this->assertDatabaseMissing('clientes', ['id' => $a->id]);
        $this->assertDatabaseMissing('cliente_seguimientos', ['id' => $sa->id]);
        $this->assertDatabaseHas('cliente_seguimientos', ['id' => $sb->id]);
        $this->assertDatabaseHas('clientes', ['id' => $b->id]);
    }

    public function test_error_al_eliminar_no_borra_previamente_los_seguimientos(): void
    {
        $user = $this->usuario(admin: true);
        $cliente = $this->cliente($user);
        $s = $cliente->seguimientos()->create(['user_id' => $user->id, 'tipo' => 'llamada', 'fecha' => now()->addDay()]);
        $dispatcher = Customer::getEventDispatcher();
        Customer::setEventDispatcher(clone $dispatcher);
        try {
            Customer::deleting(function ($modelo) use ($cliente) {
                if ($modelo->id === $cliente->id) throw new \RuntimeException('Fallo simulado');
            });
            $this->withoutExceptionHandling();
            try {
                $this->actingAs($user)->delete(route('commercial.clientes.destroy', $cliente));
                $this->fail('No se produjo el fallo simulado.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Fallo simulado', $e->getMessage());
            }
            $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
            $this->assertDatabaseHas('cliente_seguimientos', ['id' => $s->id]);
        } finally {
            Customer::setEventDispatcher($dispatcher);
            $this->withExceptionHandling();
        }
    }

    public function test_lectura_sola_no_permite_desactivar_y_deshabilita_los_botones(): void
    {
        $user = $this->usuario(['clientes.ver']);
        $cliente = $this->cliente($user);
        $this->actingAs($user)->patch(route('commercial.clientes.toggleActivo', $cliente))->assertForbidden();
        $html = $this->get(route('commercial.clientes.index'))->assertOk()->getContent();
        preg_match_all('/<button[^>]*type="submit"[^>]*role="menuitem"[^>]*>/', $html, $botones);
        $this->assertCount(4, $botones[0]);
        foreach ($botones[0] as $boton) $this->assertStringContainsString('disabled', $boton);
    }
}
