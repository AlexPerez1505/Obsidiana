<?php

namespace Tests\Feature\Api\V1\Almacen;

use App\Models\PiezaProceso;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\TokenApi;
use App\Services\RutaDeProcesos;
use App\Support\ChecklistProceso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Colas de procesos por la API: la pieza avanza por SU ruta y solo pasa a
 * stock con el checklist completo y una foto.
 */
class ProcesosTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    /** Una pieza con hojalatería y limpieza por delante. */
    private function pieza(array $procesos = ['hojalateria', 'limpieza']): ProductoSerial
    {
        $producto = Producto::create(['tipo_equipo' => 'Carro', 'marca' => 'Medix', 'modelo' => 'C-1', 'stock' => 0]);
        $producto->agregarUnidades(1, ['SN-P1']);

        $pieza = $producto->seriales()->firstOrFail();
        app(RutaDeProcesos::class)->definir($pieza, $procesos);

        return $pieza->refresh();
    }

    private function paso(ProductoSerial $pieza, string $proceso): PiezaProceso
    {
        return $pieza->procesos()->where('proceso', $proceso)->firstOrFail();
    }

    /** Todos los puntos del checklist del paso en "si". */
    private function checklistCompleto(PiezaProceso $paso): array
    {
        return collect(ChecklistProceso::llaves($paso))->mapWithKeys(fn ($l) => [$l => ['r' => 'si']])->all();
    }

    public function test_la_cola_muestra_solo_donde_esta_parada_la_pieza_con_su_resumen(): void
    {
        $pieza = $this->pieza();

        $res = $this->conToken($this->usuarioApi(['procesos.ver']))->getJson('/api/v1/procesos');

        $res->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.proceso', 'hojalateria')
            ->assertJsonPath('data.0.pieza.codigo', $pieza->codigo)
            ->assertJsonPath('data.0.pieza.falta', 'Hojalatería y Limpieza')
            ->assertJsonPath('resumen.en_cola', 1)
            ->assertJsonPath('resumen.en_curso', 0);

        $this->conToken($this->usuarioApi(['procesos.ver']))
            ->getJson('/api/v1/procesos?proceso=limpieza')
            ->assertJsonCount(0, 'data');
    }

    public function test_el_detalle_trae_el_checklist_del_paso(): void
    {
        $paso = $this->paso($this->pieza(), 'hojalateria');

        $this->conToken($this->usuarioApi(['procesos.ver']))
            ->getJson("/api/v1/procesos/{$paso->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $paso->id)
            ->assertJsonPath('checklist.0.seccion', 'proceso')
            ->assertJsonPath('checklist.0.puntos.0.llave', 'sin_golpes')
            ->assertJsonPath('siguientes.0.proceso', 'limpieza');
    }

    public function test_tomar_cerrar_y_avanzar_hasta_stock(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $pieza = $this->pieza();
        $paso = $this->paso($pieza, 'hojalateria');
        $user = $this->usuarioApi(['procesos.ver', 'procesos.trabajar', 'procesos.descartar']);
        $api = $this->conToken($user);

        // Sin tomarla no se cierra.
        $api->post("/api/v1/procesos/{$paso->id}/terminar", [
            'checklist' => $this->checklistCompleto($paso),
            'evidencias' => [UploadedFile::fake()->image('ok.jpg')],
        ])->assertStatus(409);

        $api->postJson("/api/v1/procesos/{$paso->id}/iniciar")
            ->assertOk()
            ->assertJsonPath('data.estado', 'en_curso')
            ->assertJsonPath('data.responsable_id', $user->id);

        // Ya en curso: no se vuelve a tomar.
        $api->postJson("/api/v1/procesos/{$paso->id}/iniciar")->assertStatus(409);

        // Con un punto en "no" no pasa, y no deja la foto en el disco.
        $malo = $this->checklistCompleto($paso);
        $malo['pintura'] = ['r' => 'no', 'nota' => 'Escurrida'];
        $api->post("/api/v1/procesos/{$paso->id}/terminar", [
            'checklist' => $malo,
            'evidencias' => [UploadedFile::fake()->image('mal.jpg')],
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.cierre.0', 'Hay 1 punto(s) que siguen mal. El equipo no puede pasar a stock así.');
        $this->assertSame([], Storage::disk(config('filesystems.fotos_disk', 'public'))->allFiles());

        // Sin foto tampoco.
        $api->postJson("/api/v1/procesos/{$paso->id}/terminar", ['checklist' => $this->checklistCompleto($paso)])
            ->assertStatus(422)
            ->assertJsonPath('errors.cierre.0', 'Falta al menos una foto que demuestre que quedó funcionando.');

        $api->post("/api/v1/procesos/{$paso->id}/terminar", [
            'checklist' => $this->checklistCompleto($paso),
            'evidencias' => [UploadedFile::fake()->image('ok.jpg')],
            'trabajo_realizado' => 'Se enderezó la tapa',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'terminado')
            ->assertJsonPath('data.cerrado_por', $user->id)
            ->assertJsonCount(1, 'data.evidencias_urls')
            ->assertJsonPath('message', "{$pieza->codigo}: Hojalatería terminado. Sigue en Limpieza.");

        $this->assertSame('limpieza', $pieza->fresh()->estado);

        // La limpieza no hacía falta: se descarta con motivo y queda en stock.
        $limpieza = $this->paso($pieza, 'limpieza');
        $api->postJson("/api/v1/procesos/{$limpieza->id}/iniciar")->assertOk();
        $api->postJson("/api/v1/procesos/{$limpieza->id}/omitir", ['motivo' => 'corto'])
            ->assertStatus(422)
            ->assertJsonPath('errors.motivo.0', 'Explica un poco más: esto queda como constancia de la decisión.');
        $api->postJson("/api/v1/procesos/{$limpieza->id}/omitir", ['motivo' => 'Ya venía limpia de hojalatería'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'omitido')
            ->assertJsonPath('message', "{$pieza->codigo}: Limpieza se descartó. Ya está disponible para venta.");

        $this->assertSame('disponible', $pieza->fresh()->estado);
        $this->assertSame(1, $pieza->producto->fresh()->stock);

        // Cerrado: no se reabre.
        $api->postJson("/api/v1/procesos/{$paso->id}/iniciar")->assertStatus(409);
    }

    public function test_agregar_un_proceso_regresa_la_pieza_a_la_cola(): void
    {
        $pieza = $this->pieza([]);
        $this->assertSame('disponible', $pieza->estado);

        $this->conToken($this->usuarioApi(['procesos.trabajar']))
            ->postJson("/api/v1/procesos/piezas/{$pieza->id}/agregar", ['proceso' => 'mantenimiento'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'mantenimiento')
            ->assertJsonPath('data.procesos.0.motivo', 'Agregado a mano')
            ->assertJsonPath('message', "{$pieza->codigo} se mandó a Mantenimiento.");

        $this->conToken($this->usuarioApi(['procesos.trabajar']))
            ->postJson("/api/v1/procesos/piezas/{$pieza->id}/agregar", ['proceso' => 'pintura'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['proceso']]);
    }

    public function test_definir_la_ruta_conserva_lo_hecho_y_no_aplica_a_vendidas(): void
    {
        $pieza = $this->pieza(['mantenimiento', 'limpieza']);
        $api = $this->conToken($this->usuarioApi(['inventario.registrar']));

        $api->putJson("/api/v1/procesos/piezas/{$pieza->id}/definir", ['procesos' => ['hojalateria', 'mantenimiento']])
            ->assertOk()
            ->assertJsonPath('data.estado', 'hojalateria')
            ->assertJsonCount(2, 'data.procesos')
            ->assertJsonPath('message', "{$pieza->codigo}: procesos actualizados. Ahora falta: Hojalatería y Mantenimiento.");

        $this->assertSame(
            'Corrección de la ruta tras la entrada',
            $this->paso($pieza, 'hojalateria')->motivo
        );

        $pieza->forceFill(['vendido' => true])->save();
        $api->putJson("/api/v1/procesos/piezas/{$pieza->id}/definir", ['procesos' => []])
            ->assertStatus(409)
            ->assertJsonPath('message', 'No se pueden cambiar los procesos: la pieza ya se vendió o se dio de baja.');
    }

    /** La web usa la misma lógica que se sacó del controlador. */
    public function test_la_web_sigue_igual_tras_mover_la_logica(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $admin = $this->usuarioApi([], true);
        $pieza = $this->pieza(['hojalateria']);
        $paso = $this->paso($pieza, 'hojalateria');

        $this->actingAs($admin)->get(route('inventory.procesos.index'))->assertOk()->assertSee($pieza->codigo);

        $this->actingAs($admin)->post(route('inventory.procesos.iniciar', $paso))->assertSessionHas('status');

        $this->actingAs($admin)
            ->from(route('inventory.procesos.show', $paso))
            ->post(route('inventory.procesos.terminar', $paso), ['checklist' => $this->checklistCompleto($paso)])
            ->assertRedirect(route('inventory.procesos.show', $paso))
            ->assertSessionHasErrors(['cierre' => 'Falta al menos una foto que demuestre que quedó funcionando.']);

        $this->actingAs($admin)->post(route('inventory.procesos.terminar', $paso), [
            'checklist' => $this->checklistCompleto($paso),
            'evidencias' => [UploadedFile::fake()->image('ok.jpg')],
        ])
            ->assertRedirect(route('inventory.procesos.index'))
            ->assertSessionHas('status', "{$pieza->codigo}: Hojalatería terminado. Ya está disponible para venta.");

        $this->actingAs($admin)
            ->put(route('inventory.procesos.definir', $pieza), ['procesos' => ['limpieza']])
            ->assertSessionHas('status', "{$pieza->codigo}: procesos actualizados. Ahora falta: Limpieza.");
    }

    public function test_permisos(): void
    {
        $paso = $this->paso($this->pieza(), 'hojalateria');

        $this->conToken($this->usuarioApi())->getJson('/api/v1/procesos')->assertForbidden();

        // Ver no alcanza para tomar ni descartar.
        $soloVer = $this->conToken($this->usuarioApi(['procesos.ver']));
        $soloVer->postJson("/api/v1/procesos/{$paso->id}/iniciar")->assertForbidden();

        $trabajador = $this->usuarioApi(['procesos.ver', 'procesos.trabajar']);
        $this->conToken($trabajador)->postJson("/api/v1/procesos/{$paso->id}/omitir", ['motivo' => 'No hacía falta para nada'])
            ->assertForbidden();

        // La ruta completa pide inventario.registrar, no procesos.trabajar.
        $this->conToken($trabajador)
            ->putJson("/api/v1/procesos/piezas/{$paso->producto_serial_id}/definir", ['procesos' => []])
            ->assertForbidden();

        // Llave recortada a lectura.
        $llave = $this->conToken($trabajador, ['procesos.ver'], TokenApi::TIPO_INTEGRACION);
        $llave->getJson('/api/v1/procesos')->assertOk();
        $llave->postJson("/api/v1/procesos/{$paso->id}/iniciar")->assertForbidden();

        $this->assertSame('pendiente', $paso->fresh()->estado);
    }
}
