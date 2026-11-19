<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OrdenSalida;
use App\Models\OrdenSalidaEtapa;
use App\Models\User;
use App\Models\Venta;
use App\Notifications\EtapaSalidaActualizada;
use App\Notifications\EtapaSalidaAsignada;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Las tres etapas de la orden de salida: Ingeniería → Empaque → Entrega,
 * cada una con su responsable, checklist, evidencia y firma; avisos al
 * asesor y a los admins; corregir 72 h sin NIP y después con NIP.
 */
class EtapasSalidaTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private const FIRMA = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private User $jefe;
    private User $ingeniero;
    private User $empacador;
    private User $chofer;
    private User $asesor;
    private OrdenSalida $orden;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutDefer();
        config(['services.openai.api_key' => null]);   // checklist general, sin llamar a la IA

        $raiz = storage_path('framework/testing/disks/etapas-salida');
        (new Filesystem)->cleanDirectory($raiz);
        Storage::set('public', Storage::createLocalDriver(['root' => $raiz, 'url' => '/storage']));
        config(['filesystems.fotos_disk' => 'public']);

        $this->jefe = $this->usuarioApi(['salidas.ver', 'salidas.asignar', 'salidas.preparar']);
        $this->ingeniero = $this->usuarioApi(['salidas.ver', 'salidas.preparar']);
        $this->empacador = $this->usuarioApi(['salidas.ver', 'salidas.preparar']);
        $this->chofer = $this->usuarioApi(['salidas.ver', 'salidas.preparar']);
        $this->asesor = $this->usuarioApi(['ventas.ver']);

        $cliente = Customer::create(['nombre' => 'Hospital', 'apellido' => 'Etapas']);
        $venta = Venta::forceCreate([
            'folio' => 'VEN-E-'.uniqid(), 'customer_id' => $cliente->id, 'seller_id' => $this->asesor->id, 'modalidad' => 'contado',
            'aplica_iva' => false, 'subtotal' => 1000, 'descuento_valor' => 0, 'descuento_monto' => 0, 'envio' => 0,
            'iva_monto' => 0, 'valor_a_cuenta' => 0, 'total' => 1000, 'total_contrato' => 1000,
            'num_meses' => 0, 'garantia_meses' => 6, 'estado' => 'borrador',
        ]);
        $this->orden = OrdenSalida::create(['folio' => 'OS-E-'.uniqid(), 'venta_id' => $venta->id, 'estado' => OrdenSalida::PENDIENTE]);
        $this->orden->items()->create(['nombre' => 'TORRE OLYMPUS CV-190', 'cantidad' => 1, 'orden' => 0, 'requiere_emplayado' => true]);
    }

    private function asignarTodas(): void
    {
        $this->actingAs($this->jefe)->post(route('inventory.salidas.etapas.asignar', $this->orden), ['etapas' => [
            'ingenieria' => $this->ingeniero->id, 'empaque' => $this->empacador->id, 'entrega' => $this->chofer->id,
        ]])->assertSessionHasNoErrors();
    }

    private function etapa(string $etapa): OrdenSalidaEtapa
    {
        return $this->orden->etapas()->where('etapa', $etapa)->firstOrFail();
    }

    /** Checklist general contestado todo en "Sí". */
    private function respuestas(string $etapa, User $quien): array
    {
        $this->actingAs($quien)->getJson(route('inventory.salidas.etapas.checklistIa', [$this->orden, $etapa]))->assertOk();

        return collect($this->etapa($etapa)->checklist)->mapWithKeys(fn ($p) => [$p['clave'] => 'si'])->all();
    }

    private function evidencia(string $etapa, User $quien): void
    {
        $this->actingAs($quien)->postJson(route('inventory.salidas.evidencias.foto', $this->orden), [
            'foto' => UploadedFile::fake()->image('etapa.jpg', 40, 40), 'etapa' => $etapa,
        ])->assertCreated();
    }

    private function terminar(string $etapa, User $quien, array $extra = [])
    {
        return $this->actingAs($quien)->post(route('inventory.salidas.etapas.guardar', [$this->orden, $etapa]), [
            'accion' => 'terminar', 'respuestas' => $this->respuestas($etapa, $quien), 'firma' => self::FIRMA,
        ] + $extra);
    }

    public function test_la_orden_trae_tres_etapas_y_asignarlas_avisa_a_cada_responsable(): void
    {
        Notification::fake();

        $this->actingAs($this->jefe)->get(route('inventory.salidas.show', $this->orden))
            ->assertOk()->assertSee('Etapas de la salida')->assertSee('Ingeniería')->assertSee('Guardar responsables');
        $this->assertSame(['ingenieria', 'empaque', 'entrega'], $this->orden->etapas()->pluck('etapa')->all());

        $this->asignarTodas();

        $this->assertSame($this->ingeniero->id, $this->etapa('ingenieria')->asignada_a);
        Notification::assertSentTo($this->ingeniero, EtapaSalidaAsignada::class, fn ($n, $c) => in_array('database', $c, true));
        Notification::assertSentTo($this->chofer, EtapaSalidaAsignada::class);

        $datos = (new EtapaSalidaAsignada($this->etapa('ingenieria')->load('orden.venta', 'asignadaPor')))->toDatabase($this->ingeniero);
        $this->assertStringContainsString('Ingeniería', $datos['titulo']);
        $this->assertSame(route('inventory.salidas.etapas.show', [$this->orden, 'ingenieria']), $datos['url']);

        // Solo quien asigna.
        $this->actingAs($this->ingeniero)->post(route('inventory.salidas.etapas.asignar', $this->orden), ['etapas' => ['empaque' => $this->ingeniero->id]])->assertForbidden();
    }

    public function test_cada_etapa_empieza_cuando_termina_la_anterior_y_solo_la_trabaja_su_responsable(): void
    {
        $this->asignarTodas();

        // Empaque todavía no.
        $this->actingAs($this->empacador)->post(route('inventory.salidas.etapas.guardar', [$this->orden, 'empaque']), ['accion' => 'guardar'])
            ->assertSessionHasErrors('etapa');
        // Otro preparador no puede trabajar Ingeniería (está asignada).
        $this->actingAs($this->chofer)->post(route('inventory.salidas.etapas.guardar', [$this->orden, 'ingenieria']), ['accion' => 'guardar'])
            ->assertSessionHasErrors('etapa');

        $this->actingAs($this->ingeniero)->get(route('inventory.salidas.etapas.show', [$this->orden, 'ingenieria']))
            ->assertOk()->assertSee('Checklist de ingeniería')->assertSee('¿Qué agrega de más?');
    }

    public function test_terminar_pide_checklist_nota_en_los_no_evidencia_y_firma(): void
    {
        $this->asignarTodas();
        $respuestas = $this->respuestas('ingenieria', $this->ingeniero);
        $primera = array_key_first($respuestas);
        $respuestas[$primera] = 'no';

        $this->actingAs($this->ingeniero)->post(route('inventory.salidas.etapas.guardar', [$this->orden, 'ingenieria']), [
            'accion' => 'terminar', 'respuestas' => $respuestas,
        ])->assertSessionHasErrors('etapa');

        $e = $this->etapa('ingenieria');
        $this->assertSame('en_curso', $e->estado);                  // lo contestado se guardó
        $this->assertSame('no', collect($e->checklist)->firstWhere('clave', $primera)['respuesta']);
        $faltan = session('errors')->get('etapa');
        $this->assertTrue(collect($faltan)->contains(fn ($f) => str_contains($f, 'nota')));
        $this->assertTrue(collect($faltan)->contains(fn ($f) => str_contains($f, 'evidencia')));
        $this->assertTrue(collect($faltan)->contains(fn ($f) => str_contains($f, 'firma')));
    }

    public function test_las_tres_etapas_hasta_entregar_con_avisos(): void
    {
        Notification::fake();
        $this->asignarTodas();

        // 1. Ingeniería, con lo que agrega de más.
        $this->evidencia('ingenieria', $this->ingeniero);
        $this->terminar('ingenieria', $this->ingeniero, ['extras' => [['texto' => 'Cable de luz extra', 'cantidad' => 2]], 'notas' => 'Probado al 100'])
            ->assertSessionHasNoErrors()->assertRedirect(route('salidas.seguimiento', [$this->orden, 'recien' => 'ingenieria']));

        $ing = $this->etapa('ingenieria');
        $this->assertTrue($ing->terminada());
        $this->assertSame($this->ingeniero->id, $ing->terminada_por);
        $this->assertSame('CABLE DE LUZ EXTRA', mb_strtoupper($ing->extras[0]['texto']));
        $this->assertNotNull($ing->firma_path);
        Notification::assertSentTo($this->asesor, EtapaSalidaActualizada::class, fn ($n) => $n->accion === 'terminada');
        Notification::assertSentTo($this->empacador, EtapaSalidaActualizada::class, fn ($n) => $n->accion === 'te_toca');

        // 2. Empaque: arranca con las piezas de la orden y lo que agregó ingeniería.
        $this->actingAs($this->empacador)->get(route('inventory.salidas.etapas.show', [$this->orden, 'empaque']))
            ->assertOk()->assertSee('Piezas con las que sale')->assertSee('agregado por ingenier', false);
        $this->evidencia('empaque', $this->empacador);
        $this->terminar('empaque', $this->empacador, ['extras' => [['texto' => 'TORRE OLYMPUS CV-190', 'cantidad' => 1, 'sale' => 1]]])
            ->assertSessionHasNoErrors();

        $this->orden->refresh();
        $this->assertSame(OrdenSalida::LISTA, $this->orden->estado);
        $this->assertSame($this->empacador->id, $this->orden->preparada_por);
        $this->assertTrue($this->orden->items()->first()->completo());

        // 3. Entrega por paquetería.
        $this->evidencia('entrega', $this->chofer);
        $this->terminar('entrega', $this->chofer, ['modalidad' => 'paqueteria', 'paqueteria' => 'DHL', 'guia' => '1234567890'])
            ->assertSessionHasNoErrors();

        $this->orden->refresh();
        $this->assertSame(OrdenSalida::ENTREGADA, $this->orden->estado);
        $this->assertSame($this->chofer->id, $this->orden->entregada_por);
        $this->assertStringContainsString('1234567890', $this->orden->recibe_nombre);
        Notification::assertSentTo($this->asesor, EtapaSalidaActualizada::class, fn ($n) => $n->accion === 'terminada' && $n->etapa->etapa === 'entrega');

        // Seguimiento y expediente para el asesor.
        $this->actingAs($this->asesor)->get(route('salidas.seguimiento', $this->orden))
            ->assertOk()->assertSee('ya se entregó')->assertSee('¡Entregado!')->assertSee('Lo que ha pasado');
        $pdf = $this->actingAs($this->asesor)->get(route('salidas.expediente', $this->orden));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_entrega_en_persona_pide_nombre_y_firma_de_quien_recibe(): void
    {
        $this->asignarTodas();
        foreach (['ingenieria' => $this->ingeniero, 'empaque' => $this->empacador] as $et => $u) {
            $this->evidencia($et, $u);
            $this->terminar($et, $u, $et === 'empaque' ? ['extras' => [['texto' => 'Torre', 'cantidad' => 1, 'sale' => 1]]] : [])->assertSessionHasNoErrors();
        }
        $this->evidencia('entrega', $this->chofer);

        $this->terminar('entrega', $this->chofer, ['modalidad' => 'persona'])->assertSessionHasErrors('etapa');
        $this->terminar('entrega', $this->chofer, ['modalidad' => 'persona', 'recibe_nombre' => 'Dra. Pérez', 'firma_recibe' => self::FIRMA])
            ->assertSessionHasNoErrors();

        $this->orden->refresh();
        $this->assertSame('Dra. Pérez', $this->orden->recibe_nombre);
        $this->assertNotNull($this->orden->firma_recibe_path);
    }

    public function test_corregir_72_horas_sin_nip_y_despues_con_nip(): void
    {
        Notification::fake();
        $this->asignarTodas();
        $this->evidencia('ingenieria', $this->ingeniero);
        $this->terminar('ingenieria', $this->ingeniero)->assertSessionHasNoErrors();
        $e = $this->etapa('ingenieria');

        // Terminada y sin desbloquear: no se guarda.
        $this->actingAs($this->ingeniero)->post(route('inventory.salidas.etapas.guardar', [$this->orden, 'ingenieria']), ['accion' => 'guardar', 'notas' => 'x'])
            ->assertSessionHasErrors('etapa');

        // Dentro de 72 h: basta con pedirlo.
        $this->actingAs($this->ingeniero)->post(route('inventory.salidas.etapas.desbloquear', [$this->orden, 'ingenieria']))->assertSessionHasNoErrors();
        $this->actingAs($this->ingeniero)->post(route('inventory.salidas.etapas.guardar', [$this->orden, 'ingenieria']), ['accion' => 'guardar', 'notas' => 'Se cambió el cable'])
            ->assertSessionHasNoErrors();
        $e->refresh();
        $this->assertSame('Se cambió el cable', $e->notas);
        $this->assertSame($this->ingeniero->id, $e->editada_por);
        Notification::assertSentTo($this->asesor, EtapaSalidaActualizada::class, fn ($n) => $n->accion === 'corregida');

        // Después de 72 h: pide NIP.
        session()->forget('etapas_desbloqueadas');
        $this->travel(73)->hours();
        $this->actingAs($this->ingeniero)->post(route('inventory.salidas.etapas.desbloquear', [$this->orden, 'ingenieria']))
            ->assertSessionHasErrors(['nip' => 'Pasaron más de 72 horas: hace falta tu NIP y no tienes uno. Pídele a un administrador que te lo asigne en Usuarios.']);

        $this->ingeniero->setApprovalPin('4321');
        $this->actingAs($this->ingeniero)->post(route('inventory.salidas.etapas.desbloquear', [$this->orden, 'ingenieria']), ['nip' => '1111'])
            ->assertSessionHasErrors(['nip' => 'NIP incorrecto.']);
        $this->actingAs($this->ingeniero)->post(route('inventory.salidas.etapas.desbloquear', [$this->orden, 'ingenieria']), ['nip' => '4321'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->ingeniero)->post(route('inventory.salidas.etapas.guardar', [$this->orden, 'ingenieria']), ['accion' => 'guardar', 'notas' => 'Con NIP'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Con NIP', $e->fresh()->notas);
    }

    public function test_evidencia_de_etapa_terminada_solo_al_corregirla(): void
    {
        $this->asignarTodas();
        $this->evidencia('ingenieria', $this->ingeniero);
        $this->terminar('ingenieria', $this->ingeniero)->assertSessionHasNoErrors();

        $this->actingAs($this->ingeniero)->postJson(route('inventory.salidas.evidencias.foto', $this->orden), [
            'foto' => UploadedFile::fake()->image('tarde.jpg', 20, 20), 'etapa' => 'ingenieria',
        ])->assertStatus(409);
        $this->assertSame(1, $this->orden->evidencias()->where('etapa', 'ingenieria')->count());
    }

    public function test_solo_un_admin_asigna_el_nip(): void
    {
        $admin = $this->usuarioApi(['usuarios.ver', 'usuarios.editar'], true);
        $otro = $this->usuarioApi(['usuarios.ver', 'usuarios.editar']);

        $this->actingAs($otro)->post(route('admin.users.nip', $this->ingeniero), ['nip' => '1234', 'nip_confirmation' => '1234'])->assertStatus(403);
        $this->assertNull($this->ingeniero->fresh()->approval_pin_hash);

        $this->actingAs($admin)->post(route('admin.users.nip', $this->ingeniero), ['nip' => '12a4', 'nip_confirmation' => '12a4'])->assertSessionHasErrors('nip');
        $this->actingAs($admin)->post(route('admin.users.nip', $this->ingeniero), ['nip' => '1234', 'nip_confirmation' => '9999'])->assertSessionHasErrors('nip');
        $this->actingAs($admin)->post(route('admin.users.nip', $this->ingeniero), ['nip' => '1234', 'nip_confirmation' => '1234'])->assertSessionHasNoErrors();
        $this->assertTrue($this->ingeniero->fresh()->checkApprovalPin('1234'));

        $this->actingAs($admin)->get(route('admin.users.show', $this->ingeniero))->assertOk()->assertSee('NIP de aprobación')->assertSee('Asignado');
        $this->actingAs($otro)->get(route('admin.users.show', $this->ingeniero))->assertOk()->assertDontSee('NIP de aprobación');

        $this->actingAs($admin)->delete(route('admin.users.nip.quitar', $this->ingeniero))->assertSessionHasNoErrors();
        $this->assertNull($this->ingeniero->fresh()->approval_pin_hash);
    }

    public function test_seguimiento_para_el_asesor_y_no_para_cualquiera(): void
    {
        $this->asignarTodas();
        $this->actingAs($this->asesor)->get(route('salidas.seguimiento', $this->orden))
            ->assertOk()->assertSee('va en')->assertSee('ingeniería')->assertSee('Esperando al ingeniero');

        $nadie = $this->usuarioApi(['ventas.ver']);
        $this->actingAs($nadie)->get(route('salidas.seguimiento', $this->orden))->assertForbidden();
        $this->actingAs($nadie)->get(route('salidas.expediente', $this->orden))->assertForbidden();
    }
}
