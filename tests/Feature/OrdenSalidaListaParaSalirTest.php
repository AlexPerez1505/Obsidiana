<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OrdenSalida;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Preparar y entregar son dos actos distintos.
 *
 * Quien prepara termina el checklist y deja la orden lista; la firma la hace
 * quien entregue, que puede ser otra persona y otro día. Por eso en medio
 * hay un botón para cerrar la preparación, y hasta entonces la orden no
 * aparece como "Lista para salir" ni se puede firmar.
 */
class OrdenSalidaListaParaSalirTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    private function firmaValida(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    /** Una orden con dos partidas que no requieren emplayado. */
    private function orden(User $user): OrdenSalida
    {
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);

        $venta = Venta::create([
            'folio' => 'V-OS-'.uniqid(),
            'customer_id' => $customer->id,
            'seller_id' => $user->id,
            'modalidad' => 'contado',
            'estado' => 'confirmada',
            'total_contrato' => 100,
            'created_by' => $user->id,
        ]);

        $orden = OrdenSalida::create([
            'folio' => OrdenSalida::siguienteFolio(),
            'venta_id' => $venta->id,
            'estado' => OrdenSalida::PENDIENTE,
            'created_by' => $user->id,
        ]);

        foreach (['Endoscopio', 'Monitor'] as $n => $nombre) {
            $orden->items()->create([
                'nombre' => $nombre,
                'cantidad' => 1,
                'requiere_emplayado' => false,
                'orden' => $n,
            ]);
        }

        return $orden->fresh();
    }

    private function marcarTodo(User $user, OrdenSalida $orden): void
    {
        foreach ($orden->items as $item) {
            $this->actingAs($user)->post(route('inventory.salidas.item', [$orden, $item]), ['preparado' => 1]);
        }
    }

    public function test_terminar_el_checklist_no_la_deja_lista_sola(): void
    {
        $user = $this->usuario();
        $orden = $this->orden($user);

        $this->marcarTodo($user, $orden);
        $orden->refresh();

        // El checklist está completo...
        $this->assertTrue($orden->todoListo());
        // ...pero sigue en preparación hasta que alguien la cierre.
        $this->assertSame(OrdenSalida::EN_PREPARACION, $orden->estado);
        $this->assertTrue($orden->puedeConfirmarPreparacion());
        $this->assertFalse($orden->listaParaFirmar());
        $this->assertNull($orden->preparada_en);
    }

    public function test_el_boton_aparece_cuando_se_cumplen_las_dos_condiciones(): void
    {
        $user = $this->usuario();
        $orden = $this->orden($user);

        // Con el checklist a medias no hay botón.
        $this->actingAs($user)->get(route('inventory.salidas.show', $orden))
            ->assertDontSee('Dejar lista para salir');

        $this->marcarTodo($user, $orden);

        $this->actingAs($user)->get(route('inventory.salidas.show', $orden->fresh()))
            ->assertSee('Dejar lista para salir');
    }

    public function test_dejarla_lista_registra_quien_la_preparo_y_la_pone_en_esa_lista(): void
    {
        $user = $this->usuario();
        $orden = $this->orden($user);

        $this->marcarTodo($user, $orden);

        $this->actingAs($user)->post(route('inventory.salidas.preparada', $orden->fresh()))
            ->assertSessionHasNoErrors();

        $orden->refresh();

        $this->assertSame(OrdenSalida::LISTA, $orden->estado);
        $this->assertTrue($orden->listaParaFirmar());
        $this->assertNotNull($orden->preparada_en);
        $this->assertSame($user->id, $orden->preparada_por);

        // Y aparece en la lista de "Listas para salir".
        $this->actingAs($user)->get(route('inventory.salidas.index', ['estado' => OrdenSalida::LISTA]))
            ->assertSee($orden->folio);
    }

    public function test_no_se_puede_dejar_lista_con_el_checklist_incompleto(): void
    {
        $user = $this->usuario();
        $orden = $this->orden($user);

        // Solo una de las dos partidas.
        $this->actingAs($user)->post(
            route('inventory.salidas.item', [$orden, $orden->items->first()]),
            ['preparado' => 1]
        );

        $this->actingAs($user)
            ->from(route('inventory.salidas.show', $orden))
            ->post(route('inventory.salidas.preparada', $orden->fresh()))
            ->assertSessionHasErrors('orden');

        $this->assertNull($orden->fresh()->preparada_en);
    }

    public function test_no_se_puede_firmar_sin_dejarla_lista_primero(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();
        $orden = $this->orden($user);

        $this->marcarTodo($user, $orden);

        $this->actingAs($user)
            ->from(route('inventory.salidas.show', $orden))
            ->post(route('inventory.salidas.entregar', $orden->fresh()), [
                'recibe_nombre' => 'Chofer',
                'firma_entrega' => $this->firmaValida(),
                'firma_recibe' => $this->firmaValida(),
            ])
            ->assertSessionHasErrors('orden');

        $this->assertNotSame(OrdenSalida::ENTREGADA, $orden->fresh()->estado);
    }

    public function test_una_vez_lista_ya_se_puede_firmar(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();
        $orden = $this->orden($user);

        $this->marcarTodo($user, $orden);
        $this->actingAs($user)->post(route('inventory.salidas.preparada', $orden->fresh()));

        $this->actingAs($user)->post(route('inventory.salidas.entregar', $orden->fresh()), [
            'recibe_nombre' => 'Chofer',
            'firma_entrega' => $this->firmaValida(),
            'firma_recibe' => $this->firmaValida(),
        ])->assertRedirect(route('inventory.salidas.show', $orden));

        $this->assertSame(OrdenSalida::ENTREGADA, $orden->fresh()->estado);
    }

    public function test_desmarcar_una_partida_anula_la_preparacion_cerrada(): void
    {
        $user = $this->usuario();
        $orden = $this->orden($user);

        $this->marcarTodo($user, $orden);
        $this->actingAs($user)->post(route('inventory.salidas.preparada', $orden->fresh()));

        $this->assertSame(OrdenSalida::LISTA, $orden->fresh()->estado);

        // Se desmarca una partida: la orden regresa a la fila de trabajo.
        $this->actingAs($user)->post(
            route('inventory.salidas.item', [$orden, $orden->items->first()]),
            ['preparado' => 0]
        );

        $orden->refresh();

        $this->assertSame(OrdenSalida::EN_PREPARACION, $orden->estado);
        $this->assertNull($orden->preparada_en, 'Si se rompe el checklist, la preparación cerrada deja de valer.');
        $this->assertFalse($orden->listaParaFirmar());
    }
}
