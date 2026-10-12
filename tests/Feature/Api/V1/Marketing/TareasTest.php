<?php

namespace Tests\Feature\Api\V1\Marketing;

use App\Models\Task;
use App\Models\TokenApi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class TareasTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function editor(): User
    {
        return $this->usuarioApi(['marketing.ver', 'marketing.editar']);
    }

    private function aprobador(): User
    {
        return $this->usuarioApi(['marketing.ver', 'marketing.aprobar']);
    }

    private function tarea(array $datos = []): Task
    {
        return Task::create($datos + [
            'title' => 'Flyer gastroscopio',
            'status' => 'pendiente',
            'priority' => 'media',
            'progress' => 0,
            'user_id' => $datos['user_id'] ?? User::factory()->create()->id,
        ]);
    }

    public function test_crud_de_tareas(): void
    {
        Storage::fake('public');
        $editor = $this->editor();
        $responsable = $this->usuarioApi(atributos: ['name' => 'Diseñadora']);

        $res = $this->conToken($editor)->post('/api/v1/marketing/tareas', [
            'title' => 'Carrusel torre 4K',
            'priority' => 'alta',
            'progress' => 10,
            'user_id' => $responsable->id,
            'due_date' => '2026-11-03',
            'tags' => ['torre', ' 4k ', ''],
            'platform' => ['Instagram', 'Facebook'],
            'has_video' => '1',
            'project_image' => UploadedFile::fake()->image('pieza.png'),
        ]);

        $id = $res->json('data.id');
        $res->assertCreated()
            ->assertJsonPath('message', 'Tarea creada correctamente.')
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.tags', ['torre', '4k'])
            ->assertJsonPath('data.platform', ['Instagram', 'Facebook'])
            ->assertJsonPath('data.has_video', true)
            ->assertJsonPath('data.due_date', '2026-11-03')
            ->assertJsonPath('data.user.name', 'Diseñadora')
            ->assertJsonPath('data.created_by', $editor->id);
        $this->assertStringContainsString('/storage/project_images/', $res->json('data.project_image_url'));

        $this->conToken($editor)->getJson("/api/v1/marketing/tareas/{$id}")
            ->assertOk()->assertJsonPath('data.title', 'Carrusel torre 4K');

        $this->conToken($editor)->putJson("/api/v1/marketing/tareas/{$id}", [
            'title' => 'Carrusel torre 4K (v2)',
            'status' => 'en_proceso',
            'priority' => 'media',
            'progress' => 50,
            'user_id' => $responsable->id,
            'tags' => 'torre, lanzamiento',
        ])->assertOk()
            ->assertJsonPath('message', 'Tarea actualizada correctamente.')
            ->assertJsonPath('data.status', 'en_proceso')
            ->assertJsonPath('data.tags', ['torre', 'lanzamiento'])
            ->assertJsonPath('data.progress', 50);

        $this->conToken($editor)->deleteJson("/api/v1/marketing/tareas/{$id}")
            ->assertOk()->assertJsonPath('message', 'Tarea eliminada correctamente.');

        $this->assertDatabaseMissing('tasks', ['id' => $id]);
    }

    public function test_crear_sin_datos_da_422(): void
    {
        $this->conToken($this->editor())->postJson('/api/v1/marketing/tareas', ['priority' => 'urgente'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['title', 'priority', 'progress', 'user_id']]);
    }

    public function test_flujo_de_revision_y_aprobacion(): void
    {
        $editor = $this->editor();
        $aprobador = $this->aprobador();
        $tarea = $this->tarea(['user_id' => $editor->id]);

        $this->conToken($editor)->postJson("/api/v1/marketing/tareas/{$tarea->id}/enviar-revision")
            ->assertOk()
            ->assertJsonPath('data.status', 'revision')
            ->assertJsonPath('message', 'Pieza enviada a revisión.');

        // Editar no alcanza para decidir sobre la pieza.
        $this->conToken($editor)->postJson("/api/v1/marketing/tareas/{$tarea->id}/aprobar")->assertForbidden();
        $this->conToken($editor)->postJson("/api/v1/marketing/tareas/{$tarea->id}/devolver", ['rejection_comment' => 'x'])->assertForbidden();

        $this->conToken($aprobador)->postJson("/api/v1/marketing/tareas/{$tarea->id}/devolver", [])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['rejection_comment']]);

        $this->conToken($aprobador)->postJson("/api/v1/marketing/tareas/{$tarea->id}/devolver", [
            'rejection_comment' => 'El modelo es EG-760R, no EG-760.',
            'approval_checklist' => [0, 4],
        ])->assertOk()
            ->assertJsonPath('message', 'Pieza devuelta con observaciones.')
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.progress', 20)
            ->assertJsonPath('data.approval_checklist', [0, 4]);

        $comentario = $tarea->fresh()->rejection_comment;
        $this->assertStringStartsWith("Especificaciones:\nEl modelo es EG-760R, no EG-760.", $comentario);
        $this->assertStringContainsString('Checklist pendiente: Specs verificados, Marca/logo', $comentario);

        $this->conToken($aprobador)->postJson("/api/v1/marketing/tareas/{$tarea->id}/aprobar")
            ->assertOk()
            ->assertJsonPath('message', 'Tarea aprobada correctamente.')
            ->assertJsonPath('data.status', 'completada')
            ->assertJsonPath('data.progress', 100)
            ->assertJsonPath('data.rejection_comment', null);
    }

    public function test_listado_con_filtros_y_contadores(): void
    {
        $editor = $this->editor();
        $this->tarea(['title' => 'Promo octubre', 'priority' => 'alta', 'tags' => ['promo']]);
        $this->tarea(['title' => 'Video congreso', 'status' => 'revision']);
        $this->tarea(['title' => 'Vencida', 'due_date' => now()->subDays(3)->toDateString()]);

        $this->conToken($editor)->getJson('/api/v1/marketing/tareas')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('stats.total', 3)
            ->assertJsonPath('stats.revision', 1)
            ->assertJsonPath('stats.high_priority', 1)
            ->assertJsonPath('stats.overdue', 1);

        $this->conToken($editor)->getJson('/api/v1/marketing/tareas?q=promo')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Promo octubre');

        $this->conToken($editor)->getJson('/api/v1/marketing/tareas?status=revision')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('stats.total', 1);

        $this->conToken($editor)->getJson('/api/v1/marketing/tareas?due=overdue')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Vencida');

        $this->conToken($editor)->getJson('/api/v1/marketing/tareas/opciones')
            ->assertOk()
            ->assertJsonPath('data.checklist.0', 'Nombre/modelo')
            ->assertJsonPath('data.estados.revision', 'Revisión')
            ->assertJsonStructure(['data' => ['usuarios', 'prioridades', 'categorias']]);
    }

    public function test_tableros_de_inicio_aprobacion_y_calendario(): void
    {
        $aprobador = $this->aprobador();
        $this->tarea(['status' => 'pendiente', 'rejection_comment' => 'corrige', 'category' => 'Equipos', 'due_date' => '2026-10-15']);
        $this->tarea(['status' => 'revision', 'category' => 'Congreso', 'due_date' => '2026-10-20']);
        $this->tarea(['status' => 'completada', 'due_date' => '2026-11-02']);

        $this->conToken($aprobador)->getJson('/api/v1/marketing/inicio')
            ->assertOk()
            ->assertJsonPath('data.cambios_solicitados', 1)
            ->assertJsonPath('data.en_revision', 1)
            ->assertJsonPath('data.pendiente_por_tomar', 1)
            ->assertJsonPath('data.areas_especializadas', 2);

        $this->conToken($aprobador)->getJson('/api/v1/marketing/aprobacion-flyers?status=revision')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('stats.cambios', 1)
            ->assertJsonPath('stats.en_curso', 1)
            ->assertJsonPath('stats.aprobado', 1);

        $this->conToken($aprobador)->getJson('/api/v1/marketing/calendario?month=10&year=2026')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('mes.month', 10)
            ->assertJsonPath('categorias.0.name', 'Educación');

        $this->conToken($aprobador)->getJson('/api/v1/marketing/calendario?month=13')->assertStatus(422);

        $this->conToken($this->editor())->getJson('/api/v1/marketing/aprobacion-flyers')->assertForbidden();
    }

    public function test_biblioteca_y_descarga_del_flyer(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('project_images/torre.png', 'png');
        $lector = $this->usuarioApi(['marketing.ver']);
        $flyer = $this->tarea(['title' => 'Torre 4K', 'status' => 'completada', 'project_image' => 'project_images/torre.png']);
        $sinTerminar = $this->tarea(['status' => 'revision', 'project_image' => 'project_images/torre.png']);

        $this->conToken($lector)->getJson('/api/v1/marketing/biblioteca')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $flyer->id);

        $res = $this->conToken($lector)->get("/api/v1/marketing/biblioteca/{$flyer->id}/descargar")->assertOk();
        $this->assertStringContainsString('Torre_4K_flyer.png', $res->headers->get('Content-Disposition'));

        $this->conToken($lector)->getJson("/api/v1/marketing/biblioteca/{$sinTerminar->id}/descargar")->assertNotFound();
    }

    public function test_preview_de_enlace_sin_salir_a_internet(): void
    {
        Cache::flush();
        Http::preventStrayRequests();
        Http::fake();
        $editor = $this->editor();

        $this->conToken($editor)->postJson('/api/v1/marketing/tareas/preview-link', ['url' => 'https://youtu.be/abcdef12345'])
            ->assertOk()
            ->assertJsonPath('data.type', 'youtube')
            ->assertJsonPath('data.embed_url', 'https://www.youtube.com/embed/abcdef12345');

        $this->conToken($editor)->postJson('/api/v1/marketing/tareas/preview-link', ['url' => 'no-es-url'])
            ->assertStatus(422);

        $this->conToken($this->usuarioApi(['marketing.ver']))
            ->postJson('/api/v1/marketing/tareas/preview-link', ['url' => 'https://youtu.be/abcdef12345'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_sin_permiso_y_llave_recortada(): void
    {
        $tarea = $this->tarea();

        $this->conToken($this->usuarioApi())->getJson('/api/v1/marketing/tareas')
            ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');

        $lector = $this->usuarioApi(['marketing.ver']);
        $this->conToken($lector)->getJson('/api/v1/marketing/tareas')->assertOk();
        $this->conToken($lector)->deleteJson("/api/v1/marketing/tareas/{$tarea->id}")->assertForbidden();

        $admin = $this->usuarioApi(admin: true);
        $llave = $this->conToken($admin, ['marketing.ver'], TokenApi::TIPO_INTEGRACION);
        $llave->getJson("/api/v1/marketing/tareas/{$tarea->id}")->assertOk();
        $llave->postJson("/api/v1/marketing/tareas/{$tarea->id}/aprobar")->assertForbidden();
        $llave->getJson('/api/v1/servicios')->assertForbidden();

        $this->assertSame('pendiente', $tarea->fresh()->status);
    }
}
