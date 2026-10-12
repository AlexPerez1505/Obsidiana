<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Congress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * La firma se traza una sola vez en el perfil y de ahí se carga sola en
 * las pantallas que la piden. Nadie tiene que volver a dibujarla con el
 * mouse en cada entrada o cada salida.
 */
class FirmaRegistradaTest extends TestCase
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

    /** PNG 1x1 en base64, como lo manda el lienzo. */
    private function firmaValida(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    public function test_el_usuario_registra_su_firma_desde_el_perfil(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();
        $this->assertFalse($user->tieneFirma());

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.firma.store'), ['firma' => $this->firmaValida()])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertTrue($user->tieneFirma());
        Storage::disk(config('filesystems.fotos_disk', 'public'))->assertExists($user->firma_path);
        $this->assertStringStartsWith('data:image/png;base64,', $user->firmaDataUri());
    }

    public function test_una_firma_invalida_no_se_guarda(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.firma.store'), ['firma' => 'esto-no-es-una-imagen'])
            ->assertSessionHasErrors('firma');

        $this->assertNull($user->fresh()->firma_path);
    }

    public function test_registrar_una_nueva_reemplaza_la_anterior_y_borra_el_archivo_viejo(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();

        $this->actingAs($user)->post(route('profile.firma.store'), ['firma' => $this->firmaValida()]);
        $primera = $user->fresh()->firma_path;

        $this->actingAs($user)->post(route('profile.firma.store'), ['firma' => $this->firmaValida()]);
        $segunda = $user->fresh()->firma_path;

        $this->assertNotSame($primera, $segunda);
        Storage::disk(config('filesystems.fotos_disk', 'public'))->assertMissing($primera);
        Storage::disk(config('filesystems.fotos_disk', 'public'))->assertExists($segunda);
    }

    public function test_se_puede_quitar_la_firma_registrada(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();
        $this->actingAs($user)->post(route('profile.firma.store'), ['firma' => $this->firmaValida()]);
        $path = $user->fresh()->firma_path;

        $this->actingAs($user)->delete(route('profile.firma.destroy'))->assertRedirect();

        $this->assertNull($user->fresh()->firma_path);
        Storage::disk(config('filesystems.fotos_disk', 'public'))->assertMissing($path);
    }

    public function test_la_entrada_carga_sola_la_firma_registrada(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();
        $this->actingAs($user)->post(route('profile.firma.store'), ['firma' => $this->firmaValida()]);

        $pagina = $this->actingAs($user)->get(route('inventory.movimientos.create'));

        $pagina->assertOk();
        $pagina->assertSee('data-firma-registrada', false);
        $pagina->assertSee('Se cargó tu firma registrada');
    }

    public function test_sin_firma_registrada_la_entrada_invita_a_registrarla(): void
    {
        $user = $this->usuario();

        $pagina = $this->actingAs($user)->get(route('inventory.movimientos.create'));

        $pagina->assertOk();
        $pagina->assertDontSee('data-firma-registrada', false);
        $pagina->assertSee('registrar tu firma');
    }

    public function test_en_la_salida_solo_se_precarga_la_de_quien_entrega(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();
        $this->actingAs($user)->post(route('profile.firma.store'), ['firma' => $this->firmaValida()]);

        $orden = $this->ordenListaParaFirmar($user);

        $pagina = $this->actingAs($user)->get(route('inventory.salidas.show', $orden));

        $pagina->assertOk();

        // El atributo va en el lienzo de quien entrega, no en el de quien recibe.
        $html = $pagina->getContent();
        $posEntrega = strpos($html, 'data-pad="firma_entrega"');
        $posRecibe = strpos($html, 'data-pad="firma_recibe"');

        $this->assertNotFalse($posEntrega);
        $this->assertNotFalse($posRecibe);
        $this->assertStringContainsString(
            'data-firma-registrada',
            substr($html, $posEntrega, $posRecibe - $posEntrega),
            'La firma registrada debe precargarse en la de quien entrega.'
        );
        $this->assertStringNotContainsString(
            'data-firma-registrada',
            substr($html, $posRecibe, 400),
            'La firma de quien recibe es del cliente: no se precarga.'
        );
    }

    /** Una orden de salida con una partida, lista para la pantalla de firma. */
    private function ordenListaParaFirmar(User $user): \App\Models\OrdenSalida
    {
        $customer = \App\Models\Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);

        $venta = \App\Models\Venta::create([
            'folio' => 'V-TEST-1',
            'customer_id' => $customer->id,
            'seller_id' => $user->id,
            'modalidad' => 'contado',
            'estado' => 'confirmada',
            'total_contrato' => 100,
            'created_by' => $user->id,
        ]);

        $orden = \App\Models\OrdenSalida::create([
            'folio' => \App\Models\OrdenSalida::siguienteFolio(),
            'venta_id' => $venta->id,
            'estado' => \App\Models\OrdenSalida::PENDIENTE,
            'created_by' => $user->id,
        ]);

        $orden->items()->create([
            'nombre' => 'Endoscopio Olympus',
            'cantidad' => 1,
            'requiere_emplayado' => false,
            'preparado' => true,
            'preparado_en' => now(),
            'orden' => 0,
        ]);

        // El formulario de firma aparece cuando la preparación ya se cerró.
        $orden->fresh()->confirmarPreparacion($user->id);

        return $orden->fresh();
    }

    public function test_un_congreso_no_tiene_nada_que_ver_con_la_firma(): void
    {
        // Guard de humo: la firma registrada no depende de otros módulos.
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuario();
        Congress::create([
            'nombre' => 'Congreso',
            'categoria_id' => Category::create(['nombre' => 'Endoscopia'])->id,
            'fecha_inicio' => now()->toDateString(),
            'fecha_finalizacion' => now()->addDay()->toDateString(),
            'hora_montaje' => '08:00',
            'hora_desmontaje' => '18:00',
        ]);

        $this->actingAs($user)->post(route('profile.firma.store'), ['firma' => $this->firmaValida()]);

        $this->assertTrue($user->fresh()->tieneFirma());
    }
}
