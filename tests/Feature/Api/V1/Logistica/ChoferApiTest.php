<?php

namespace Tests\Feature\Api\V1\Logistica;

use App\Models\Customer;
use App\Models\OrdenSalida;
use App\Models\Ruta;
use App\Models\RutaParada;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * La app del chofer: GPS (suelto y en lote), llegada, entrega con
 * evidencias, no entregada, incidencias y combustible. Solo sus rutas.
 */
class ChoferApiTest extends TestCase
{
    use DatosDeLogistica, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();

        config(['logistica.google_key' => null]);
        Http::preventStrayRequests();
        Storage::fake(config('filesystems.fotos_disk', 'public'));
    }

    private function chofer()
    {
        return $this->usuarioApi(['rutas.chofer']);
    }

    public function test_el_chofer_ve_solo_sus_rutas(): void
    {
        $chofer = $this->chofer();
        $mia = $this->ruta($chofer);
        $this->ruta($this->chofer());
        $cerrada = $this->ruta($chofer, Ruta::FINALIZADA, ['finalizada_en' => now()]);

        $this->conToken($chofer)->getJson('/api/v1/chofer/rutas')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mia->id);

        $this->conToken($chofer)->getJson('/api/v1/chofer/rutas?estado=finalizadas')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $cerrada->id);

        $this->conToken($chofer)->getJson("/api/v1/chofer/rutas/{$mia->id}")
            ->assertOk()
            ->assertJsonPath('meta.siguiente_parada_id', $mia->paradas[0]->id)
            ->assertJsonPath('meta.intervalo_gps_s', config('logistica.intervalo_gps_s'))
            ->assertJsonPath('meta.tipos_incidencia.falla_mecanica', 'Falla mecánica');
    }

    public function test_no_opera_rutas_ajenas_aunque_sea_admin(): void
    {
        $ajena = $this->ruta($this->chofer());
        $parada = $ajena->paradas[0];

        foreach ([$this->chofer(), $this->usuarioApi(admin: true)] as $user) {
            $this->conToken($user)->getJson("/api/v1/chofer/rutas/{$ajena->id}")
                ->assertForbidden()->assertJsonPath('message', 'Esta ruta no está asignada a ti.');
            $this->conToken($user)->postJson("/api/v1/chofer/rutas/{$ajena->id}/ubicacion", ['lat' => 19.29, 'lng' => -99.65])
                ->assertForbidden();
            $this->conToken($user)->postJson("/api/v1/chofer/rutas/{$ajena->id}/paradas/{$parada->id}/no-entregada", ['motivo' => 'x'])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('ruta_ubicaciones', 0);
        $this->assertSame(RutaParada::EN_CAMINO, $parada->fresh()->estado);
    }

    public function test_una_parada_de_otra_ruta_da_404(): void
    {
        $chofer = $this->chofer();
        $mia = $this->ruta($chofer);
        $otra = $this->ruta($this->chofer());

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$mia->id}/paradas/{$otra->paradas[0]->id}/llegada")
            ->assertNotFound();
    }

    public function test_sin_permiso_de_chofer_da_403(): void
    {
        $user = $this->usuarioApi(['rutas.ver']);
        $ruta = $this->ruta($user);

        $this->conToken($user)->getJson('/api/v1/chofer/rutas')->assertForbidden();
        $this->conToken($user)->postJson("/api/v1/chofer/rutas/{$ruta->id}/ubicacion", ['lat' => 19.29, 'lng' => -99.65])->assertForbidden();
    }

    public function test_ubicacion_suelta_detecta_la_llegada_por_geocerca(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer);

        // Lejos: solo se guarda el punto
        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/ubicacion", ['lat' => 19.2826, 'lng' => -99.6557, 'velocidad_ms' => 10])
            ->assertOk()
            ->assertJsonPath('procesadas', 1)
            ->assertJsonPath('avisos', [])
            ->assertJsonPath('data.activa', true)
            ->assertJsonPath('data.ultima_ubicacion.velocidad_kmh', 36);

        // Dentro del radio de la primera parada
        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/ubicacion", ['lat' => 19.2901, 'lng' => -99.6501])
            ->assertOk()
            ->assertJsonPath('avisos', ['Llegaste a Hospital Norte'])
            ->assertJsonPath('data.paradas.0.estado', RutaParada::ARRIBO);

        $this->assertDatabaseCount('ruta_ubicaciones', 2);
        $this->assertSame('auto', $ruta->paradas[0]->fresh()->llegada_origen);
    }

    public function test_lote_de_lecturas_sin_senal_se_procesa_en_orden_de_hora(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer);

        // Llegan desordenadas: primero la que pasa por la parada, luego la de antes
        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/ubicacion", ['lecturas' => [
            ['lat' => 19.2901, 'lng' => -99.6501, 'registrada_en' => now()->subMinutes(5)->toIso8601String()],
            ['lat' => 19.2826, 'lng' => -99.6557, 'registrada_en' => now()->subMinutes(10)->toIso8601String(), 'precision' => 8],
            ['lat' => 19.2700, 'lng' => -99.6300, 'registrada_en' => now()->subMinute()->toIso8601String()],
        ]])
            ->assertOk()
            ->assertJsonPath('procesadas', 3)
            ->assertJsonPath('avisos', ['Llegaste a Hospital Norte'])
            ->assertJsonPath('data.paradas.0.estado', RutaParada::ARRIBO)
            // La última posición es la más reciente por hora, no la última del arreglo
            ->assertJsonPath('data.ultima_ubicacion.lat', 19.27);

        $this->assertDatabaseCount('ruta_ubicaciones', 3);
    }

    public function test_lote_invalido_da_422_y_no_guarda_nada(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer);

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/ubicacion", ['lecturas' => [
            ['lat' => 19.29, 'lng' => -99.65, 'registrada_en' => now()->toIso8601String()],
            ['lat' => 95, 'lng' => -99.65],
        ]])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['lecturas.1.lat', 'lecturas.1.registrada_en']]);

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/ubicacion", [])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['lat', 'lng']]);

        $this->assertDatabaseCount('ruta_ubicaciones', 0);
    }

    public function test_con_la_ruta_cerrada_el_gps_no_se_guarda(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer, Ruta::FINALIZADA);

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/ubicacion", ['lat' => 19.29, 'lng' => -99.65])
            ->assertOk()->assertJsonPath('data.activa', false)->assertJsonPath('procesadas', 0);

        $this->assertDatabaseCount('ruta_ubicaciones', 0);
    }

    public function test_llegada_manual(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer);
        $parada = $ruta->paradas[0];

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/paradas/{$parada->id}/llegada", ['lat' => 19.29, 'lng' => -99.65])
            ->assertOk()
            ->assertJsonPath('message', 'Llegada registrada.')
            ->assertJsonPath('data.estado', RutaParada::ARRIBO)
            ->assertJsonPath('data.llegada_origen', 'manual');
    }

    public function test_completar_con_fotos_y_firma_en_base64_cierra_la_orden_de_salida(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer);
        $parada = $ruta->paradas[0];

        $cliente = Customer::create(['nombre' => 'Hospital', 'apellido' => 'Norte']);
        $venta = Venta::create(['folio' => 'V-PRUEBA-2', 'customer_id' => $cliente->id, 'estado' => 'confirmada']);
        $orden = OrdenSalida::create(['folio' => 'OS-PRUEBA-2', 'venta_id' => $venta->id, 'estado' => OrdenSalida::LISTA, 'preparada_en' => now()]);
        $parada->update(['orden_salida_id' => $orden->id]);

        $res = $this->conToken($chofer)->post("/api/v1/chofer/rutas/{$ruta->id}/paradas/{$parada->id}/completar", [
            'receptor_nombre' => 'Dra. López',
            'firma' => $this->firmaBase64,
            'lat' => 19.2901,
            'lng' => -99.6501,
            'fotos' => [UploadedFile::fake()->image('entrega.jpg'), UploadedFile::fake()->image('caja.jpg')],
            'remision' => UploadedFile::fake()->create('remision.pdf', 50, 'application/pdf'),
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Entrega registrada.')
            ->assertJsonPath('data.estado', RutaParada::COMPLETADA)
            ->assertJsonPath('data.receptor_nombre', 'Dra. López')
            ->assertJsonPath('data.orden_salida.estado', OrdenSalida::ENTREGADA)
            ->assertJsonCount(3, 'data.evidencias');

        $this->assertNotNull($res->json('data.firma_url'));
        $this->assertStringStartsWith('http', $res->json('data.evidencias.0.archivo_url'));
        Storage::disk(config('filesystems.fotos_disk', 'public'))->assertExists($parada->fresh()->firma_path);

        $this->assertSame('Dra. López', $orden->fresh()->recibe_nombre);
        $this->assertSame($chofer->id, $orden->fresh()->entregada_por);

        // La siguiente parada queda en camino
        $this->assertSame(RutaParada::EN_CAMINO, $ruta->paradas[1]->fresh()->estado);
    }

    public function test_completar_con_firma_como_archivo_y_ultima_parada_finaliza_la_ruta(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer);
        $ruta->paradas[0]->update(['estado' => RutaParada::COMPLETADA, 'completada_en' => now()]);
        $ultima = $ruta->paradas[1];

        $firma = UploadedFile::fake()->createWithContent('firma.png', base64_decode($this->firmaBase64));

        $this->conToken($chofer)->post("/api/v1/chofer/rutas/{$ruta->id}/paradas/{$ultima->id}/completar", [
            'receptor_nombre' => 'Recepción',
            'firma' => $firma,
            'lat' => 19.25,
            'lng' => -99.60,
            'fotos' => [UploadedFile::fake()->image('entrega.jpg')],
        ])
            ->assertOk()
            ->assertJsonPath('data.ruta.estado', Ruta::FINALIZADA);

        $this->assertNotNull($ultima->fresh()->firma_path);
    }

    public function test_completar_sin_evidencia_da_422_con_lo_que_falta(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer);
        $parada = $ruta->paradas[0];

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/paradas/{$parada->id}/completar", [
            'receptor_nombre' => 'Dra. López',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.evidencia.0', 'Para cerrar la entrega falta: la ubicación (activa el GPS), al menos una foto, la firma.');

        // Una firma que no es imagen se rechaza en vez de guardarse vacía
        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/paradas/{$parada->id}/completar", [
            'firma' => 'esto-no-es-una-imagen',
        ])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['firma']]);

        $this->assertSame(RutaParada::EN_CAMINO, $parada->fresh()->estado);
    }

    public function test_completar_una_parada_ya_cerrada_da_409(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer, Ruta::EN_RUTA, ['exigir_evidencia' => false]);
        $parada = $ruta->paradas[0];
        $parada->update(['estado' => RutaParada::NO_ENTREGADA]);

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/paradas/{$parada->id}/completar", [])
            ->assertStatus(409)->assertJsonPath('message', 'Esta parada ya está cerrada.');
    }

    public function test_no_entregada(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer);
        $parada = $ruta->paradas[0];

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/paradas/{$parada->id}/no-entregada", [])
            ->assertStatus(422)->assertJsonPath('errors.motivo.0', 'Escribe por qué no se pudo entregar.');

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/paradas/{$parada->id}/no-entregada", [
            'motivo' => 'Cliente ausente', 'lat' => 19.29, 'lng' => -99.65,
        ])
            ->assertOk()
            ->assertJsonPath('data.estado', RutaParada::NO_ENTREGADA)
            ->assertJsonPath('data.motivo_no_entrega', 'Cliente ausente')
            ->assertJsonPath('data.ruta.estado', Ruta::EN_RUTA);

        // Fuera de curso ya no se opera
        $programada = $this->ruta($chofer, Ruta::PROGRAMADA);
        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$programada->id}/paradas/{$programada->paradas[0]->id}/no-entregada", ['motivo' => 'x'])
            ->assertStatus(409)->assertJsonPath('message', 'La ruta no está en curso.');
    }

    public function test_reportar_incidencia_con_foto(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer);

        $this->conToken($chofer)->post("/api/v1/chofer/rutas/{$ruta->id}/incidencias", [
            'tipo' => 'falla_mecanica',
            'descripcion' => 'Se ponchó una llanta',
            'ruta_parada_id' => $ruta->paradas[0]->id,
            'foto' => UploadedFile::fake()->image('llanta.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.tipo_label', 'Falla mecánica')
            ->assertJsonPath('data.ruta_parada_id', $ruta->paradas[0]->id)
            ->assertJsonPath('data.resuelta_en', null);

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/incidencias", ['tipo' => 'inventado'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['tipo']]);

        $this->assertDatabaseHas('ruta_eventos', ['ruta_id' => $ruta->id, 'tipo' => 'incidencia']);
    }

    public function test_cargar_combustible_en_ruta(): void
    {
        $chofer = $this->chofer();
        $ruta = $this->ruta($chofer);

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/combustible", [
            'litros' => 40.5, 'monto' => 950, 'odometro_km' => 120500, 'tanque_lleno' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.litros', 40.5)
            ->assertJsonPath('data.monto', 950)
            ->assertJsonPath('data.ruta_id', $ruta->id)
            ->assertJsonPath('data.vehicle_id', $ruta->vehicle_id)
            ->assertJsonPath('data.fecha', today()->toDateString());

        $this->assertDatabaseHas('ruta_eventos', ['ruta_id' => $ruta->id, 'tipo' => 'combustible', 'user_id' => $chofer->id]);

        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$ruta->id}/combustible", ['litros' => 0])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['litros', 'monto']]);

        $sinVehiculo = $this->ruta($chofer, Ruta::PROGRAMADA, ['vehicle_id' => null]);
        $this->conToken($chofer)->postJson("/api/v1/chofer/rutas/{$sinVehiculo->id}/combustible", ['litros' => 10, 'monto' => 200])
            ->assertStatus(422)->assertJsonPath('message', 'La ruta no tiene vehículo asignado.');
    }
}
