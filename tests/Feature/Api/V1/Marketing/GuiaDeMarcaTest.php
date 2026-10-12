<?php

namespace Tests\Feature\Api\V1\Marketing;

use App\Models\BrandGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class GuiaDeMarcaTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    public function test_la_primera_consulta_crea_la_guia_por_defecto(): void
    {
        $this->conToken($this->usuarioApi(['marketing.ver']))->getJson('/api/v1/marketing/guia-de-marca')
            ->assertOk()
            ->assertJsonPath('data.colors.0.name', 'Azul MediBuy')
            ->assertJsonPath('data.fonts.1.name', 'Inter');

        $this->assertDatabaseCount('guias_de_marca', 1);
    }

    public function test_editar_reemplaza_solo_lo_que_se_manda(): void
    {
        $editor = $this->usuarioApi(['marketing.ver', 'marketing.editar']);

        $this->conToken($editor)->putJson('/api/v1/marketing/guia-de-marca', [
            'colors' => [['name' => 'Negro obsidiana', 'hex' => '#0B0B0F']],
        ])->assertOk()
            ->assertJsonPath('message', 'Guía de marca actualizada.')
            ->assertJsonPath('data.colors', [['name' => 'Negro obsidiana', 'hex' => '#0B0B0F']])
            ->assertJsonPath('data.fonts.0.name', 'Open Sans');

        $this->assertSame('#0B0B0F', BrandGuide::sole()->colors[0]['hex']);
    }

    public function test_validacion_y_permisos(): void
    {
        $editor = $this->usuarioApi(['marketing.ver', 'marketing.editar']);

        $this->conToken($editor)->putJson('/api/v1/marketing/guia-de-marca', [
            'colors' => [['name' => 'Sin hex']],
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['colors.0.hex']]);

        $this->conToken($this->usuarioApi(['marketing.ver']))
            ->putJson('/api/v1/marketing/guia-de-marca', ['colors' => []])
            ->assertForbidden();
    }
}
