<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CotizacionProductoSearchTest extends TestCase
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

    public function test_busqueda_de_productos_responde_con_productos_y_paquetes(): void
    {
        $user = $this->usuarioAprobado();

        $tipo = EquipmentType::create(['name' => 'Ultrasonido '.uniqid()]);
        $producto = Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'marca' => 'Marca Visible',
            'modelo' => 'Modelo Visible',
            'precio' => 1200,
            'stock' => 2,
        ]);

        $paquete = Paquete::create([
            'nombre' => 'Paquete Visible',
            'activo' => true,
        ]);
        $paquete->productos()->sync([$producto->id => ['cantidad' => 1]]);

        $response = $this->actingAs($user)
            ->getJson(route('commercial.cotizaciones.productos.buscar', ['q' => 'Visible']));

        $response->assertOk();
        $response->assertJsonFragment([
            'tipo_item' => 'producto',
            'modelo' => 'Modelo Visible',
        ]);
        $response->assertJsonFragment([
            'tipo_item' => 'paquete',
            'nombre' => 'Paquete Visible',
        ]);
    }

    public function test_guardar_cotizacion_sincroniza_cliente_id_heredado_si_existe(): void
    {
        $user = $this->usuarioAprobado();
        $customer = Customer::create([
            'nombre' => 'Juan',
            'apellido' => 'Garcia',
            'gmail' => 'juan@example.com',
        ]);

        $tipo = EquipmentType::create(['name' => 'Laser '.uniqid()]);
        $producto = Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'marca' => 'Marca Guardado',
            'modelo' => 'Modelo Guardado',
            'precio' => 12000,
            'stock' => 1,
        ]);

        if (! Schema::hasColumn('cotizaciones', 'cliente_id')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->unsignedBigInteger('cliente_id')->nullable();
            });
        }

        $response = $this->actingAs($user)->post(route('commercial.cotizaciones.store'), [
            'customer_id' => $customer->id,
            'modalidad' => 'contado',
            'garantia_meses' => 6,
            'items' => [[
                'tipo_item' => 'producto',
                'producto_id' => $producto->id,
                'nombre' => 'Producto Guardado',
                'modelo' => 'Modelo Guardado',
                'marca' => 'Marca Guardado',
                'cantidad' => 1,
                'precio_unitario' => 12000,
                'sobreprecio' => 0,
                'es_regalo' => false,
            ]],
        ]);

        $cotizacion = Cotizacion::firstOrFail();

        $response->assertRedirect(route('commercial.cotizaciones.show', $cotizacion));
        $this->assertSame($customer->id, $cotizacion->customer_id);
        $this->assertSame($customer->id, $cotizacion->cliente_id);
    }
}
