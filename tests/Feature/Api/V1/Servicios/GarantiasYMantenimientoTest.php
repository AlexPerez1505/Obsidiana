<?php

namespace Tests\Feature\Api\V1\Servicios;

use App\Models\GarantiaDocumento;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class GarantiasYMantenimientoTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function capturista(): User
    {
        return $this->usuarioApi(['servicios.ver', 'servicios.crear'], atributos: ['name' => 'Capturista de servicios']);
    }

    private function ordenInterna(User $user, User $tecnico, array $datos = []): Service
    {
        $res = $this->conToken($user)->postJson('/api/v1/servicios', $datos + [
            'mantenimiento_interno' => '1',
            'internal_technician_id' => $tecnico->id,
            'tipo_equipo' => 'Colonoscopio',
            'modelo' => 'EC-760',
            'serie' => 'COL-77',
            'descripcion_equipo' => 'Fuga en canal de trabajo',
        ])->assertCreated();

        return Service::findOrFail($res->json('data.id'));
    }

    public function test_agrega_y_lista_cartas_de_garantia(): void
    {
        Storage::fake('public');
        $user = $this->capturista();

        $res = $this->conToken($user)->post('/api/v1/servicios/garantias', [
            'nombre' => 'Garantía Fujinon 2026',
            'tipo_equipo' => 'GASTROSCOPIO',
            'archivos' => [
                UploadedFile::fake()->create('carta.pdf', 120, 'application/pdf'),
                UploadedFile::fake()->image('sello.jpg'),
            ],
        ]);

        $anio = now()->year;
        $res->assertCreated()
            ->assertJsonPath('message', 'Carta agregada correctamente.')
            ->assertJsonPath('data.folio', "GAR-{$anio}-0001")
            ->assertJsonCount(2, 'data.archivos_url');
        $this->assertStringContainsString('/storage/garantias/', $res->json('data.archivos_url.0'));
        $this->assertCount(2, Storage::disk('public')->files('garantias'));

        GarantiaDocumento::create(['folio' => "GAR-{$anio}-0002", 'nombre' => 'Otra', 'tipo_equipo' => 'TORRE', 'archivos' => []]);

        $this->conToken($user)->getJson('/api/v1/servicios/garantias')
            ->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.nombre', 'Otra');

        $this->conToken($user)->getJson('/api/v1/servicios/garantias?tipo_equipo=GASTROSCOPIO')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->conToken($user)->getJson('/api/v1/servicios/garantias/'.$res->json('data.id'))
            ->assertOk()->assertJsonPath('data.nombre', 'Garantía Fujinon 2026');
    }

    public function test_carta_sin_nombre_da_422_con_el_mensaje_de_la_web(): void
    {
        $this->conToken($this->capturista())->postJson('/api/v1/servicios/garantias', ['tipo_equipo' => 'TORRE'])
            ->assertStatus(422)
            ->assertJsonPath('errors.nombre.0', 'El nombre de la carta es obligatorio.');

        $lector = $this->usuarioApi(['servicios.ver']);
        $this->conToken($lector)->getJson('/api/v1/servicios/garantias')->assertOk();
        $this->conToken($lector)->postJson('/api/v1/servicios/garantias', ['nombre' => 'X', 'tipo_equipo' => 'Y'])->assertForbidden();
    }

    public function test_tablero_de_mantenimiento_por_tecnico_y_externos(): void
    {
        $user = $this->capturista();
        $joel = $this->usuarioApi(atributos: ['name' => 'Joel Ramírez']);
        $enMesa = $this->ordenInterna($user, $joel);
        $this->ordenInterna($user, $joel); // registrada, todavía no está en su mesa
        $enMesa->update(['status' => 'en_progreso']);

        $this->conToken($user)->getJson('/api/v1/servicios/mantenimiento/tecnicos')
            ->assertOk()
            ->assertJsonPath('data.0.id', $joel->id)
            ->assertJsonPath('data.0.count', 1)
            ->assertJsonPath('data.0.initials', 'JR')
            ->assertJsonPath('data.1.id', 'externo')
            ->assertJsonPath('data.1.is_external', true);

        $this->conToken($user)->getJson('/api/v1/servicios/mantenimiento?tecnico='.$joel->id)
            ->assertOk()
            ->assertJsonPath('seleccionado.name', 'Joel Ramírez')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $enMesa->id);

        $this->conToken($user)->getJson('/api/v1/servicios/mantenimiento?tipo=externo')
            ->assertOk()
            ->assertJsonPath('seleccionado.id', 'externo')
            ->assertJsonCount(0, 'data');
    }

    public function test_hoja_de_mantenimiento_se_prellena_y_se_guarda(): void
    {
        $user = $this->capturista();
        $joel = $this->usuarioApi(atributos: ['name' => 'Joel Ramírez']);
        $orden = $this->ordenInterna($user, $joel);

        $this->conToken($user)->getJson("/api/v1/servicios/mantenimiento/{$orden->id}/reporte")
            ->assertOk()
            ->assertJsonPath('data.datos.folio', $orden->service_number)
            ->assertJsonPath('data.datos.marca', 'Fujinon')
            ->assertJsonPath('data.datos.ns', 'COL-77')
            ->assertJsonPath('data.datos.diagnostico', 'Fuga en canal de trabajo')
            ->assertJsonPath('data.datos.tecnico', 'Joel Ramírez')
            ->assertJsonPath('data.reporte', null);

        $hoja = ['campos' => ['ns' => 'COL-77'], 'estado' => ['angulacion' => 'ok'], 'nota' => 'Cambio de válvula'];

        $this->conToken($user)->putJson("/api/v1/servicios/mantenimiento/{$orden->id}/reporte", ['reporte' => $hoja])
            ->assertOk()
            ->assertJsonPath('message', 'Reporte guardado.')
            ->assertJsonPath('data.reporte.nota', 'Cambio de válvula');

        $this->assertSame($hoja, json_decode($orden->fresh()->reporte_data, true));

        $this->conToken($user)->getJson("/api/v1/servicios/{$orden->id}")
            ->assertOk()->assertJsonPath('data.reporte_guardado', true);

        $this->conToken($user)->putJson("/api/v1/servicios/mantenimiento/{$orden->id}/reporte", [])
            ->assertStatus(422);

        $lector = $this->usuarioApi(['servicios.ver']);
        $this->conToken($lector)->getJson("/api/v1/servicios/mantenimiento/{$orden->id}/reporte")->assertOk();
        $this->conToken($lector)->putJson("/api/v1/servicios/mantenimiento/{$orden->id}/reporte", ['reporte' => null])
            ->assertForbidden();
    }
}
