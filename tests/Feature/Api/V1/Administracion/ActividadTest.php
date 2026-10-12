<?php

namespace Tests\Feature\Api\V1\Administracion;

use App\Models\Task;
use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class ActividadTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    public function test_pide_actividad_ver(): void
    {
        $user = $this->usuarioApi(['usuarios.ver']);

        $this->conToken($user)->getJson('/api/v1/actividad')->assertForbidden();

        $conPermiso = $this->usuarioApi(['actividad.ver']);
        $this->conToken($conPermiso, ['usuarios.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/actividad')->assertForbidden();
    }

    public function test_linea_de_tiempo_paginada_con_resumen(): void
    {
        $user = $this->usuarioApi(['actividad.ver']);
        $otro = $this->usuarioApi();

        foreach (['Flyer de octubre', 'Video del congreso', 'Post de servicio'] as $titulo) {
            Task::forceCreate(['title' => $titulo, 'user_id' => $otro->id, 'created_by' => $otro->id]);
        }

        $this->conToken($user)->getJson('/api/v1/actividad?modulo=tareas&per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.modulo', 'tareas')
            ->assertJsonPath('data.0.usuario_id', $otro->id)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.resumen.total', 3)
            ->assertJsonPath('meta.resumen.por_modulo.tareas', 3)
            ->assertJsonPath('meta.modulo', 'tareas')
            ->assertJsonStructure(['data' => [['cuando', 'usuario', 'titulo', 'detalle', 'url']], 'links', 'meta' => ['desde', 'hasta']]);

        // Un módulo que no existe no filtra; un rango al revés se voltea.
        $this->conToken($user)->getJson('/api/v1/actividad?modulo=nada&desde='.now()->addDay()->toDateString().'&hasta='.now()->subDays(2)->toDateString())
            ->assertOk()
            ->assertJsonPath('meta.modulo', null)
            ->assertJsonPath('meta.resumen.total', 3);

        $this->conToken($user)->getJson('/api/v1/actividad?desde=ayer-no')
            ->assertStatus(422)->assertJsonStructure(['errors' => ['desde']]);
    }
}
