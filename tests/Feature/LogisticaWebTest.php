<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OrdenSalida;
use App\Models\Ruta;
use App\Models\RutaParada;
use App\Models\User;
use App\Models\VehiculoCombustible;
use App\Models\VehiculoMantenimiento;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Api\V1\Logistica\DatosDeLogistica;
use Tests\TestCase;

/**
 * Las pantallas de logística después de mover su lógica a servicios
 * compartidos con la API (PlaneacionDeRutas, ControlDeFlota,
 * IndicadoresLogistica, MapaEnVivo): mismas respuestas y mismos efectos.
 */
class LogisticaWebTest extends TestCase
{
    use DatosDeLogistica, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['logistica.google_key' => null]);
        Http::preventStrayRequests();
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
            'email_verified_at' => now(),
        ]);
    }

    private function ordenLista(): OrdenSalida
    {
        $cliente = Customer::create(['nombre' => 'Hospital', 'apellido' => 'Ángeles', 'direccion' => 'Av. Juárez 10']);
        $venta = Venta::create(['folio' => 'V-WEB-'.uniqid(), 'customer_id' => $cliente->id, 'estado' => 'confirmada']);
        $orden = OrdenSalida::create(['folio' => 'OS-WEB-'.substr(uniqid(), -6), 'venta_id' => $venta->id, 'estado' => OrdenSalida::LISTA, 'preparada_en' => now()]);
        $orden->items()->create(['nombre' => 'Torre HD', 'cantidad' => 2, 'orden' => 1]);

        return $orden;
    }

    public function test_alta_desde_ordenes_listas_edicion_y_optimizacion(): void
    {
        $orden = $this->ordenLista();

        $this->actingAs($this->admin)->get(route('logistica.rutas.create', ['listas' => 1]))
            ->assertOk()
            // Las paradas iniciales van en @json dentro del script del formulario
            ->assertSee('"orden_salida_id":'.$orden->id, false)
            ->assertSee('"mercancia":"2 Torre HD"', false);

        // Mercancía vacía con orden ligada: se toma de la orden
        $this->actingAs($this->admin)->post(route('logistica.rutas.store'), $this->datosRuta([
            'paradas' => [
                ['nombre' => 'Hospital Ángeles', 'lat' => 19.29, 'lng' => -99.65, 'orden_salida_id' => $orden->id],
                ['nombre' => 'Clínica', 'lat' => 19.25, 'lng' => -99.60, 'mercancia' => '3 Monitor'],
            ],
        ]))->assertRedirect()->assertSessionHas('status');

        $ruta = Ruta::with('paradas.items')->firstOrFail();
        $this->assertSame(Ruta::BORRADOR, $ruta->estado);
        $this->assertSame('Torre HD', $ruta->paradas[0]->items[0]->descripcion);
        $this->assertSame(2, (int) $ruta->paradas[0]->items[0]->cantidad);
        $this->assertSame($orden->venta->customer_id, $ruta->paradas[0]->customer_id);
        $this->assertSame(3, (int) $ruta->paradas[1]->items[0]->cantidad);

        $this->actingAs($this->admin)->get(route('logistica.rutas.edit', $ruta))->assertOk();
        $this->actingAs($this->admin)->get(route('logistica.rutas.show', $ruta))->assertOk();

        $chofer = User::factory()->create();
        $vehiculo = $this->vehiculo();

        $this->actingAs($this->admin)->put(route('logistica.rutas.update', $ruta), $this->datosRuta([
            'vehicle_id' => $vehiculo->id, 'chofer_id' => $chofer->id,
        ]))->assertRedirect(route('logistica.rutas.show', $ruta));

        $this->assertSame(Ruta::PROGRAMADA, $ruta->fresh()->estado);
        $this->assertSame(3, $ruta->paradas()->count());

        $this->actingAs($this->admin)->from(route('logistica.rutas.show', $ruta))
            ->post(route('logistica.rutas.optimizar', $ruta))
            ->assertRedirect(route('logistica.rutas.show', $ruta))
            ->assertSessionHas('status', fn ($s) => str_starts_with($s, 'Orden de paradas optimizado ('));

        $this->assertSame('local', $ruta->fresh()->optimizada_con);
    }

    public function test_el_choque_de_chofer_regresa_al_formulario(): void
    {
        $chofer = User::factory()->create();
        $this->ruta($chofer, Ruta::EN_RUTA);

        $this->actingAs($this->admin)->from(route('logistica.rutas.create'))
            ->post(route('logistica.rutas.store'), $this->datosRuta(['chofer_id' => $chofer->id]))
            ->assertRedirect(route('logistica.rutas.create'))
            ->assertSessionHasErrors(['chofer_id' => 'Ese chofer ya va en una ruta en curso.']);

        $this->assertSame(1, Ruta::count());
    }

    public function test_optimizar_en_curso_sin_posicion_avisa(): void
    {
        $ruta = $this->ruta(User::factory()->create(), Ruta::EN_RUTA);

        $this->actingAs($this->admin)->from(route('logistica.rutas.show', $ruta))
            ->post(route('logistica.rutas.optimizar', $ruta))
            ->assertSessionHasErrors(['ruta' => 'Todavía no hay posición del vehículo para reoptimizar.']);
    }

    public function test_flota_analytics_y_mapa(): void
    {
        $vehiculo = $this->vehiculo();
        VehiculoMantenimiento::create(['vehicle_id' => $vehiculo->id, 'tipo' => 'verificacion', 'fecha' => today()->subYear(), 'proximo_fecha' => today()->subDays(3)]);

        $this->actingAs($this->admin)->get(route('logistica.flota.index'))
            ->assertOk()->assertSee('vencido hace 3 día(s)');
        $this->actingAs($this->admin)->get(route('logistica.flota.show', $vehiculo))->assertOk();

        $this->actingAs($this->admin)->post(route('logistica.flota.mantenimiento.store', $vehiculo), [
            'tipo' => 'preventivo', 'fecha' => today()->toDateString(), 'costo' => 1200,
        ])->assertSessionHas('status', 'Mantenimiento registrado.');
        $this->assertSame(2, VehiculoMantenimiento::count());

        $this->actingAs($this->admin)->post(route('logistica.flota.combustible.store', $vehiculo), [
            'fecha' => today()->toDateString(), 'litros' => 30, 'monto' => 700,
        ])->assertSessionHas('status', 'Carga de combustible registrada.');

        $carga = VehiculoCombustible::firstOrFail();
        $this->actingAs($this->admin)->delete(route('logistica.flota.combustible.destroy', $carga))
            ->assertSessionHas('status', 'Carga eliminada.');
        $this->assertSame(0, VehiculoCombustible::count());

        $ruta = $this->ruta(User::factory()->create(), Ruta::FINALIZADA, ['km_recorridos' => 12.5]);
        $ruta->paradas[0]->update(['estado' => RutaParada::COMPLETADA, 'completada_en' => now()]);

        $this->actingAs($this->admin)->get(route('logistica.analytics'))->assertOk();

        $enCurso = $this->ruta(User::factory()->create(), Ruta::EN_RUTA);
        $this->actingAs($this->admin)->getJson(route('logistica.mapa.feed'))
            ->assertOk()
            ->assertJsonPath('rutas.0.id', $enCurso->id)
            ->assertJsonPath('rutas.0.siguiente', 'Hospital Norte')
            ->assertJsonStructure(['rutas', 'libres']);
    }

    public function test_combustible_del_chofer_e_incidencia_resuelta(): void
    {
        $chofer = User::factory()->create(['status' => User::STATUS_APPROVED, 'approved_at' => now(), 'email_verified_at' => now(), 'is_admin' => true]);
        $ruta = $this->ruta($chofer, Ruta::EN_RUTA);

        $this->actingAs($chofer)->post(route('logistica.chofer.combustible', $ruta), ['litros' => 25, 'monto' => 600])
            ->assertSessionHas('status', 'Carga de combustible registrada.');

        $this->assertDatabaseHas('vehiculo_combustible', ['ruta_id' => $ruta->id, 'vehicle_id' => $ruta->vehicle_id, 'user_id' => $chofer->id]);
        $this->assertDatabaseHas('ruta_eventos', ['ruta_id' => $ruta->id, 'tipo' => 'combustible', 'user_id' => $chofer->id]);

        $incidencia = $ruta->incidencias()->create(['tipo' => 'retraso']);

        $this->actingAs($this->admin)->post(route('logistica.incidencias.resolver', $incidencia), ['resolucion' => 'Listo'])
            ->assertSessionHas('status', 'Incidencia marcada como resuelta.');

        $this->assertSame($this->admin->id, $incidencia->fresh()->resuelta_por);
        $this->assertDatabaseHas('ruta_eventos', ['ruta_id' => $ruta->id, 'tipo' => 'incidencia_resuelta', 'user_id' => $this->admin->id]);
    }
}
