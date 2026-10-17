<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\Garantia;
use App\Models\GarantiaCosto;
use App\Models\GarantiaReclamacion;
use App\Models\OrdenSalida;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Notifications\AvisoDeGarantia;
use App\Services\Garantias\GeneradorDeGarantias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Módulo de garantías: nacen de las ventas (una por unidad o componente),
 * siguen la entrega, la cancelación y el borrado de la venta, y las
 * reclamaciones respetan sus reglas (evidencia para negar, aprobación de
 * excepciones y gastos, serie anterior y nueva en una sustitución).
 */
class GarantiasTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private User $admin;

    private Customer $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.fotos_disk', 'public'));
        $this->admin = $this->usuarioApi([], true);
        $this->cliente = Customer::create(['nombre' => 'Hospital', 'apellido' => 'Ángeles']);
    }

    private function producto(array $series, string $modelo = 'GIF-H190', string $condicion = 'nuevo'): Producto
    {
        $tipo = EquipmentType::create(['name' => 'Gastroscopio '.uniqid()]);
        $p = Producto::create([
            'equipment_type_id' => $tipo->id, 'tipo_equipo' => $tipo->name,
            'marca' => 'Olympus', 'modelo' => $modelo.'-'.uniqid(), 'precio' => 1000, 'stock' => 0,
        ]);
        $p->agregarUnidades(count($series), $series, null, $condicion);

        return $p->refresh();
    }

    private function vender(array $items, int $meses = 12): Venta
    {
        $this->actingAs($this->admin)->post(route('commercial.ventas.store'), [
            'customer_id' => $this->cliente->id,
            'modalidad' => 'contado',
            'garantia_meses' => $meses,
            'items' => $items,
        ])->assertRedirect();

        return Venta::latest('id')->first();
    }

    private function ventaDeProducto(array $series = ['SER-001', 'SER-002'], int $meses = 12): Venta
    {
        $p = $this->producto($series);

        return $this->vender([[
            'tipo_item' => 'producto', 'producto_id' => $p->id, 'nombre' => 'Gastroscopio Olympus',
            'marca' => 'Olympus', 'modelo' => $p->modelo, 'cantidad' => count($series), 'precio_unitario' => 140000,
        ]], $meses);
    }

    /* ======================= Generación desde ventas ======================= */

    public function test_cada_unidad_vendida_genera_su_garantia_con_su_serie(): void
    {
        $venta = $this->ventaDeProducto(['SER-001', 'SER-002'], 12);

        $garantias = Garantia::where('venta_id', $venta->id)->orderBy('no_serie')->get();

        $this->assertCount(2, $garantias);
        $this->assertSame(['SER-001', 'SER-002'], $garantias->pluck('no_serie')->all());
        $this->assertSame($this->cliente->id, $garantias[0]->customer_id);
        $this->assertSame(12, $garantias[0]->meses);
        $this->assertSame('medibuy', $garantias[0]->tipo);
        // Sin entrega firmada se cuenta desde la venta, de forma provisional.
        $this->assertNull($garantias[0]->fecha_entrega);
        $this->assertTrue($garantias[0]->inicio->isToday());
        $this->assertSame(today()->addMonthsNoOverflow(12)->subDay()->toDateString(), $garantias[0]->vence->toDateString());
        $this->assertEquals(140000, (float) $garantias[0]->precio_venta);
        $this->assertSame(1, $garantias[0]->eventos()->where('tipo', 'creada')->count());
    }

    public function test_una_torre_se_desglosa_en_componentes_con_garantia_propia(): void
    {
        $procesador = $this->producto(['PROC-1'], 'CV-190');
        $camara = $this->producto(['CAM-1', 'CAM-2'], 'CH-S190');

        $torre = Paquete::create(['nombre' => 'Torre de laparoscopía']);
        $torre->productos()->sync([$procesador->id => ['cantidad' => 1], $camara->id => ['cantidad' => 2]]);

        $venta = $this->vender([[
            'tipo_item' => 'paquete', 'paquete_id' => $torre->id, 'nombre' => 'Torre de laparoscopía',
            'cantidad' => 1, 'precio_unitario' => 500000,
        ]]);

        $componentes = Garantia::where('venta_id', $venta->id)->get();

        $this->assertCount(3, $componentes);
        $this->assertSame(['Torre de laparoscopía'], $componentes->pluck('grupo')->unique()->values()->all());
        $this->assertEqualsCanonicalizing(['PROC-1', 'CAM-1', 'CAM-2'], $componentes->pluck('no_serie')->all());
    }

    public function test_venta_sin_garantia_no_genera_y_resincronizar_no_duplica(): void
    {
        $sin = $this->ventaDeProducto(['X-1'], 0);
        $this->assertSame(0, Garantia::where('venta_id', $sin->id)->count());

        $con = $this->ventaDeProducto(['Y-1', 'Y-2']);
        $folios = Garantia::where('venta_id', $con->id)->pluck('folio')->sort()->values();

        $r = app(GeneradorDeGarantias::class)->sincronizarTodas();

        $this->assertSame(0, $r['creadas']);
        $this->assertSame($folios->all(), Garantia::where('venta_id', $con->id)->pluck('folio')->sort()->values()->all());
    }

    public function test_la_cobertura_arranca_con_la_entrega_firmada(): void
    {
        $venta = $this->ventaDeProducto(['E-1']);
        $venta->forceFill(['created_at' => now()->subDays(20)])->saveQuietly();

        $g = Garantia::where('venta_id', $venta->id)->first();
        $this->assertNull($g->fecha_entrega);

        // La venta ya trae su orden de salida; al firmarse la entrega, la garantía se recorre.
        $orden = OrdenSalida::where('venta_id', $venta->id)->first()
            ?? OrdenSalida::create(['folio' => 'OS-'.uniqid(), 'venta_id' => $venta->id, 'estado' => OrdenSalida::PENDIENTE]);
        $orden->update(['estado' => OrdenSalida::ENTREGADA, 'entregada_en' => now()->subDays(5)]);

        $g->refresh();
        $this->assertSame(now()->subDays(5)->toDateString(), $g->fecha_entrega->toDateString());
        $this->assertSame(now()->subDays(5)->toDateString(), $g->inicio->toDateString());
    }

    public function test_cancelar_o_borrar_la_venta_anula_sus_garantias_sin_borrarlas(): void
    {
        $venta = $this->ventaDeProducto(['C-1']);
        $venta->update(['estado' => 'cancelada']);

        $g = Garantia::where('venta_id', $venta->id)->first();
        $this->assertSame('anulada', $g->estado);
        $this->assertStringContainsString('canceló', $g->motivo_anulacion);

        $otra = $this->ventaDeProducto(['D-1']);
        $folio = Garantia::where('venta_id', $otra->id)->value('folio');
        $this->actingAs($this->admin)->delete(route('commercial.ventas.destroy', $otra))->assertRedirect();

        $this->assertSame('anulada', Garantia::where('folio', $folio)->value('estado'));
    }

    /* ============================ Reclamaciones ============================ */

    public function test_reclamacion_por_serie_y_sus_reglas_hasta_el_cierre(): void
    {
        config(['garantias.limite_aprobacion' => 5000]);
        $venta = $this->ventaDeProducto(['REC-1']);
        $g = Garantia::where('venta_id', $venta->id)->first();

        $gestor = $this->usuarioApi(['garantias.ver', 'garantias.gestionar', 'garantias.dictaminar']);

        // Se registra con la serie: se liga sola a la garantía.
        $this->actingAs($gestor)->post(route('garantias.reclamaciones.store'), [
            'no_serie' => 'REC-1', 'canal' => 'telefono', 'prioridad' => 'alta',
            'sintomas' => 'La imagen se congela durante el procedimiento.',
        ])->assertRedirect();

        $rec = GarantiaReclamacion::first();
        $this->assertSame($g->id, $rec->garantia_id);
        $this->assertStringStartsWith('REC-', $rec->folio);
        $this->assertNotNull($rec->limite_en);

        // No avanza a reparación sin dictamen.
        $this->post(route('garantias.reclamaciones.etapa', $rec), ['etapa' => 'atencion'])->assertSessionHasErrors('etapa');

        // Negar sin evidencia no se permite.
        $this->post(route('garantias.reclamaciones.dictamen', $rec), [
            'dictamen' => 'no_procede', 'motivo' => 'Daño por líquidos', 'fundamento' => 'Exclusiones de la póliza',
        ])->assertSessionHasErrors('motivo');

        $this->post(route('garantias.reclamaciones.dictamen', $rec), ['dictamen' => 'procede'])->assertSessionHasNoErrors();
        $this->post(route('garantias.reclamaciones.etapa', $rec), ['etapa' => 'atencion'])->assertSessionHasNoErrors();
        $this->assertSame('en_reparacion', $g->fresh()->semaforo());

        // Un gasto arriba del límite espera aprobación y bloquea el cierre.
        $this->post(route('garantias.reclamaciones.costo', $rec), [
            'concepto' => 'refaccion', 'monto' => 8000, 'absorbe' => 'fabricante',
        ])->assertSessionHasNoErrors();
        $costo = GarantiaCosto::first();
        $this->assertSame('pendiente', $costo->estado);

        $this->post(route('garantias.reclamaciones.cerrar', $rec), ['resolucion' => 'reparacion'])->assertSessionHasErrors('resolucion');
        $this->post(route('garantias.reclamaciones.costo.resolver', $costo), ['aprobar' => 1])->assertForbidden();

        $this->actingAs($this->admin)->post(route('garantias.reclamaciones.costo.resolver', $costo), ['aprobar' => 1]);
        $this->assertSame('aprobado', $costo->fresh()->estado);

        // Sustitución definitiva: la garantía cambia de serie y queda el rastro.
        $this->actingAs($gestor)->post(route('garantias.reclamaciones.sustitucion', $rec), [
            'tipo' => 'definitiva', 'equipo' => 'Gastroscopio nuevo', 'no_serie_entregado' => 'REC-2', 'entregado_en' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame('REC-2', $g->fresh()->no_serie);
        $this->assertTrue($g->eventos()->where('tipo', 'sustitucion')->exists());

        $this->post(route('garantias.reclamaciones.cerrar', $rec), ['resolucion' => 'sustitucion', 'satisfaccion' => 5])
            ->assertSessionHasNoErrors();
        $this->assertSame('cerrada', $rec->fresh()->etapa);
        $this->assertSame('vigente', $g->fresh()->semaforo());
    }

    public function test_negar_con_evidencia_y_aceptar_vencida_solo_con_permiso_de_aprobar(): void
    {
        $venta = $this->ventaDeProducto(['V-1']);
        $g = Garantia::where('venta_id', $venta->id)->first();
        $g->update(['inicio' => now()->subYears(2), 'vence' => now()->subYear(), 'fechas_manual' => true]);

        $gestor = $this->usuarioApi(['garantias.ver', 'garantias.gestionar', 'garantias.dictaminar']);
        $this->actingAs($gestor)->post(route('garantias.reclamaciones.store'), [
            'garantia_id' => $g->id, 'canal' => 'correo', 'prioridad' => 'media', 'sintomas' => 'No enciende la fuente de luz.',
        ]);
        $rec = GarantiaReclamacion::first();

        $this->post(route('garantias.reclamaciones.dictamen', $rec), ['dictamen' => 'procede'])->assertSessionHasErrors('dictamen');

        $this->post(route('garantias.reclamaciones.archivo', $rec), [
            'tipo' => 'evidencia', 'archivos' => [UploadedFile::fake()->image('falla.jpg')],
        ])->assertSessionHasNoErrors();
        $this->post(route('garantias.reclamaciones.dictamen', $rec), [
            'dictamen' => 'no_procede', 'motivo' => 'Garantía vencida', 'fundamento' => 'Vigencia de la póliza '.$g->folio,
        ])->assertSessionHasNoErrors();
        $this->assertSame('no_procede', $rec->fresh()->dictamen);

        $this->actingAs($this->admin)->post(route('garantias.reclamaciones.dictamen', $rec), ['dictamen' => 'procede'])->assertSessionHasNoErrors();
        $this->assertTrue($rec->eventos()->where('tipo', 'aprobacion')->exists());
    }

    public function test_anular_y_corregir_fechas_requieren_aprobar_y_quedan_en_bitacora(): void
    {
        $g = Garantia::where('venta_id', $this->ventaDeProducto(['F-1'])->id)->first();
        $gestor = $this->usuarioApi(['garantias.ver', 'garantias.gestionar']);

        $this->actingAs($gestor)->post(route('garantias.anular', $g), ['motivo' => 'Error de captura'])->assertForbidden();

        $this->actingAs($this->admin)->post(route('garantias.fechas', $g), [
            'inicio' => today()->toDateString(), 'meses' => 36, 'tipo' => 'extendida', 'motivo' => 'Garantía extendida contratada',
        ])->assertSessionHasNoErrors();
        $g->refresh();
        $this->assertSame(36, $g->meses);
        $this->assertTrue($g->fechas_manual);
        $this->assertTrue($g->eventos()->where('tipo', 'fechas')->exists());

        // Con fechas manuales, la sincronización ya no las toca.
        app(GeneradorDeGarantias::class)->sincronizarTodas();
        $this->assertSame(36, $g->fresh()->meses);

        $this->post(route('garantias.anular', $g), ['motivo' => 'Error de captura'])->assertSessionHasNoErrors();
        $this->assertSame('anulada', $g->fresh()->estado);
        $this->assertDatabaseHas('garantias', ['id' => $g->id]);
    }

    /* ======================== Pantallas y portal ========================= */

    public function test_todas_las_pantallas_y_documentos_cargan(): void
    {
        $g = Garantia::where('venta_id', $this->ventaDeProducto(['P-1'])->id)->first();
        $this->actingAs($this->admin)->post(route('garantias.reclamaciones.store'), [
            'garantia_id' => $g->id, 'canal' => 'telefono', 'prioridad' => 'media', 'sintomas' => 'Mancha en la imagen', 'es_seguridad' => 1,
        ]);
        $rec = GarantiaReclamacion::first();
        $this->assertSame('critica', $rec->prioridad);

        foreach ([
            route('garantias.tablero'), route('garantias.reportes'), route('garantias.index'),
            route('garantias.index', ['semaforo' => 'reclamacion']), route('garantias.create'),
            route('garantias.show', $g), route('garantias.serie', 'P-1'), route('garantias.coberturas.index'),
            route('garantias.reclamaciones.index'), route('garantias.reclamaciones.index', ['vista' => 'seguridad']),
            route('garantias.reclamaciones.create', ['garantia' => $g->id]), route('garantias.reclamaciones.show', $rec),
            route('commercial.ventas.show', $g->venta_id),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('garantias.buscar', ['q' => 'P-1']))->assertRedirect(route('garantias.show', $g));
        $this->getJson(route('garantias.reclamaciones.buscar-serie', ['q' => 'P-1']))->assertJsonFragment(['folio' => $g->folio]);

        $this->get(route('garantias.poliza', $g))->assertOk()->assertHeader('content-type', 'application/pdf');
        foreach (['acuse', 'informe', 'acta'] as $tipo) {
            $this->get(route('garantias.reclamaciones.documento', [$rec, $tipo]))->assertOk();
        }
    }

    public function test_el_cliente_consulta_y_reporta_desde_su_portal(): void
    {
        $g = Garantia::where('venta_id', $this->ventaDeProducto(['PORT-1'])->id)->first();

        $this->get(route('publico.garantia', $g->public_token))->assertOk()->assertSee($g->folio)->assertSee('PORT-1');
        $this->get(route('publico.garantia.poliza', $g->public_token))->assertOk();
        $this->get('/consulta/garantia/'.\Illuminate\Support\Str::uuid())->assertNotFound();

        $this->post(route('publico.garantia.reclamar', $g->public_token), [
            'contacto_nombre' => 'Dra. López', 'contacto_telefono' => '5512345678',
            'sintomas' => 'El equipo no enciende desde ayer por la mañana.',
        ])->assertRedirect(route('publico.garantia', $g->public_token));

        $rec = GarantiaReclamacion::first();
        $this->assertSame('portal', $rec->canal);
        $this->assertNull($rec->primera_respuesta_en);
        $this->assertSame($g->id, $rec->garantia_id);
    }

    public function test_sin_permiso_no_se_entra(): void
    {
        $nadie = $this->usuarioApi();
        $this->actingAs($nadie)->get(route('garantias.tablero'))->assertForbidden();

        $lector = $this->usuarioApi(['garantias.ver']);
        $this->actingAs($lector)->get(route('garantias.tablero'))->assertOk();
        $this->actingAs($lector)->get(route('garantias.reclamaciones.create'))->assertForbidden();
        $this->actingAs($lector)->get(route('garantias.coberturas.index'))->assertForbidden();
    }

    public function test_avisos_de_vencimiento_llegan_una_sola_vez(): void
    {
        Notification::fake();
        $g = Garantia::where('venta_id', $this->ventaDeProducto(['AV-1'])->id)->first();
        $g->update(['vence' => today()->addDays(30), 'responsable_id' => $this->admin->id, 'fechas_manual' => true]);

        $this->artisan('garantias:avisar')->assertSuccessful();
        $this->artisan('garantias:avisar')->assertSuccessful();

        Notification::assertSentToTimes($this->admin, AvisoDeGarantia::class, 1);
    }
}
