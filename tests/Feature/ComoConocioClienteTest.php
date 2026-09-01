<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Congress;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre lo pedido: si el cliente no se conoció en un congreso, se puede
 * escribir a mano cómo se le conoció; si sí fue en un congreso, ese texto
 * no aplica (el congreso ya responde la pregunta). El dato se debe poder
 * leer luego desde Cotización, ya relleno solo.
 */
class ComoConocioClienteTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioAprobado(): User
    {
        return User::factory()->create(['is_admin' => true, 'status' => User::STATUS_APPROVED, 'approved_at' => now()]);
    }

    public function test_guarda_como_conocio_cuando_no_hay_congreso(): void
    {
        $user = $this->usuarioAprobado();

        $this->actingAs($user)->post(route('commercial.clientes.store'), [
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'telefono' => '5551234567',
            'direccion' => 'Calle 123',
            'como_conocio' => 'Recomendación de un colega',
        ])->assertRedirect(route('commercial.clientes.index'));

        $cliente = Customer::where('telefono', '5551234567')->first();

        $this->assertSame('Recomendación de un colega', $cliente->como_conocio);
        $this->assertSame('Recomendación de un colega', $cliente->comoConocio());
    }

    public function test_ignora_como_conocio_cuando_si_hay_congreso(): void
    {
        $user = $this->usuarioAprobado();
        $categoria = Category::create(['nombre' => 'Categoria Test']);
        $congreso = Congress::create([
            'nombre' => 'Expo Médica 2026',
            'categoria_id' => $categoria->id,
            'fecha_inicio' => now(),
            'fecha_finalizacion' => now()->addDays(2),
        ]);

        $this->actingAs($user)->post(route('commercial.clientes.store'), [
            'nombre' => 'Ana',
            'apellido' => 'García',
            'telefono' => '5559876543',
            'direccion' => 'Calle 456',
            'congreso_id' => $congreso->id,
            'como_conocio' => 'Esto no debería guardarse',
        ]);

        $cliente = Customer::where('telefono', '5559876543')->first();

        $this->assertNull($cliente->como_conocio);
        $this->assertSame('Expo Médica 2026', $cliente->comoConocio());
    }

    public function test_sin_congreso_ni_texto_libre_comoconocio_regresa_null(): void
    {
        $cliente = Customer::create(['nombre' => 'Sin', 'apellido' => 'Dato']);

        $this->assertNull($cliente->comoConocio());
    }

    public function test_buscar_clientes_incluye_como_lo_conocimos(): void
    {
        $user = $this->usuarioAprobado();
        Customer::create(['nombre' => 'Pedro', 'apellido' => 'Sánchez', 'como_conocio' => 'Redes sociales']);

        $response = $this->actingAs($user)->get(route('commercial.cotizaciones.clientes.buscar', ['q' => 'Pedro']));

        $response->assertOk();
        $response->assertJsonFragment(['conocido' => 'Redes sociales']);
    }
}
