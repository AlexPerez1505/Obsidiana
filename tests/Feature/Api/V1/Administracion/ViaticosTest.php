<?php

namespace Tests\Feature\Api\V1\Administracion;

use App\Models\Vehicle;
use App\Models\Viatic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class ViaticosTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    public function test_solo_administradores(): void
    {
        $user = $this->usuarioApi(['usuarios.ver']);

        $this->conToken($user)->getJson('/api/v1/viaticos')->assertForbidden();
        $this->conToken($user)->postJson('/api/v1/viaticos', ['place' => 'GDL'])->assertForbidden();
    }

    public function test_registrar_con_foto_del_ticket_crea_sus_gastos(): void
    {
        Storage::fake('public');
        $admin = $this->usuarioApi(admin: true);
        $vehiculo = Vehicle::create(['plate_number' => 'XYZ-9', 'brand' => 'Toyota', 'model' => 'Hilux']);

        $res = $this->conToken($admin)->post('/api/v1/viaticos', [
            'place' => 'Guadalajara',
            'vehicle_id' => $vehiculo->id,
            'tolls' => 250.5,
            'fuel' => 800,
            'expense_date' => '2026-10-09',
            'ticket_photos' => [UploadedFile::fake()->image('ticket.jpg')],
        ])
            ->assertCreated()
            ->assertJsonPath('data.vehicle_name', 'Toyota Hilux')
            ->assertJsonPath('data.expense_date', '2026-10-09')
            ->assertJsonPath('data.total', 1050.5)
            ->assertJsonCount(2, 'data.gastos')
            ->assertJsonCount(1, 'data.ticket_photos_url');

        $viatic = Viatic::firstOrFail();
        Storage::disk('public')->assertExists($viatic->ticket_photos[0]);
        $this->assertSame(Storage::disk('public')->url($viatic->ticket_photos[0]), $res->json('data.ticket_photos_url.0'));
        $this->assertEqualsCanonicalizing(['toll', 'fuel'], $viatic->expenses()->pluck('type')->all());
    }

    public function test_editar_agrega_y_quita_fotos_sin_tocar_archivos_ajenos(): void
    {
        Storage::fake('public');
        $admin = $this->usuarioApi(admin: true);

        $propia = UploadedFile::fake()->image('a.jpg')->store('viaticos/tickets', 'public');
        $ajena = UploadedFile::fake()->create('ine.pdf', 10)->store('employee_documents/1', 'public');
        $viatic = Viatic::create(['user_id' => $admin->id, 'place' => 'Monterrey', 'ticket_photos' => [$propia]]);

        // Con archivos: POST multipart + _method=PATCH.
        $this->conToken($admin)->post("/api/v1/viaticos/{$viatic->id}", [
            '_method' => 'PATCH',
            'place' => 'Monterrey centro',
            'ticket_photos' => [UploadedFile::fake()->image('b.jpg')],
            'quitar_fotos' => [$propia, $ajena],
        ])
            ->assertOk()
            ->assertJsonPath('data.place', 'Monterrey centro')
            ->assertJsonCount(1, 'data.ticket_photos');

        $viatic->refresh();
        $this->assertNotContains($propia, $viatic->ticket_photos);
        Storage::disk('public')->assertMissing($propia);
        Storage::disk('public')->assertExists($viatic->ticket_photos[0]);
        // Una ruta que no era del viático no se borra.
        Storage::disk('public')->assertExists($ajena);
    }

    public function test_gastos_sueltos_y_sus_totales(): void
    {
        $admin = $this->usuarioApi(admin: true);
        $viatic = Viatic::create(['user_id' => $admin->id, 'place' => 'Puebla']);
        $otro = Viatic::create(['user_id' => $admin->id, 'place' => 'Toluca']);

        $gasto = $this->conToken($admin)->postJson("/api/v1/viaticos/{$viatic->id}/gastos", ['type' => 'meal', 'amount' => 180])
            ->assertCreated()
            ->assertJsonPath('data.label', 'Sin descripción')
            ->assertJsonPath('data.icon', 'receipt')
            ->assertJsonPath('meta.total', 180)
            ->assertJsonPath('meta.count', 1)
            ->json('data.id');

        $this->conToken($admin)->patchJson("/api/v1/viaticos/{$viatic->id}/gastos/{$gasto}", ['type' => 'lodging', 'label' => 'Hotel', 'amount' => 900])
            ->assertOk()
            ->assertJsonPath('data.type', 'lodging')
            ->assertJsonPath('data.amount', 900)
            ->assertJsonPath('meta.total', 900);

        // Un gasto de otro viático no se toca por esta ruta.
        $this->conToken($admin)->deleteJson("/api/v1/viaticos/{$otro->id}/gastos/{$gasto}")->assertNotFound();

        $this->conToken($admin)->postJson("/api/v1/viaticos/{$viatic->id}/gastos", ['type' => 'avion', 'amount' => -1])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['type', 'amount']]);

        $this->conToken($admin)->deleteJson("/api/v1/viaticos/{$viatic->id}/gastos/{$gasto}")
            ->assertOk()->assertJsonPath('meta.count', 0);
    }

    public function test_listar_ver_y_eliminar_borra_sus_fotos(): void
    {
        Storage::fake('public');
        $admin = $this->usuarioApi(admin: true);
        $foto = UploadedFile::fake()->image('t.jpg')->store('viaticos/tickets', 'public');
        $viatic = Viatic::create(['user_id' => $admin->id, 'place' => 'Querétaro', 'ticket_photos' => [$foto]]);

        $this->conToken($admin)->getJson('/api/v1/viaticos?buscar=Quer')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.usuario.id', $admin->id);

        $this->conToken($admin)->getJson("/api/v1/viaticos/{$viatic->id}")
            ->assertOk()->assertJsonPath('data.gastos', []);

        $this->conToken($admin)->deleteJson("/api/v1/viaticos/{$viatic->id}")
            ->assertOk()->assertJsonPath('message', 'Viático eliminado correctamente.');

        $this->assertDatabaseMissing('viatics', ['id' => $viatic->id]);
        Storage::disk('public')->assertMissing($foto);
    }
}
