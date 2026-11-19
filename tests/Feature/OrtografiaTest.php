<?php

namespace Tests\Feature;

use App\Services\Ortografia\CorrectorOrtografico;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/** Autocorrector ortográfico global (la IA va simulada). */
class OrtografiaTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.openai.api_key' => 'sk-prueba', 'services.openai.model' => 'gpt-5.4', 'cache.default' => 'array']);
        Cache::flush();
    }

    private function iaResponde(string $corregido, array $cambios = []): void
    {
        Http::fake(['*/responses' => Http::response([
            'output_text' => json_encode(['corregido' => $corregido, 'cambios' => $cambios]),
        ])]);
    }

    public function test_corrige_en_mayusculas_y_manda_minusculas_a_la_ia(): void
    {
        $this->iaResponde('el cliente necesita la pantalla', [['antes' => 'nesesita', 'despues' => 'necesita'], ['antes' => 'pantaya', 'despues' => 'pantalla']]);

        $r = app(CorrectorOrtografico::class)->corregir('EL CLIENTE NESESITA LA PANTAYA');

        $this->assertSame('EL CLIENTE NECESITA LA PANTALLA', $r['texto']);
        $this->assertSame([['antes' => 'NESESITA', 'despues' => 'NECESITA'], ['antes' => 'PANTAYA', 'despues' => 'PANTALLA']], $r['cambios']);
        Http::assertSent(fn ($req) => $req['input'][1]['content'] === 'el cliente nesesita la pantaya'
            && $req['reasoning']['effort'] === 'none');
    }

    public function test_respeta_minusculas_y_usa_cache(): void
    {
        $this->iaResponde('la cámara tiene un rayón', [['antes' => 'camara', 'despues' => 'cámara']]);
        $c = app(CorrectorOrtografico::class);

        $this->assertSame('la cámara tiene un rayón', $c->corregir('la camara tiene un rayon')['texto']);
        $c->corregir('la camara tiene un rayon');
        Http::assertSentCount(1);   // la segunda vez sale de caché
    }

    public function test_no_acepta_algo_que_no_sea_una_correccion(): void
    {
        // La IA "reescribió" de más: se queda el original.
        $this->iaResponde('Estimado cliente, le informamos amablemente que su equipo requiere un servicio de mantenimiento completo.');
        $r = app(CorrectorOrtografico::class)->corregir('EQUIPO CON FALLA');
        $this->assertSame('EQUIPO CON FALLA', $r['texto']);
        $this->assertSame([], $r['cambios']);
    }

    public function test_sin_letras_o_sin_llave_no_pregunta(): void
    {
        Http::fake();
        $c = app(CorrectorOrtografico::class);
        $this->assertSame('MB-000123', $c->corregir('MB-000123')['texto']);

        config(['services.openai.api_key' => null]);
        $r = $c->corregir('TEXTO CON ERORES');
        $this->assertSame('TEXTO CON ERORES', $r['texto']);
        $this->assertFalse($r['disponible']);
        Http::assertNothingSent();
    }

    public function test_si_la_ia_falla_regresa_el_texto_igual(): void
    {
        Http::fake(['*/responses' => Http::response(['error' => 'x'], 500)]);
        $r = app(CorrectorOrtografico::class)->corregir('TEXTO CON ERORES');
        $this->assertSame('TEXTO CON ERORES', $r['texto']);
        $this->assertFalse($r['disponible']);
    }

    public function test_la_ruta_pide_sesion_y_esta_en_todas_las_pantallas(): void
    {
        $this->iaResponde('llamar el próximo miércoles');
        $this->postJson(route('ortografia.corregir'), ['texto' => 'LLAMAR EL PROXIMO MIERCOLES'])->assertUnauthorized();

        $u = $this->usuarioApi(['inventario.ver']);
        $this->actingAs($u)->postJson(route('ortografia.corregir'), ['texto' => 'LLAMAR EL PROXIMO MIERCOLES'])
            ->assertOk()->assertJsonPath('texto', 'LLAMAR EL PRÓXIMO MIÉRCOLES');

        $this->actingAs($u)->get(route('inventory.movimientos.index'))->assertOk()
            ->assertSee('_ortografia', false)->assertSee('orto-nota', false);
    }
}
