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

    /**
     * El alta pasó de un modal dentro del listado a una página propia
     * (mismo patrón que el resto del sistema): esto cubre que la página
     * cargue y que el alta siga funcionando desde ahí.
     */
    public function test_pagina_de_crear_carga_y_el_alta_sigue_funcionando(): void
    {
        $user = $this->usuarioAprobado();

        $this->actingAs($user)->get(route('admin.vehicles.create'))->assertOk();

        $response = $this->actingAs($user)->post(route('admin.vehicles.store'), [
            'plate_number' => 'NEW-001',
        ]);

        $response->assertRedirect(route('admin.vehicles.index'));
        $this->assertDatabaseHas('vehicles', ['plate_number' => 'NEW-001']);
    }
}
