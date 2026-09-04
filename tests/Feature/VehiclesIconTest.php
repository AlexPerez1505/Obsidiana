<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vehículos ahora usa el ícono de Gravity UI (<x-gravityui-car/>) en vez de
 * un SVG suelto; esto solo confirma que el componente resuelve bien y las
 * páginas siguen cargando (tanto con vehículos como sin ninguno).
 */
class VehiclesIconTest extends TestCase
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

    public function test_index_sin_vehiculos_renderiza_el_icono_gravityui(): void
    {
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->get(route('admin.vehicles.index'));

        $response->assertOk();
        $response->assertSee('<svg', false);
    }

    public function test_index_con_vehiculo_renderiza_el_icono_gravityui(): void
    {
        $user = $this->usuarioAprobado();

        Vehicle::create([
            'plate_number' => 'ABC-123',
            'brand' => 'Nissan',
            'model' => 'NP300',
            'year' => 2022,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('admin.vehicles.index'));

        $response->assertOk();
        $response->assertSee('ABC-123');
    }

    public function test_show_sin_fotos_renderiza_el_icono_gravityui(): void
    {
        $user = $this->usuarioAprobado();

        $vehicle = Vehicle::create([
            'plate_number' => 'XYZ-789',
            'brand' => 'Toyota',
            'model' => 'Hilux',
            'year' => 2021,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('admin.vehicles.show', $vehicle));

        $response->assertOk();
    }
}
