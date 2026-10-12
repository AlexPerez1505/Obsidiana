<?php

namespace Tests\Feature\Api\V1\Administracion;

use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class VehiculosTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    public function test_solo_administradores(): void
    {
        $user = $this->usuarioApi(['rutas.flota', 'usuarios.ver']);

        $this->conToken($user)->getJson('/api/v1/vehiculos')->assertForbidden();
        $this->conToken($user)->postJson('/api/v1/vehiculos', ['plate_number' => 'ABC'])->assertForbidden();
    }

    public function test_registrar_con_fotos_y_documentos(): void
    {
        Storage::fake('public');
        $admin = $this->usuarioApi(admin: true);

        $res = $this->conToken($admin)->post('/api/v1/vehiculos', [
            'plate_number' => 'JAL-123-A',
            'brand' => 'Nissan',
            'model' => 'NP300',
            'year' => 2024,
            'photos' => [UploadedFile::fake()->image('frente.jpg')],
            'insurance_doc' => UploadedFile::fake()->create('poliza.pdf', 50, 'application/pdf'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.plate_number', 'JAL-123-A')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonCount(1, 'data.photos_url')
            ->assertJsonPath('message', 'Vehículo registrado correctamente.');

        $vehiculo = Vehicle::firstOrFail();
        Storage::disk('public')->assertExists($vehiculo->photos[0]);
        Storage::disk('public')->assertExists($vehiculo->insurance_doc);
        $this->assertSame(Storage::disk('public')->url($vehiculo->insurance_doc), $res->json('data.insurance_doc_url'));
        $this->assertNull($res->json('data.tenancy_doc_url'));

        $this->conToken($admin)->postJson('/api/v1/vehiculos', ['plate_number' => 'JAL-123-A'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['plate_number']]);
    }

    public function test_buscar_y_ver(): void
    {
        $admin = $this->usuarioApi(admin: true);
        Vehicle::create(['plate_number' => 'AAA-1', 'brand' => 'Nissan', 'model' => 'Versa', 'status' => 'active']);
        $ford = Vehicle::create(['plate_number' => 'BBB-2', 'brand' => 'Ford', 'model' => 'Ranger', 'status' => 'maintenance']);

        $this->conToken($admin)->getJson('/api/v1/vehiculos?buscar=ford')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ford->id);
        $this->conToken($admin)->getJson('/api/v1/vehiculos?status=maintenance')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->conToken($admin)->getJson("/api/v1/vehiculos/{$ford->id}")
            ->assertOk()
            ->assertJsonPath('data.status_label', 'En mantenimiento')
            ->assertJsonPath('data.photos_url', []);

        $this->conToken($admin)->getJson('/api/v1/vehiculos/999999')
            ->assertNotFound()->assertJsonPath('codigo', 'no_encontrado');
    }
}
