<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cubre el endpoint que recibe el video de verificación en pedazos
 * (chunks) y lo ensambla, para no mandar el archivo completo de golpe.
 */
class InventoryVideoChunkTest extends TestCase
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

    private function subirChunk(User $user, array $data): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->post(
            route('inventory.movimientos.videoChunk'),
            $data,
            ['Accept' => 'application/json']
        );
    }

    public function test_subir_un_video_en_varios_pedazos_lo_ensambla_correctamente(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();
        $uploadId = 'test-'.uniqid();

        $respuesta1 = $this->subirChunk($user, [
            'chunk' => UploadedFile::fake()->create('parte1.mp4', 200, 'video/mp4'),
            'upload_id' => $uploadId,
            'index' => 0,
            'total' => 3,
            'extension' => 'mp4',
        ]);
        $respuesta1->assertOk();
        $this->assertSame('chunk_recibido', $respuesta1->json('status'));

        $respuesta2 = $this->subirChunk($user, [
            'chunk' => UploadedFile::fake()->create('parte2.mp4', 200, 'video/mp4'),
            'upload_id' => $uploadId,
            'index' => 1,
            'total' => 3,
            'extension' => 'mp4',
        ]);
        $respuesta2->assertOk();
        $this->assertSame('chunk_recibido', $respuesta2->json('status'));

        $respuesta3 = $this->subirChunk($user, [
            'chunk' => UploadedFile::fake()->create('parte3.mp4', 200, 'video/mp4'),
            'upload_id' => $uploadId,
            'index' => 2,
            'total' => 3,
            'extension' => 'mp4',
        ]);
        $respuesta3->assertOk();
        $this->assertSame('listo', $respuesta3->json('status'));

        $videoPath = $respuesta3->json('video_path');
        $this->assertNotNull($videoPath);
        Storage::disk('public')->assertExists($videoPath);

        // La carpeta temporal de pedazos ya no debe existir.
        Storage::disk('public')->assertDirectoryEmpty("inventario/tmp_videos/{$uploadId}");
    }

    public function test_extension_no_permitida_regresa_error(): void
    {
        Storage::fake('public');
        $user = $this->usuarioAprobado();

        $respuesta = $this->subirChunk($user, [
            'chunk' => UploadedFile::fake()->create('malware.exe', 100),
            'upload_id' => 'test-'.uniqid(),
            'index' => 0,
            'total' => 1,
            'extension' => 'exe',
        ]);

        $respuesta->assertStatus(422)->assertJsonValidationErrors('extension');
    }
}
