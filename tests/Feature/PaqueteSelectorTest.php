<?php

namespace Tests\Feature;

use App\Models\EquipmentType;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El selector de productos de Paquetes cambió de <select> por checklist con
 * imagen y buscador; esto cubre que las páginas sigan cargando y que crear
 * o editar un paquete siga guardando bien con los mismos nombres de campo.
 */
class PaqueteSelectorTest extends TestCase
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

    private function producto(): Producto
    {
        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia '.uniqid()]);

        return Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'marca' => 'MarcaTest',
            'modelo' => 'ModeloTest',
            'precio' => 500,
            'stock' => 0,
        ]);
    }

    public function test_crear_paquete_precarga_los_productos_seleccionados_desde_productos(): void
    {
        $user = $this->usuarioAprobado();
        $producto = $this->producto();

        $response = $this->actingAs($user)->get(route('inventory.paquetes.create', ['productos' => [$producto->id]]));

        $response->assertOk();
        $response->assertViewHas('seleccionados', [$producto->id => 1]);
        $response->assertSee($producto->tipo_equipo);
    }

    public function test_guardar_paquete_sigue_funcionando_con_los_mismos_campos(): void
    {
        $user = $this->usuarioAprobado();
        $producto = $this->producto();

        $response = $this->actingAs($user)->post(route('inventory.paquetes.store'), [
            'nombre' => 'Paquete de prueba',
            'productos' => [
                ['id' => $producto->id, 'cantidad' => 2],
            ],
        ]);

        $response->assertRedirect(route('inventory.paquetes.index'));

        $paquete = Paquete::first();
        $this->assertNotNull($paquete);
        $this->assertSame(2, $paquete->productos->firstWhere('id', $producto->id)->pivot->cantidad);
    }

    public function test_editar_paquete_precarga_las_cantidades_ya_guardadas(): void
    {
        $user = $this->usuarioAprobado();
        $producto = $this->producto();

        $paquete = Paquete::create(['nombre' => 'Paquete existente']);
        $paquete->productos()->sync([$producto->id => ['cantidad' => 3]]);

        $response = $this->actingAs($user)->get(route('inventory.paquetes.edit', $paquete));

        $response->assertOk();
        $response->assertViewHas('seleccionados', [$producto->id => 3]);
    }
}
