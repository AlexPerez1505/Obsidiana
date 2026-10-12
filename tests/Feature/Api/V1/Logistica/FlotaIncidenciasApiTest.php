<?php

namespace Tests\Feature\Api\V1\Logistica;

use App\Models\Ruta;
use App\Models\RutaParada;
use App\Models\TokenApi;
use App\Models\VehiculoCombustible;
use App\Models\VehiculoMantenimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Flota (combustible y mantenimiento), tablero de incidencias e
 * indicadores de logística por la API.
 */
class FlotaIncidenciasApiTest extends TestCase
{
    use DatosDeLogistica, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.fotos_disk', 'public'));
    }

    public function test_combustible_calcula_rendimiento_y_se_puede_borrar(): void
    {
        $user = $this->usuarioApi(['rutas.flota']);
        $vehiculo = $this->vehiculo();

        $this->conToken($user)->post("/api/v1/flota/{$vehiculo->id}/combustible", [
            'fecha' => today()->subDays(10)->toDateString(), 'litros' => 40, 'monto' => 900, 'odometro_km' => 10000, 'tanque_lleno' => 1,
        ], ['Accept' => 'application/json'])->assertCreated();

        $segunda = $this->conToken($user)->post("/api/v1/flota/{$vehiculo->id}/combustible", [
            'fecha' => today()->toDateString(), 'litros' => 40, 'monto' => 950, 'odometro_km' => 10480, 'tanque_lleno' => 1,
            'ticket' => UploadedFile::fake()->image('ticket.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('message', 'Carga de combustible registrada.')
            ->assertJsonPath('data.odometro_km', 10480);

        $this->assertNotNull($segunda->json('data.ticket_url'));

        // 480 km / 40 L de la segunda carga
        $this->conToken($user)->getJson("/api/v1/flota/{$vehiculo->id}")
            ->assertOk()
            ->assertJsonPath('data.vehiculo.id', $vehiculo->id)
            ->assertJsonPath('data.rendimiento_km_l', 12)
            ->assertJsonPath('data.odometro_km', 10480)
            ->assertJsonCount(2, 'data.cargas')
            ->assertJsonPath('meta.tipos_mantenimiento.preventivo', 'Preventivo (servicio)');

        $this->conToken($user)->getJson("/api/v1/flota/{$vehiculo->id}/combustible")
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);

        $ticket = VehiculoCombustible::findOrFail($segunda->json('data.id'))->ticket;

        $this->conToken($user)->deleteJson('/api/v1/flota/combustible/'.$segunda->json('data.id'))
            ->assertOk()->assertJsonPath('message', 'Carga eliminada.');

        Storage::disk(config('filesystems.fotos_disk', 'public'))->assertMissing($ticket);
        $this->assertDatabaseCount('vehiculo_combustible', 1);
    }

    public function test_mantenimiento_genera_alertas_en_el_tablero(): void
    {
        $user = $this->usuarioApi(['rutas.flota']);
        $vehiculo = $this->vehiculo();

        $creado = $this->conToken($user)->postJson("/api/v1/flota/{$vehiculo->id}/mantenimiento", [
            'tipo' => 'preventivo',
            'fecha' => today()->subMonths(5)->toDateString(),
            'costo' => 3500,
            'proximo_fecha' => today()->addDays(5)->toDateString(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.tipo_label', 'Preventivo (servicio)')
            ->assertJsonPath('data.proximo_fecha', today()->addDays(5)->toDateString());

        $this->conToken($user)->getJson('/api/v1/flota')
            ->assertOk()
            ->assertJsonPath('data.0.vehiculo.id', $vehiculo->id)
            ->assertJsonPath('data.0.alertas.0', 'Preventivo (servicio) vence en 5 día(s)')
            ->assertJsonPath('data.0.ultimo_mantenimiento.id', $creado->json('data.id'))
            ->assertJsonPath('con_alertas', 1);

        $this->conToken($user)->getJson("/api/v1/flota/{$vehiculo->id}/mantenimiento")
            ->assertOk()->assertJsonCount(1, 'data');

        $this->conToken($user)->postJson("/api/v1/flota/{$vehiculo->id}/mantenimiento", ['tipo' => 'nada', 'fecha' => today()->addDay()->toDateString()])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['tipo', 'fecha']]);

        $this->conToken($user)->deleteJson('/api/v1/flota/mantenimiento/'.$creado->json('data.id'))
            ->assertOk()->assertJsonPath('message', 'Mantenimiento eliminado.');

        $this->assertSame(0, VehiculoMantenimiento::count());
    }

    public function test_flota_pide_su_permiso(): void
    {
        $vehiculo = $this->vehiculo();
        $user = $this->usuarioApi(['rutas.ver']);

        $this->conToken($user)->getJson('/api/v1/flota')->assertForbidden();
        $this->conToken($user)->postJson("/api/v1/flota/{$vehiculo->id}/combustible", [])->assertForbidden();

        // Llave de integración recortada: puede ver la flota, no cargar combustible... (mismo permiso)
        $flotero = $this->usuarioApi(['rutas.flota', 'rutas.ver']);
        $this->conToken($flotero, ['rutas.ver'], TokenApi::TIPO_INTEGRACION)->getJson('/api/v1/flota')->assertForbidden();
    }

    public function test_incidencias_listar_ver_y_resolver(): void
    {
        $user = $this->usuarioApi(['rutas.incidencias']);
        $ruta = $this->ruta($this->usuarioApi());
        $abierta = $ruta->incidencias()->create(['tipo' => 'trafico', 'descripcion' => 'Bloqueo en Tollocan', 'user_id' => $ruta->chofer_id]);
        $ruta->incidencias()->create(['tipo' => 'retraso', 'resuelta_en' => now()]);

        $this->conToken($user)->getJson('/api/v1/logistica/incidencias')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $abierta->id)
            ->assertJsonPath('data.0.ruta.folio', $ruta->folio)
            ->assertJsonPath('abiertas', 1);

        $this->conToken($user)->getJson('/api/v1/logistica/incidencias?estado=resueltas')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->conToken($user)->getJson("/api/v1/logistica/incidencias/{$abierta->id}")
            ->assertOk()->assertJsonPath('data.tipo_label', 'Tráfico / bloqueo');

        $this->conToken($user)->postJson("/api/v1/logistica/incidencias/{$abierta->id}/resolver", ['resolucion' => 'Se tomó otra ruta'])
            ->assertOk()
            ->assertJsonPath('message', 'Incidencia marcada como resuelta.')
            ->assertJsonPath('data.resuelta_por', $user->id)
            ->assertJsonPath('data.resolucion', 'Se tomó otra ruta');

        $this->assertNotNull($abierta->fresh()->resuelta_en);
        $this->assertDatabaseHas('ruta_eventos', ['ruta_id' => $ruta->id, 'tipo' => 'incidencia_resuelta', 'user_id' => $user->id]);

        $this->conToken($this->usuarioApi(['rutas.ver']))->getJson('/api/v1/logistica/incidencias')->assertForbidden();
    }

    public function test_indicadores_del_periodo(): void
    {
        $user = $this->usuarioApi(['rutas.analytics']);
        $ruta = $this->ruta($this->usuarioApi(), Ruta::FINALIZADA, ['km_recorridos' => 42.5]);
        $ruta->paradas[0]->update(['estado' => RutaParada::COMPLETADA, 'llegada_en' => now()->subMinutes(20), 'completada_en' => now()]);
        $ruta->paradas[1]->update(['estado' => RutaParada::NO_ENTREGADA, 'completada_en' => now()]);

        $this->conToken($user)->getJson('/api/v1/logistica/indicadores?desde='.today()->subDays(6)->toDateString().'&hasta='.today()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.desde', today()->subDays(6)->toDateString())
            ->assertJsonPath('data.kpis.entregas', 1)
            ->assertJsonPath('data.kpis.no_entregadas', 1)
            ->assertJsonPath('data.kpis.exito', 50)
            ->assertJsonPath('data.kpis.km', 42.5)
            ->assertJsonPath('data.kpis.tiempo_cliente', 20)
            ->assertJsonPath('data.por_vehiculo.0.vehicle_id', $ruta->vehicle_id)
            ->assertJsonPath('data.por_chofer.0.chofer_id', $ruta->chofer_id)
            ->assertJsonCount(7, 'data.por_dia')
            ->assertJsonPath('data.por_dia.6.entregadas', 1);

        $this->conToken($user)->getJson('/api/v1/logistica/indicadores?desde=no-es-fecha')
            ->assertStatus(422)->assertJsonStructure(['errors' => ['desde']]);

        $this->conToken($this->usuarioApi(['rutas.ver']))->getJson('/api/v1/logistica/indicadores')->assertForbidden();
    }
}
