<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\Paquete;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Cotizaciones y ventas se eliminan sin NIP las primeras 72 horas desde
 * que se crearon; después se pide el NIP de aprobación (o la contraseña).
 */
class EliminarConNipTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private User $vendedor;

    private Customer $cliente;

    private Paquete $kit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vendedor = $this->usuarioApi(['cotizaciones.ver', 'cotizaciones.crear', 'cotizaciones.eliminar', 'ventas.ver', 'ventas.crear', 'ventas.eliminar']);
        $this->vendedor->setApprovalPin('4321');
        $this->cliente = Customer::create(['nombre' => 'Clínica', 'apellido' => 'Prueba', 'asesor_id' => $this->vendedor->id]);
        $this->kit = Paquete::create(['nombre' => 'Kit', 'precio' => 1000, 'activo' => true]);
    }

    private function datos(): array
    {
        return ['customer_id' => $this->cliente->id, 'modalidad' => 'contado', 'garantia_meses' => 6,
            'items' => [['tipo_item' => 'paquete', 'paquete_id' => $this->kit->id, 'nombre' => 'Kit', 'cantidad' => 1, 'precio_unitario' => 1000]]];
    }

    private function cotizacion(): Cotizacion
    {
        $this->actingAs($this->vendedor)->post(route('commercial.cotizaciones.store'), $this->datos())->assertSessionHasNoErrors();

        return Cotizacion::latest('id')->firstOrFail();
    }

    private function venta(): Venta
    {
        $this->actingAs($this->vendedor)->post(route('commercial.ventas.store'), $this->datos())->assertSessionHasNoErrors();

        return Venta::latest('id')->firstOrFail();
    }

    private function envejecer($doc, int $horas): void
    {
        $doc::whereKey($doc->id)->update(['created_at' => now()->subHours($horas)]);
    }

    public function test_cotizacion_reciente_se_elimina_sin_nip(): void
    {
        $cot = $this->cotizacion();
        $this->envejecer($cot, 71);

        $this->delete(route('commercial.cotizaciones.destroy', $cot))->assertSessionHasNoErrors();
        $this->assertModelMissing($cot);
    }

    public function test_cotizacion_de_mas_de_72_horas_pide_nip(): void
    {
        $cot = $this->cotizacion();
        $this->envejecer($cot, 73);

        $this->delete(route('commercial.cotizaciones.destroy', $cot))->assertSessionHasErrors('nip');
        $this->delete(route('commercial.cotizaciones.destroy', $cot), ['nip' => '0000'])->assertSessionHasErrors(['nip' => 'NIP incorrecto.']);
        $this->assertModelExists($cot);

        $this->delete(route('commercial.cotizaciones.destroy', $cot), ['nip' => '4321'])->assertSessionHasNoErrors();
        $this->assertModelMissing($cot);
    }

    public function test_venta_reciente_sin_nip_y_vieja_con_nip(): void
    {
        $nueva = $this->venta();
        $this->delete(route('commercial.ventas.destroy', $nueva))->assertSessionHasNoErrors();
        $this->assertModelMissing($nueva);

        $vieja = $this->venta();
        $this->envejecer($vieja, 80);
        $this->delete(route('commercial.ventas.destroy', $vieja))->assertSessionHasErrors('nip');
        $this->assertModelExists($vieja);
        $this->delete(route('commercial.ventas.destroy', $vieja), ['nip' => '4321'])->assertSessionHasNoErrors();
        $this->assertModelMissing($vieja);
    }

    public function test_la_api_aplica_la_misma_regla(): void
    {
        $cot = $this->cotizacion();
        $this->envejecer($cot, 100);

        $this->conToken($this->vendedor)->deleteJson("/api/v1/cotizaciones/{$cot->id}")->assertStatus(422)->assertJsonValidationErrors('nip');
        $this->conToken($this->vendedor)->deleteJson("/api/v1/cotizaciones/{$cot->id}", ['nip' => '4321'])->assertOk();
        $this->assertModelMissing($cot);
    }

    public function test_sin_nip_registrado_pide_la_contrasena(): void
    {
        $this->vendedor->forceFill(['approval_pin_hash' => null])->save();
        $cot = $this->cotizacion();
        $this->envejecer($cot, 73);

        $this->delete(route('commercial.cotizaciones.destroy', $cot), ['nip' => 'mala'])->assertSessionHasErrors(['nip' => 'Contraseña incorrecta.']);
        $this->delete(route('commercial.cotizaciones.destroy', $cot), ['nip' => 'password'])->assertSessionHasNoErrors();
        $this->assertModelMissing($cot);
    }

    public function test_la_lista_dice_cuando_pide_nip(): void
    {
        $cot = $this->cotizacion();
        $this->get(route('commercial.cotizaciones.index'))->assertOk()->assertSee('data-nip="0"', false);
        $this->envejecer($cot, 73);
        $this->get(route('commercial.cotizaciones.index'))->assertOk()->assertSee('data-nip="1"', false)->assertSee('dlgEliminarDoc', false);
    }
}
