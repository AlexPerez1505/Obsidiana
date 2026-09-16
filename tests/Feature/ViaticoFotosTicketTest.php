<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Viatic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El botón "Agregar foto del ticket" era un placeholder (solo mostraba una
 * alerta) y la base solo soportaba una foto. Esto cubre el flujo real:
 * varias fotos por viático, agregar más después, y quitar una sin perder
 * las demás.
 */
class ViaticoFotosTicketTest extends TestCase
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

    public function test_crear_viatico_con_varias_fotos_de_ticket(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('admin.viatics.store'), [
            'place' => 'Guadalajara',
            'tolls' => 100,
            'ticket_photos' => [
                UploadedFile::fake()->image('ticket1.jpg'),
                UploadedFile::fake()->image('ticket2.jpg'),
            ],
        ]);

        $viatic = Viatic::firstOrFail();
        $response->assertRedirect(route('admin.viatics.show', $viatic));

        $this->assertCount(2, $viatic->ticket_photos);
        foreach ($viatic->ticket_photos as $ruta) {
            Storage::disk('public')->assertExists($ruta);
        }
    }

    public function test_agregar_una_foto_mas_conserva_las_que_ya_habia(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $existente = UploadedFile::fake()->image('previa.jpg')->store('viaticos/tickets', 'public');
        $viatic = Viatic::create([
            'user_id' => $user->id,
            'place' => 'Monterrey',
            'ticket_photos' => [$existente],
        ]);

        $this->actingAs($user)->patch(route('admin.viatics.update', $viatic), [
            'ticket_photos' => [UploadedFile::fake()->image('nueva.jpg')],
        ]);

        $viatic->refresh();
        $this->assertCount(2, $viatic->ticket_photos);
        $this->assertContains($existente, $viatic->ticket_photos);
    }

    public function test_quitar_una_foto_borra_el_archivo_y_deja_las_demas(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $foto1 = UploadedFile::fake()->image('a.jpg')->store('viaticos/tickets', 'public');
        $foto2 = UploadedFile::fake()->image('b.jpg')->store('viaticos/tickets', 'public');
        $viatic = Viatic::create([
            'user_id' => $user->id,
            'place' => 'CDMX',
            'ticket_photos' => [$foto1, $foto2],
        ]);

        $this->actingAs($user)->patch(route('admin.viatics.update', $viatic), [
            'quitar_fotos' => [$foto1],
        ]);

        $viatic->refresh();
        $this->assertSame([$foto2], array_values($viatic->ticket_photos));
        Storage::disk('public')->assertMissing($foto1);
        Storage::disk('public')->assertExists($foto2);
    }

    public function test_eliminar_el_viatico_borra_sus_fotos_del_disco(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $foto = UploadedFile::fake()->image('a.jpg')->store('viaticos/tickets', 'public');
        $viatic = Viatic::create([
            'user_id' => $user->id,
            'place' => 'CDMX',
            'ticket_photos' => [$foto],
        ]);

        $this->actingAs($user)->delete(route('admin.viatics.destroy', $viatic));

        Storage::disk('public')->assertMissing($foto);
    }

    public function test_paginas_de_crear_editar_y_ver_cargan_sin_error_500(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $viatic = Viatic::create([
            'user_id' => $user->id,
            'place' => 'Puebla',
            'ticket_photos' => [UploadedFile::fake()->image('t.jpg')->store('viaticos/tickets', 'public')],
        ]);

        $this->actingAs($user)->get(route('admin.viatics.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.viatics.create'))->assertOk();
        $this->actingAs($user)->get(route('admin.viatics.edit', $viatic))->assertOk();
        $this->actingAs($user)->get(route('admin.viatics.show', $viatic))->assertOk();
    }

    /**
     * Ningún dato del viático es obligatorio: a veces solo se quiere dejar
     * apartado el registro y completarlo después, o de plano no aplica
     * algún gasto. Un lugar, un monto o una fecha vacíos no deben tronar.
     */
    public function test_crear_viatico_sin_ningun_dato_no_truena(): void
    {
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('admin.viatics.store'), []);

        $viatic = Viatic::firstOrFail();
        $response->assertRedirect(route('admin.viatics.show', $viatic));
        $this->assertNull($viatic->place);
        $this->assertNull($viatic->vehicle_id);
        $this->assertNull($viatic->expense_date);
    }

    public function test_editar_viatico_dejando_todo_en_blanco_no_truena(): void
    {
        $user = $this->usuarioAprobado();

        $viatic = Viatic::create([
            'user_id' => $user->id,
            'place' => 'Puebla',
            'tolls' => 100,
            'expense_date' => now(),
        ]);

        // Un formulario real manda todos los campos, aunque vengan vacíos
        // (a diferencia de no mandar la llave): así es como el navegador
        // los entrega de verdad.
        $response = $this->actingAs($user)->patch(route('admin.viatics.update', $viatic), [
            'place' => '',
            'vehicle_id' => '',
            'tolls' => '',
            'fuel' => '',
            'meals' => '',
            'lodging' => '',
            'additional' => '',
            'description' => '',
            'expense_date' => '',
        ]);

        $response->assertRedirect(route('admin.viatics.index'));
        $viatic->refresh();
        $this->assertNull($viatic->place);
        $this->assertNull($viatic->expense_date);
    }
}
