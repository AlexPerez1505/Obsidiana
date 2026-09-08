<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Cubre el endpoint POST /marketing/tareas/preview-link que devuelve
 * una previsualizacion hibrida (YouTube/Canva/Vimeo/Drive/imagen/OpenGraph)
 * del link de entrega pegado por el usuario.
 */
class LinkPreviewTest extends TestCase
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

    private function previewLink(User $user, string $url): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->postJson(
            route('marketing.tareas.preview_link'),
            ['url' => $url]
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_link_de_youtube_devuelve_embed(): void
    {
        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $resp->assertOk()
            ->assertJson([
                'type' => 'youtube',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'embed_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            ]);
    }

    public function test_link_corto_de_youtube_devuelve_embed(): void
    {
        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'https://youtu.be/abcdef12345');

        $resp->assertOk()
            ->assertJson([
                'type' => 'youtube',
                'embed_url' => 'https://www.youtube.com/embed/abcdef12345',
            ]);
    }

    public function test_youtube_shorts_devuelve_embed(): void
    {
        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'https://www.youtube.com/shorts/abc123XYZ');

        $resp->assertOk()
            ->assertJson(['type' => 'youtube']);
    }

    public function test_link_de_vimeo_devuelve_embed(): void
    {
        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'https://vimeo.com/123456789');

        $resp->assertOk()
            ->assertJson([
                'type' => 'vimeo',
                'embed_url' => 'https://player.vimeo.com/video/123456789',
            ]);
    }

    public function test_link_de_google_drive_devuelve_preview(): void
    {
        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'https://drive.google.com/file/d/1aBcDeFgHiJkLmN0pQrS/view');

        $resp->assertOk()
            ->assertJson([
                'type' => 'drive',
                'embed_url' => 'https://drive.google.com/file/d/1aBcDeFgHiJkLmN0pQrS/preview',
            ]);
    }

    public function test_imagen_directa_devuelve_type_image(): void
    {
        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'https://example.com/foto.jpg');

        $resp->assertOk()
            ->assertJson([
                'type' => 'image',
                'image' => 'https://example.com/foto.jpg',
            ]);
    }

    public function test_imagen_webp_tambien_se_detecta(): void
    {
        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'https://example.com/flyer.webp');

        $resp->assertOk()
            ->assertJson(['type' => 'image']);
    }

    public function test_link_generico_devuelve_tarjeta_opengraph(): void
    {
        Http::fake([
            'example.com/*' => Http::response(
                '<html><head>'
                . '<meta property="og:title" content="Mi titulo de prueba">'
                . '<meta property="og:description" content="Una descripcion de prueba">'
                . '<meta property="og:image" content="https://example.com/img.jpg">'
                . '<title>Titulo de la pagina</title>'
                . '</head><body>Hola</body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'https://example.com/pagina');

        $resp->assertOk()
            ->assertJson([
                'type' => 'og',
                'url' => 'https://example.com/pagina',
                'title' => 'Mi titulo de prueba',
                'description' => 'Una descripcion de prueba',
                'image' => 'https://example.com/img.jpg',
            ]);
    }

    public function test_link_generico_sin_opengraph_usa_title_tag(): void
    {
        Http::fake([
            'example.com/*' => Http::response(
                '<html><head><title>Titulo simple</title></head><body>Hola</body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'https://example.com/simple');

        $resp->assertOk()
            ->assertJsonPath('type', 'og')
            ->assertJsonPath('title', 'Titulo simple');
    }

    public function test_link_que_no_responde_caen_a_link_simple(): void
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'https://example.com/caido');

        $resp->assertOk()
            ->assertJson(['type' => 'link']);
    }

    public function test_url_invalida_devuelve_422(): void
    {
        $user = $this->usuarioAprobado();

        $resp = $this->previewLink($user, 'no-es-una-url');

        $resp->assertStatus(422);
    }

    public function test_el_resultado_se_cachea(): void
    {
        Http::fake([
            'example.com/*' => Http::response(
                '<html><head><meta property="og:title" content="Cacheado"></head><body></body></html>',
                200
            ),
        ]);

        $user = $this->usuarioAprobado();

        // Primera llamada: hace el fetch
        $this->previewLink($user, 'https://example.com/cache-test');

        // Segunda llamada: debe salir del cache (no hacer otro fetch)
        $callCount = 0;
        Http::fake([
            'example.com/*' => function () use (&$callCount) {
                $callCount++;
                return Http::response('<html></html>', 200);
            },
        ]);

        $resp = $this->previewLink($user, 'https://example.com/cache-test');
        $resp->assertOk()
            ->assertJsonPath('title', 'Cacheado');

        // El segundo fake no debio llamarse porque el resultado estaba en cache
        $this->assertSame(0, $callCount, 'El resultado debio salir del cache, no hacer otro fetch.');
    }

    public function test_usuario_no_autenticado_no_puede_acceder(): void
    {
        $resp = $this->postJson(
            route('marketing.tareas.preview_link'),
            ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']
        );

        // Para JSON, el middleware auth devuelve 401; para web normal redirige al login.
        $this->assertContains($resp->status(), [401, 302, 301, 303, 307, 308]);
    }
}
