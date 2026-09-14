<?php

namespace Tests\Feature;

use App\Models\EquipmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cuando el registro de entrada se rechaza, lo capturado no se tira.
 *
 * Antes el formulario regresaba en blanco: había que volver a escribir
 * todo, volver a firmar y volver a subir el video (que ya estaba en el
 * servidor). Lo único que el navegador no permite restaurar son los
 * archivos elegidos, y eso se avisa en pantalla.
 */
class EntradaConservaDatosTest extends TestCase
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

    private function firmaValida(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    private function subirVideoDePrueba(User $user): string
    {
        $respuesta = $this->actingAs($user)->post(route('inventory.movimientos.videoChunk'), [
            'chunk' => UploadedFile::fake()->create('chunk.mp4', 500, 'video/mp4'),
            'upload_id' => 'test-'.uniqid(),
            'index' => 0,
            'total' => 1,
            'extension' => 'mp4',
        ]);

        return $respuesta->json('video_path');
    }

    public function test_un_registro_rechazado_regresa_con_los_datos_la_firma_y_el_video(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuarioAprobado();
        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);
        $video = $this->subirVideoDePrueba($user);
        $firma = $this->firmaValida();

        // Falta la evidencia fotográfica a propósito: el servidor rechaza.
        $respuesta = $this->actingAs($user)
            ->from(route('inventory.movimientos.create'))
            ->post(route('inventory.movimientos.store'), [
                'condicion' => 'nuevo',
                'equipment_type_id' => $tipo->id,
                'cantidad' => 3,
                'movement_date' => now()->format('Y-m-d'),
                'descripcion' => 'Llegó en caja sellada',
                'notas' => 'Se abrió para inspección',
                'modo_identificacion' => 'series',
                'series_texto' => "23A00010\n23A00011\n23A00012",
                'firma' => $firma,
                'video_path' => $video,
            ]);

        $respuesta->assertRedirect(route('inventory.movimientos.create'));
        $respuesta->assertSessionHasErrors('evidencias');

        // El formulario se vuelve a dibujar con lo que ya se había capturado.
        $pagina = $this->actingAs($user)->get(route('inventory.movimientos.create'));

        $pagina->assertOk();
        $pagina->assertSee('Llegó en caja sellada', false);
        $pagina->assertSee('Se abrió para inspección', false);
        $pagina->assertSee('23A00010', false);
        $pagina->assertSee('value="3"', false);
        // La firma regresa como valor del input: el lienzo la re-dibuja.
        $pagina->assertSee($firma, false);
        // El video ya vive en el servidor: no se vuelve a subir.
        $pagina->assertSee($video, false);
        // Y se avisa que las fotos sí hay que volver a adjuntarlas.
        $pagina->assertSee('volver a adjuntar', false);
    }

    public function test_el_modo_y_la_condicion_elegidos_siguen_marcados(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $user = $this->usuarioAprobado();
        $tipo = EquipmentType::create(['name' => 'Torre de endoscopia']);

        $this->actingAs($user)
            ->from(route('inventory.movimientos.create'))
            ->post(route('inventory.movimientos.store'), [
                'condicion' => 'usado',
                'equipment_type_id' => $tipo->id,
                'cantidad' => 1,
                'movement_date' => now()->format('Y-m-d'),
                'modo_identificacion' => 'series',
                'firma' => $this->firmaValida(),
                // Sin video ni evidencias: se rechaza.
            ]);

        $pagina = $this->actingAs($user)->get(route('inventory.movimientos.create'));

        $pagina->assertOk();
        // "usado" queda marcado, no se regresa a "nuevo" por omisión.
        $pagina->assertSee('value="usado" data-condicion', false);
        $contenido = $pagina->getContent();
        $posUsado = strpos($contenido, 'value="usado" data-condicion');
        $this->assertNotFalse($posUsado);
        $this->assertStringContainsString(
            'checked',
            substr($contenido, $posUsado, 120),
            'El radio de condición "usado" debe seguir marcado al volver.'
        );
    }
}
