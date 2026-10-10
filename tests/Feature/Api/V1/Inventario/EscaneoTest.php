<?php

namespace Tests\Feature\Api\V1\Inventario;

use App\Models\Category;
use App\Models\Congress;
use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\Feature\Api\V1\Inventario\Concerns\DatosDeInventario;
use Tests\TestCase;

class EscaneoTest extends TestCase
{
    use DatosDeInventario, RefreshDatabase, UsaApi;

    public function test_la_liga_completa_del_qr_da_la_pieza_sin_precios(): void
    {
        $producto = $this->productoConPiezas(['SN-A']);
        $pieza = $producto->seriales()->firstOrFail();
        $user = $this->usuarioApi(['inventario.escanear', 'precios.ver']);

        $res = $this->conToken($user)->postJson('/api/v1/escanear', ['codigo' => $pieza->urlPublica()]);

        $res->assertOk()
            ->assertJsonPath('data.id', $pieza->id)
            ->assertJsonPath('data.codigo', $pieza->codigo)
            ->assertJsonPath('data.no_serie', 'SN-A')
            ->assertJsonPath('data.marca_modelo', 'Olympus X1')
            ->assertJsonPath('data.vendible', true)
            ->assertJsonPath('data.congreso', null);

        // Aunque el usuario pueda verlos, el escaneo no manda precios.
        $this->assertStringNotContainsString('precio', $res->getContent());

        // El código pelón, en minúsculas, también sirve.
        $this->conToken($user)->postJson('/api/v1/escanear', ['codigo' => strtolower($pieza->codigo)])
            ->assertOk()->assertJsonPath('data.id', $pieza->id);
    }

    public function test_una_pieza_en_congreso_avisa_donde_esta(): void
    {
        $producto = $this->productoConPiezas(['SN-C']);
        $pieza = $producto->seriales()->firstOrFail();
        $congreso = Congress::create([
            'nombre' => 'Congreso de Endoscopia',
            'categoria_id' => Category::firstOrCreate(['nombre' => 'Endoscopia'])->id,
            'fecha_inicio' => now()->addDay()->toDateString(),
            'fecha_finalizacion' => now()->addDays(3)->toDateString(),
            'hora_montaje' => '08:00',
            'hora_desmontaje' => '18:00',
        ]);
        $pieza->update(['congress_id' => $congreso->id, 'enviado_a_congreso_en' => now()]);

        $this->conToken($this->usuarioApi(['inventario.escanear']))
            ->postJson('/api/v1/escanear', ['codigo' => $pieza->codigo])
            ->assertOk()
            ->assertJsonPath('data.congreso.id', $congreso->id)
            ->assertJsonPath('data.vendible', true);
    }

    public function test_lo_que_no_es_etiqueta_da_422_y_la_no_registrada_404(): void
    {
        $user = $this->usuarioApi(['inventario.escanear']);

        $this->conToken($user)->postJson('/api/v1/escanear', ['codigo' => 'hola'])
            ->assertStatus(422)
            ->assertJsonPath('errors.codigo.0', 'Eso no parece una etiqueta del sistema.');

        $this->conToken($user)->postJson('/api/v1/escanear', ['codigo' => 'MB-999999'])
            ->assertNotFound()
            ->assertJsonPath('message', 'La etiqueta MB-999999 no está registrada.')
            ->assertJsonPath('codigo', 'no_encontrado');

        $this->conToken($user)->postJson('/api/v1/escanear', [])
            ->assertStatus(422)->assertJsonValidationErrors('codigo');
    }

    public function test_escanear_pide_su_propio_permiso(): void
    {
        $this->conToken($this->usuarioApi(['inventario.ver']))
            ->postJson('/api/v1/escanear', ['codigo' => 'MB-000001'])
            ->assertForbidden();

        $admin = $this->usuarioApi(admin: true);
        $this->conToken($admin, ['inventario.ver'], TokenApi::TIPO_INTEGRACION)
            ->postJson('/api/v1/escanear', ['codigo' => 'MB-000001'])
            ->assertForbidden();
    }
}
