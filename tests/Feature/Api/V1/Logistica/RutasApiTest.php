<?php

namespace Tests\Feature\Api\V1\Logistica;

use App\Models\Customer;
use App\Models\OrdenSalida;
use App\Models\Ruta;
use App\Models\RutaParada;
use App\Models\TokenApi;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Rutas desde la oficina por la API: mismas reglas y permisos que la web.
 */
class RutasApiTest extends TestCase
{
    use DatosDeLogistica, RefreshDatabase, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();

        // Sin llave: el orden se calcula en local y no se llama a Google
        config(['logistica.google_key' => null]);
        Http::preventStrayRequests();
    }

    public function test_crear_ver_editar_y_borrar_una_ruta(): void
    {
        $user = $this->usuarioApi(['rutas.ver', 'rutas.crear', 'rutas.editar', 'rutas.eliminar']);
        $chofer = $this->usuarioApi();
        $vehiculo = $this->vehiculo();

        // Sin chofer ni vehículo queda en borrador
        $creada = $this->conToken($user)->postJson('/api/v1/rutas', $this->datosRuta())
            ->assertCreated()
            ->assertJsonPath('data.estado', Ruta::BORRADOR)
            ->assertJsonPath('data.hora_salida', '08:30')
            ->assertJsonCount(3, 'data.paradas')
            ->assertJsonPath('data.paradas.0.items.0.cantidad', 2)
            ->assertJsonPath('data.paradas.0.items.0.descripcion', 'Monitor Philips')
            ->assertJsonPath('data.paradas.0.items.1.cantidad', 3)
            ->assertJsonPath('data.paradas.1.ventana_hasta', '13:00')
            ->assertJsonPath('data.paradas.1.urgente', true);

        $id = $creada->json('data.id');
        $this->assertStringStartsWith('RUTA-', $creada->json('data.folio'));
        $this->assertDatabaseHas('ruta_eventos', ['ruta_id' => $id, 'tipo' => 'creada', 'user_id' => $user->id]);

        $this->conToken($user)->getJson("/api/v1/rutas/{$id}")
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.avance.total', 3)
            ->assertJsonStructure(['data' => ['paradas', 'eventos', 'desviaciones', 'incidencias', 'trazo_planeado', 'trazo_real']]);

        // Con chofer y vehículo pasa a programada; las paradas se reemplazan
        $this->conToken($user)->putJson("/api/v1/rutas/{$id}", $this->datosRuta([
            'vehicle_id' => $vehiculo->id,
            'chofer_id' => $chofer->id,
            'paradas' => [['nombre' => 'Solo una', 'lat' => 19.29, 'lng' => -99.65]],
        ]))
            ->assertOk()
            ->assertJsonPath('message', 'Ruta actualizada.')
            ->assertJsonPath('data.estado', Ruta::PROGRAMADA)
            ->assertJsonPath('data.chofer.id', $chofer->id)
            ->assertJsonPath('data.vehicle.plate_number', $vehiculo->plate_number)
            ->assertJsonCount(1, 'data.paradas');

        $this->assertSame(1, RutaParada::where('ruta_id', $id)->count());

        $this->conToken($user)->deleteJson("/api/v1/rutas/{$id}")
            ->assertOk()->assertJsonPath('message', 'Ruta eliminada.');

        $this->assertDatabaseMissing('rutas', ['id' => $id]);
    }

    public function test_listado_filtra_abiertas_por_defecto_y_trae_conteos(): void
    {
        $user = $this->usuarioApi(['rutas.ver']);

        $abierta = $this->ruta(null, Ruta::PROGRAMADA);
        $cerrada = $this->ruta(null, Ruta::FINALIZADA);

        $this->conToken($user)->getJson('/api/v1/rutas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $abierta->id)
            ->assertJsonPath('conteos.abiertas', 1)
            ->assertJsonPath('conteos.finalizada', 1)
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);

        $this->conToken($user)->getJson('/api/v1/rutas?estado=finalizada')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $cerrada->id);

        $this->conToken($user)->getJson('/api/v1/rutas?estado=todas&q='.urlencode('Clínica'))
            ->assertOk()->assertJsonCount(2, 'data');

        $this->conToken($user)->getJson('/api/v1/rutas?estado=inventado')
            ->assertStatus(422)->assertJsonStructure(['errors' => ['estado']]);
    }

    public function test_optimizar_sin_google_usa_el_calculo_local(): void
    {
        $user = $this->usuarioApi(['rutas.crear']);
        $ruta = $this->ruta(null, Ruta::PROGRAMADA);

        $res = $this->conToken($user)->postJson("/api/v1/rutas/{$ruta->id}/optimizar")
            ->assertOk()
            ->assertJsonPath('data.optimizada_con', 'local');

        $this->assertStringStartsWith('Orden de paradas optimizado', $res->json('message'));
        $this->assertGreaterThan(0, $res->json('data.distancia_m'));
        $this->assertNotNull($res->json('data.paradas.0.eta'));
        $this->assertDatabaseHas('ruta_eventos', ['ruta_id' => $ruta->id, 'tipo' => 'optimizada']);
    }

    public function test_optimizar_reglas_dan_409_con_el_mensaje_de_la_web(): void
    {
        $user = $this->usuarioApi(['rutas.crear']);

        // En curso sin posición del vehículo
        $enCurso = $this->ruta(null, Ruta::EN_RUTA);
        $this->conToken($user)->postJson("/api/v1/rutas/{$enCurso->id}/optimizar")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Todavía no hay posición del vehículo para reoptimizar.')
            ->assertJsonPath('codigo', 'conflicto');

        // Paradas sin coordenadas
        $sinUbicar = $this->ruta(null, Ruta::PROGRAMADA);
        $sinUbicar->paradas()->first()->update(['lat' => null, 'lng' => null]);
        $this->conToken($user)->postJson("/api/v1/rutas/{$sinUbicar->id}/optimizar")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Todas las paradas necesitan ubicación en el mapa antes de optimizar.');
    }

    public function test_iniciar_finalizar_y_cancelar(): void
    {
        $user = $this->usuarioApi(['rutas.operar']);
        $chofer = $this->usuarioApi();
        $ruta = $this->ruta($chofer, Ruta::PROGRAMADA);

        $this->conToken($user)->postJson("/api/v1/rutas/{$ruta->id}/iniciar")
            ->assertOk()
            ->assertJsonPath('data.estado', Ruta::EN_RUTA)
            ->assertJsonPath('data.paradas.0.estado', RutaParada::EN_CAMINO);

        // Ya no está programada
        $this->conToken($user)->postJson("/api/v1/rutas/{$ruta->id}/iniciar")
            ->assertStatus(409)->assertJsonPath('message', 'Solo se puede iniciar una ruta programada.');

        $this->conToken($user)->postJson("/api/v1/rutas/{$ruta->id}/finalizar")
            ->assertOk()->assertJsonPath('data.estado', Ruta::FINALIZADA)->assertJsonPath('data.km_recorridos', 0);

        $this->conToken($user)->postJson("/api/v1/rutas/{$ruta->id}/cancelar")
            ->assertStatus(409)->assertJsonPath('message', 'La ruta ya está cerrada.');

        $otra = $this->ruta($chofer, Ruta::BORRADOR);
        $this->conToken($user)->postJson("/api/v1/rutas/{$otra->id}/cancelar")
            ->assertOk()->assertJsonPath('data.estado', Ruta::CANCELADA)->assertJsonPath('message', 'Ruta cancelada.');
    }

    public function test_iniciar_sin_chofer_da_409(): void
    {
        $user = $this->usuarioApi(['rutas.operar']);
        $ruta = $this->ruta(null, Ruta::PROGRAMADA);

        $this->conToken($user)->postJson("/api/v1/rutas/{$ruta->id}/iniciar")
            ->assertStatus(409)->assertJsonPath('message', 'Asigna chofer y vehículo antes de iniciar.');
    }

    public function test_una_ruta_en_curso_no_se_edita_ni_se_borra(): void
    {
        $user = $this->usuarioApi(['rutas.editar', 'rutas.eliminar']);
        $ruta = $this->ruta($this->usuarioApi(), Ruta::EN_RUTA);

        $this->conToken($user)->putJson("/api/v1/rutas/{$ruta->id}", $this->datosRuta())
            ->assertStatus(409)->assertJsonPath('message', 'Una ruta en curso o cerrada ya no se edita.');

        $this->conToken($user)->deleteJson("/api/v1/rutas/{$ruta->id}")
            ->assertStatus(409)->assertJsonPath('message', 'No se puede eliminar una ruta en curso. Finalízala o cancélala primero.');

        $this->assertDatabaseHas('rutas', ['id' => $ruta->id]);
    }

    public function test_validacion_y_choque_de_chofer_dan_422(): void
    {
        $user = $this->usuarioApi(['rutas.crear']);

        $this->conToken($user)->postJson('/api/v1/rutas', ['paradas' => []])
            ->assertStatus(422)
            ->assertJsonPath('errors.paradas.0', 'Agrega al menos una parada.');

        $this->conToken($user)->postJson('/api/v1/rutas', ['paradas' => [['lat' => 200]]])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['paradas.0.nombre', 'paradas.0.lat']]);

        // El chofer ya va en una ruta en curso
        $chofer = $this->usuarioApi();
        $this->ruta($chofer, Ruta::EN_RUTA);

        $this->conToken($user)->postJson('/api/v1/rutas', $this->datosRuta(['chofer_id' => $chofer->id]))
            ->assertStatus(422)
            ->assertJsonPath('errors.chofer_id.0', 'Ese chofer ya va en una ruta en curso.');
    }

    public function test_en_vivo_trae_posicion_y_siguiente_parada(): void
    {
        $user = $this->usuarioApi(['rutas.ver']);
        $ruta = $this->ruta($this->usuarioApi());
        $ruta->ubicaciones()->create(['vehicle_id' => $ruta->vehicle_id, 'lat' => 19.285, 'lng' => -99.653, 'velocidad_kmh' => 32.4, 'registrada_en' => now()->subSeconds(20)]);

        $this->conToken($user)->getJson("/api/v1/rutas/{$ruta->id}/en-vivo")
            ->assertOk()
            ->assertJsonPath('data.activa', true)
            ->assertJsonPath('data.ultima_ubicacion.lat', 19.285)
            ->assertJsonPath('data.ultima_ubicacion.velocidad_kmh', 32.4)
            ->assertJsonPath('data.siguiente_parada.nombre', 'Hospital Norte')
            ->assertJsonCount(2, 'data.paradas');
    }

    public function test_catalogos_ordenes_listas_y_armar_ruta_con_ellas(): void
    {
        $user = $this->usuarioApi(['rutas.crear']);
        $vehiculo = $this->vehiculo();

        $cliente = Customer::create(['nombre' => 'Hospital', 'apellido' => 'Ángeles', 'direccion' => 'Av. Juárez 10', 'telefono' => '7221234567']);
        $venta = Venta::create(['folio' => 'V-PRUEBA-1', 'customer_id' => $cliente->id, 'estado' => 'confirmada']);
        $orden = OrdenSalida::create(['folio' => 'OS-PRUEBA-1', 'venta_id' => $venta->id, 'estado' => OrdenSalida::LISTA, 'preparada_en' => now()]);
        $orden->items()->create(['nombre' => 'Torre de laparoscopía', 'cantidad' => 1, 'orden' => 1]);

        $this->conToken($user)->getJson('/api/v1/rutas/catalogos')
            ->assertOk()
            ->assertJsonPath('data.google_activo', false)
            ->assertJsonPath('data.vehiculos.0.id', $vehiculo->id)
            ->assertJsonPath('data.ordenes.0.folio', 'OS-PRUEBA-1')
            ->assertJsonMissingPath('data.google_key');

        $listas = $this->conToken($user)->getJson('/api/v1/rutas/ordenes-listas')
            ->assertOk()
            ->assertJsonPath('data.0.orden_salida_id', $orden->id)
            ->assertJsonPath('data.0.parada.direccion', 'Av. Juárez 10')
            ->assertJsonPath('data.0.parada.mercancia', '1 Torre de laparoscopía');

        $parada = $listas->json('data.0.parada') + ['lat' => 19.29, 'lng' => -99.65];

        $this->conToken($user)->postJson('/api/v1/rutas', $this->datosRuta(['paradas' => [$parada]]))
            ->assertCreated()
            ->assertJsonPath('data.paradas.0.orden_salida_id', $orden->id)
            ->assertJsonPath('data.paradas.0.customer_id', $cliente->id);

        // Ya va en una ruta abierta: deja de salir como lista
        $this->conToken($user)->getJson('/api/v1/rutas/ordenes-listas')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_geocodificar_usa_openstreetmap_sin_llave(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([
            ['display_name' => 'Toluca, Estado de México', 'lat' => '19.2826', 'lon' => '-99.6557'],
        ])]);

        $user = $this->usuarioApi(['rutas.crear']);

        $this->conToken($user)->getJson('/api/v1/rutas/geocodificar?q='.urlencode('Toluca centro'))
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Toluca, Estado de México')
            ->assertJsonPath('data.0.lat', 19.2826);

        $this->conToken($user)->getJson('/api/v1/rutas/geocodificar')
            ->assertStatus(422)->assertJsonStructure(['errors' => ['q']]);
    }

    public function test_sin_permiso_da_403(): void
    {
        $user = $this->usuarioApi(['rutas.ver']);
        $ruta = $this->ruta(null, Ruta::PROGRAMADA);

        $this->conToken($user)->postJson('/api/v1/rutas', $this->datosRuta())->assertForbidden();
        $this->conToken($user)->putJson("/api/v1/rutas/{$ruta->id}", $this->datosRuta())->assertForbidden();
        $this->conToken($user)->deleteJson("/api/v1/rutas/{$ruta->id}")->assertForbidden();
        $this->conToken($user)->postJson("/api/v1/rutas/{$ruta->id}/iniciar")->assertForbidden();
        $this->conToken($user)->getJson('/api/v1/rutas/catalogos')->assertForbidden();
        $this->conToken($user)->getJson('/api/v1/mapa')->assertForbidden();

        $this->conToken($this->usuarioApi())->getJson('/api/v1/rutas')
            ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');
    }

    public function test_token_recortado_no_pasa_aunque_el_usuario_pueda(): void
    {
        $user = $this->usuarioApi(['rutas.ver', 'rutas.crear']);

        $this->conToken($user, ['rutas.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/rutas')->assertOk();

        $this->conToken($user, ['rutas.ver'], TokenApi::TIPO_INTEGRACION)
            ->postJson('/api/v1/rutas', $this->datosRuta())->assertForbidden();

        // Tampoco los catálogos, que se revisan dentro del controlador
        $this->conToken($user, ['rutas.ver'], TokenApi::TIPO_INTEGRACION)
            ->getJson('/api/v1/rutas/catalogos')->assertForbidden();
    }

    public function test_ruta_inexistente_da_404_en_json(): void
    {
        $user = $this->usuarioApi(['rutas.ver']);

        $this->conToken($user)->getJson('/api/v1/rutas/999999')
            ->assertNotFound()->assertJsonPath('codigo', 'no_encontrado');
    }

    public function test_mapa_en_vivo_trae_rutas_en_curso_y_vehiculos_libres(): void
    {
        $user = $this->usuarioApi(['rutas.mapa']);
        $ruta = $this->ruta($this->usuarioApi());
        $libre = $this->vehiculo();
        $this->ruta(null, Ruta::PROGRAMADA);

        $res = $this->conToken($user)->getJson('/api/v1/mapa')
            ->assertOk()
            ->assertJsonCount(1, 'data.rutas')
            ->assertJsonPath('data.rutas.0.id', $ruta->id)
            ->assertJsonPath('data.rutas.0.vehicle.id', $ruta->vehicle_id)
            ->assertJsonPath('data.rutas.0.ultima_ubicacion', null)
            ->assertJsonPath('data.intervalo_s', 5);

        // El vehículo de la ruta en curso no aparece como libre
        $libres = collect($res->json('data.libres'))->pluck('id');
        $this->assertTrue($libres->contains($libre->id));
        $this->assertFalse($libres->contains($ruta->vehicle_id));
    }
}
