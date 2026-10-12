<?php

namespace Tests\Feature\Api\V1\Administracion;

use App\Models\MaterialRequest;
use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class MaterialesTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function pedido(): array
    {
        return [
            'category' => 'Papeleria',
            'material_name' => 'Tóner',
            'quantity' => 3,
            'unit' => 'Pieza',
            'urgency' => 'Urgente',
            'required_date' => '2026-10-15',
        ];
    }

    public function test_cualquiera_pide_material_y_ve_solo_lo_suyo(): void
    {
        $yo = $this->usuarioApi();
        $otro = $this->usuarioApi();

        $res = $this->conToken($yo)->postJson('/api/v1/materiales', $this->pedido())
            ->assertCreated()
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.requested_by', $yo->id)
            ->assertJsonPath('data.required_date', '2026-10-15');

        $this->assertMatchesRegularExpression('/^SOL-\d{4}$/', $res->json('data.folio'));
        $ajena = $this->conToken($otro)->postJson('/api/v1/materiales', $this->pedido())->assertCreated()->json('data.id');
        $this->assertNotSame($res->json('data.folio'), MaterialRequest::find($ajena)->folio);

        $this->conToken($yo)->getJson('/api/v1/materiales')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $res->json('data.id'))
            ->assertJsonStructure(['meta' => ['pendientes', 'categorias', 'urgencias']]);

        $this->conToken($yo)->getJson("/api/v1/materiales/{$ajena}")->assertForbidden();
    }

    public function test_revisar_es_solo_de_admin_con_token_total(): void
    {
        $yo = $this->usuarioApi();
        $admin = $this->usuarioApi(admin: true);
        $id = $this->conToken($yo)->postJson('/api/v1/materiales', $this->pedido())->json('data.id');

        $this->conToken($yo)->patchJson("/api/v1/materiales/{$id}/revision", ['decision' => 'approve'])->assertForbidden();
        $this->conToken($admin, ['usuarios.ver'], TokenApi::TIPO_INTEGRACION)
            ->patchJson("/api/v1/materiales/{$id}/revision", ['decision' => 'approve'])->assertForbidden();

        $this->conToken($admin)->patchJson("/api/v1/materiales/{$id}/revision", ['decision' => 'approve', 'review_notes' => 'Ok'])
            ->assertOk()
            ->assertJsonPath('data.status', 'aprobada')
            ->assertJsonPath('data.approved_quantity', 3)
            ->assertJsonPath('data.reviewed_by', $admin->id)
            ->assertJsonPath('message', 'Solicitud aprobada.');

        // El admin las ve todas.
        $ids = collect($this->conToken($admin)->getJson('/api/v1/materiales')->json('data'))->pluck('id');
        $this->assertContains($id, $ids);

        $this->conToken($admin)->patchJson("/api/v1/materiales/{$id}/revision", ['decision' => 'quizas'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['decision']]);
    }

    public function test_valida_el_pedido(): void
    {
        $yo = $this->usuarioApi();

        $this->conToken($yo)->postJson('/api/v1/materiales', ['quantity' => 0, 'urgency' => 'Ya'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['category', 'material_name', 'quantity', 'unit', 'urgency']]);
    }
}
