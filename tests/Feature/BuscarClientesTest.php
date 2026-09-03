<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el bug reportado: el buscador de clientes en Cotización/Venta
 * dejaba de funcionar en cuanto se escribía algo, porque la consulta
 * filtraba por una columna "correo" que no existe en la tabla clientes
 * (la columna real se llama "gmail").
 */
class BuscarClientesTest extends TestCase
{
    use RefreshDatabase;

    public function test_buscar_clientes_escribiendo_algo_no_truena_y_encuentra_por_nombre(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'status' => User::STATUS_APPROVED, 'approved_at' => now()]);
        Customer::create(['nombre' => 'Roberto', 'apellido' => 'Gómez', 'gmail' => 'roberto@example.com']);
        Customer::create(['nombre' => 'Ana', 'apellido' => 'López']);

        $response = $this->actingAs($user)->get(route('commercial.cotizaciones.clientes.buscar', ['q' => 'Roberto']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['nombre' => 'Roberto Gómez', 'correo' => 'roberto@example.com']);
    }

    public function test_buscar_clientes_por_correo_gmail(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'status' => User::STATUS_APPROVED, 'approved_at' => now()]);
        Customer::create(['nombre' => 'Luis', 'apellido' => 'Martínez', 'gmail' => 'luis.martinez@gmail.com']);

        $response = $this->actingAs($user)->get(route('commercial.cotizaciones.clientes.buscar', ['q' => 'luis.martinez']));

        $response->assertOk();
        $response->assertJsonCount(1);
    }

    public function test_buscar_clientes_sin_texto_regresa_los_primeros(): void
    {
        $user = User::factory()->create(['is_admin' => true, 'status' => User::STATUS_APPROVED, 'approved_at' => now()]);
        Customer::create(['nombre' => 'Carlos', 'apellido' => 'Ruiz']);

        $response = $this->actingAs($user)->get(route('commercial.cotizaciones.clientes.buscar'));

        $response->assertOk();
        $response->assertJsonCount(1);
    }
}
