<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Congress;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Congresos ya no vive enterrado en Configuración > Catálogos: tiene su
 * propia pantalla en Gestión de Inventario, con calendario, productos que
 * se llevaron (por unidad, no solo el conteo) y quién asistió.
 */
class CongresosInventarioTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioAprobado(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    private function congreso(): Congress
    {
        $categoria = Category::create(['nombre' => 'Endoscopia']);

        return Congress::create([
            'nombre' => 'Congreso de Prueba',
            'categoria_id' => $categoria->id,
            'fecha_inicio' => now()->addDays(2)->toDateString(),
            'fecha_finalizacion' => now()->addDays(4)->toDateString(),
            'hora_montaje' => '08:00',
            'hora_desmontaje' => '18:00',
            'direccion' => 'Centro de Convenciones',
        ]);
    }

    public function test_el_indice_carga_sin_congresos(): void
    {
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->get(route('inventory.congresos.index'));

        $response->assertOk();
        $response->assertSee('Aún no hay congresos');
    }

    public function test_el_indice_muestra_el_congreso_seleccionado(): void
    {
        $user = $this->usuarioAprobado();
        $congress = $this->congreso();

        $response = $this->actingAs($user)->get(route('inventory.congresos.index', ['congreso' => $congress->id]));

        $response->assertOk();
        $response->assertSee('Congreso de Prueba');
        $response->assertSee('Centro de Convenciones');
    }

    public function test_crear_congreso_desde_inventario(): void
    {
        $user = $this->usuarioAprobado();
        $categoria = Category::create(['nombre' => 'Endoscopia']);

        $response = $this->actingAs($user)->post(route('inventory.congresos.store'), [
            'nombre' => 'Congreso Nuevo',
            'categoria_id' => $categoria->id,
            'fecha_inicio' => now()->addDay()->toDateString(),
            'fecha_finalizacion' => now()->addDays(2)->toDateString(),
            'hora_montaje' => '08:00',
            'hora_desmontaje' => '18:00',
        ]);

        $congress = Congress::where('nombre', 'Congreso Nuevo')->firstOrFail();
        $response->assertRedirect(route('inventory.congresos.index', ['congreso' => $congress->id]));
    }

    public function test_agregar_producto_marca_las_unidades_disponibles_sin_afectar_su_estado(): void
    {
        $user = $this->usuarioAprobado();
        $congress = $this->congreso();

        $producto = Producto::create([
            'tipo_equipo' => 'Endoscopio',
            'marca' => 'Olympus',
            'modelo' => 'X1',
            'stock' => 0,
        ]);
        $producto->agregarUnidades(3, ['SN-1', 'SN-2', 'SN-3']);
        $elegidas = $producto->seriales()->take(2)->pluck('id');

        $this->actingAs($user)->post(route('inventory.congresos.productos.store', $congress), [
            'serial_ids' => $elegidas->all(),
        ]);

        $enviadas = ProductoSerial::where('producto_id', $producto->id)
            ->where('congress_id', $congress->id)
            ->get();

        $this->assertCount(2, $enviadas);
        // Sigue vendible: llevarlo al congreso no lo bloquea ni le cambia el estado.
        $this->assertTrue($enviadas->first()->vendible());
        $this->assertSame('disponible', $enviadas->first()->estado);

        $resumen = $congress->fresh()->productosResumen();
        $this->assertSame(2, $resumen->first()['cantidad']);
    }

    public function test_quitar_una_unidad_del_congreso_la_regresa(): void
    {
        $user = $this->usuarioAprobado();
        $congress = $this->congreso();

        $producto = Producto::create([
            'tipo_equipo' => 'Endoscopio',
            'marca' => 'Olympus',
            'modelo' => 'X1',
            'stock' => 0,
        ]);
        $producto->agregarUnidades(1, ['SN-9']);
        $serial = $producto->seriales()->first();
        $serial->update(['congress_id' => $congress->id]);

        $this->actingAs($user)->delete(route('inventory.congresos.productos.destroy', [
            'congress' => $congress,
            'serial' => $serial,
        ]));

        $this->assertNull($serial->fresh()->congress_id);
    }

    public function test_agregar_y_quitar_participante(): void
    {
        $user = $this->usuarioAprobado();
        $congress = $this->congreso();

        $this->actingAs($user)->post(route('inventory.congresos.participantes.store', $congress), [
            'nombre' => 'Dr. Carlos Méndez',
            'rol' => 'Ponente',
            'empresa' => 'Hospital Ángeles',
        ]);

        $participante = $congress->participantes()->firstOrFail();
        $this->assertSame('Dr. Carlos Méndez', $participante->nombre);

        $this->actingAs($user)->delete(route('inventory.congresos.participantes.destroy', [
            'congress' => $congress,
            'participante' => $participante,
        ]));

        $this->assertSame(0, $congress->participantes()->count());
    }

    public function test_ya_no_existen_las_rutas_viejas_de_configuracion(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('configuracion.congresos.create'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('configuracion.congresos.show'));
    }

    public function test_unidades_disponibles_incluye_la_foto_para_elegir_a_mano(): void
    {
        $user = $this->usuarioAprobado();

        $producto = Producto::create([
            'tipo_equipo' => 'Endoscopio',
            'marca' => 'Olympus',
            'modelo' => 'X1',
            'stock' => 0,
        ]);
        $producto->agregarUnidades(1, ['SN-7']);

        $response = $this->actingAs($user)->getJson(route('inventory.congresos.unidadesDisponibles', ['producto_id' => $producto->id]));

        $response->assertOk();
        $response->assertJsonCount(1, 'unidades');
        $response->assertJsonStructure(['unidades' => [['id', 'codigo', 'no_serie', 'foto']]]);
    }

    public function test_unidades_disponibles_no_incluye_las_que_ya_estan_en_un_congreso(): void
    {
        $user = $this->usuarioAprobado();
        $congress = $this->congreso();

        $producto = Producto::create([
            'tipo_equipo' => 'Endoscopio',
            'marca' => 'Olympus',
            'modelo' => 'X1',
            'stock' => 0,
        ]);
        $producto->agregarUnidades(1, ['SN-8']);
        $producto->seriales()->first()->update(['congress_id' => $congress->id]);

        $response = $this->actingAs($user)->getJson(route('inventory.congresos.unidadesDisponibles', ['producto_id' => $producto->id]));

        $response->assertJsonCount(0, 'unidades');
    }

    public function test_agregar_y_quitar_usuario_del_sistema_en_el_congreso(): void
    {
        $admin = $this->usuarioAprobado();
        $congress = $this->congreso();
        $vendedor = User::factory()->create(['status' => User::STATUS_APPROVED, 'approved_at' => now()]);

        $this->actingAs($admin)->post(route('inventory.congresos.usuarios.store', $congress), [
            'user_id' => $vendedor->id,
        ]);

        $this->assertTrue($congress->notifiedUsers()->where('users.id', $vendedor->id)->exists());

        $this->actingAs($admin)->delete(route('inventory.congresos.usuarios.destroy', [
            'congress' => $congress,
            'user' => $vendedor,
        ]));

        $this->assertFalse($congress->notifiedUsers()->where('users.id', $vendedor->id)->exists());
    }
}
