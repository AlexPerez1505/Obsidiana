<?php

namespace Tests\Feature;

use App\Models\Cobro;
use App\Models\CobroSolicitud;
use App\Models\Customer;
use App\Models\Paquete;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Pagos por aprobar: el vendedor sube el comprobante (o el efectivo) y solo
 * un administrador o alguien con NIP lo aprueba; hasta entonces no cuenta.
 */
class ComprobantesPagoTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private User $vendedor;

    private User $cajera;

    private Venta $venta;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->vendedor = $this->usuarioApi(['ventas.ver', 'ventas.crear', 'cobranza.ver', 'cobranza.comprobantes', 'cobranza.registrar']);
        $this->cajera = $this->usuarioApi(['ventas.ver', 'cobranza.ver', 'cobranza.registrar', 'ventas.ver_todas']);
        $this->cajera->setApprovalPin('2468');

        $cliente = Customer::create(['nombre' => 'Hospital', 'apellido' => 'Pagos', 'asesor_id' => $this->vendedor->id]);
        $kit = Paquete::create(['nombre' => 'Kit', 'precio' => 10000, 'activo' => true]);
        $this->actingAs($this->vendedor)->post(route('commercial.ventas.store'), [
            'customer_id' => $cliente->id, 'modalidad' => 'contado', 'garantia_meses' => 6,
            'items' => [['tipo_item' => 'paquete', 'paquete_id' => $kit->id, 'nombre' => 'Kit', 'cantidad' => 1, 'precio_unitario' => 10000]],
        ])->assertSessionHasNoErrors();
        $this->venta = Venta::latest('id')->firstOrFail();
    }

    private function subir(array $extra = [])
    {
        return $this->actingAs($this->vendedor)->post(route('commercial.comprobantes.store', $this->venta), $extra + [
            'monto' => 4000, 'fecha' => now()->toDateString(), 'metodo' => 'transferencia', 'referencia' => 'SPEI 123',
            'archivos' => [UploadedFile::fake()->image('comprobante.jpg')],
        ]);
    }

    public function test_el_vendedor_sube_y_no_cuenta_hasta_aprobarse(): void
    {
        $this->subir()->assertSessionHasNoErrors();

        $s = CobroSolicitud::firstOrFail();
        $this->assertSame('pendiente', $s->estado);
        $this->assertCount(1, $s->archivos);
        $this->assertSame(0, Cobro::where('venta_id', $this->venta->id)->count());
        $this->assertEquals(10000, $this->venta->fresh()->saldo());
    }

    public function test_transferencia_sin_comprobante_no_se_acepta_pero_efectivo_si(): void
    {
        $this->subir(['archivos' => []])->assertSessionHasErrorsIn('comprobante', 'archivos');
        $this->subir(['metodo' => 'efectivo', 'archivos' => []])->assertSessionHasNoErrors();
        $this->assertSame('efectivo', CobroSolicitud::firstOrFail()->metodo);
    }

    public function test_no_se_puede_subir_mas_de_lo_que_falta(): void
    {
        $this->subir(['monto' => 8000])->assertSessionHasNoErrors();
        $this->subir(['monto' => 3000])->assertSessionHasErrors('monto'); // 8,000 ya por aprobar + 3,000 > 10,000
    }

    public function test_solo_con_nip_o_admin_se_aprueba_y_crea_el_cobro(): void
    {
        $this->subir();
        $s = CobroSolicitud::firstOrFail();

        // El vendedor (sin NIP) no puede aprobar su propio pago.
        $this->actingAs($this->vendedor)->post(route('commercial.comprobantes.aprobar', $s), ['nip' => '2468'])->assertSessionHasErrors('nip');
        // Con NIP equivocado tampoco.
        $this->actingAs($this->cajera)->post(route('commercial.comprobantes.aprobar', $s), ['nip' => '0000'])->assertSessionHasErrors(['nip' => 'NIP incorrecto.']);
        $this->assertSame('pendiente', $s->fresh()->estado);

        $this->actingAs($this->cajera)->post(route('commercial.comprobantes.aprobar', $s), ['nip' => '2468'])->assertSessionHasNoErrors();
        $s->refresh();
        $this->assertSame('aprobado', $s->estado);
        $this->assertSame($this->cajera->id, $s->revisado_por);
        $cobro = Cobro::findOrFail($s->cobro_id);
        $this->assertEquals(4000, (float) $cobro->monto);
        $this->assertSame(1, $cobro->evidencias()->count());
        $this->assertEquals(6000, $this->venta->fresh()->saldo());

        // No se aprueba dos veces.
        $this->actingAs($this->cajera)->post(route('commercial.comprobantes.aprobar', $s), ['nip' => '2468'])->assertSessionHasErrors('nip');
        $this->assertSame(1, Cobro::where('venta_id', $this->venta->id)->count());
    }

    public function test_un_admin_sin_nip_aprueba_con_su_contrasena(): void
    {
        $this->subir();
        $admin = $this->usuarioApi([], true);

        $this->actingAs($admin)->post(route('commercial.comprobantes.aprobar', CobroSolicitud::first()), ['nip' => 'password'])->assertSessionHasNoErrors();
        $this->assertSame('aprobado', CobroSolicitud::first()->estado);
    }

    public function test_rechazar_con_motivo(): void
    {
        $this->subir();
        $s = CobroSolicitud::firstOrFail();

        $this->actingAs($this->vendedor)->post(route('commercial.comprobantes.rechazar', $s), ['motivo' => 'x'])->assertSessionHasErrors('motivo');
        $this->actingAs($this->cajera)->post(route('commercial.comprobantes.rechazar', $s), ['motivo' => 'No aparece en la cuenta'])->assertSessionHasNoErrors();
        $this->assertSame('rechazado', $s->fresh()->estado);
        $this->assertSame(0, Cobro::where('venta_id', $this->venta->id)->count());

        $this->actingAs($this->vendedor)->get(route('commercial.ventas.show', $this->venta))->assertOk()->assertSee('No aparece en la cuenta');
    }

    public function test_registrar_cobro_directo_solo_con_nip_o_admin(): void
    {
        $datos = ['monto' => 1000, 'fecha' => now()->toDateString(), 'metodo' => 'efectivo'];

        $this->actingAs($this->vendedor)->post(route('commercial.ventas.cobros.store', $this->venta), $datos)->assertSessionHasErrors('monto');
        $this->assertSame(0, Cobro::count());

        $this->actingAs($this->cajera)->post(route('commercial.ventas.cobros.store', $this->venta), $datos)->assertSessionHasNoErrors();
        $this->assertSame(1, Cobro::where('venta_id', $this->venta->id)->count());
    }

    public function test_al_subir_avisa_por_correo_a_quienes_pueden_aprobar(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $admin = $this->usuarioApi([], true);
        $sinNip = $this->usuarioApi(['ventas.ver', 'ventas.ver_todas', 'cobranza.ver']);
        $baneado = $this->usuarioApi([], true);
        $baneado->forceFill(['status' => 'banned'])->save();

        $this->subir()->assertSessionHasNoErrors();

        $n = \App\Notifications\PagoPorAprobar::class;
        \Illuminate\Support\Facades\Notification::assertSentTo([$admin, $this->cajera], $n,
            fn ($notif, $canales) => in_array('mail', $canales, true) && in_array('database', $canales, true));
        \Illuminate\Support\Facades\Notification::assertNotSentTo([$this->vendedor, $sinNip, $baneado], $n);

        // El correo se arma con los datos del pago y la liga para revisarlo.
        $mail = (new $n(CobroSolicitud::firstOrFail()))->toMail($admin);
        $html = $mail->render();
        $this->assertStringContainsString('$4,000.00', $html);
        $this->assertStringContainsString($this->venta->folio, $html);
        $this->assertStringContainsString('pagos-por-aprobar', $html);
        $this->assertStringContainsString('Pago por aprobar', $mail->envelope()->subject);
    }

    public function test_registrar_lo_que_ya_subio_el_vendedor_se_frena(): void
    {
        $this->subir();
        $datos = ['monto' => 4000, 'fecha' => now()->toDateString(), 'metodo' => 'transferencia'];

        // Mismo monto que el pago subido: no se registra, se pide aprobar ese.
        $this->actingAs($this->cajera)->post(route('commercial.ventas.cobros.store', $this->venta), $datos)
            ->assertSessionHasErrors('monto')->assertSessionHas('cb_duplicado');
        $this->assertSame(0, Cobro::where('venta_id', $this->venta->id)->count());

        // Otro monto pasa normal.
        $this->actingAs($this->cajera)->post(route('commercial.ventas.cobros.store', $this->venta), ['monto' => 1000] + $datos)->assertSessionHasNoErrors();

        // Si de verdad es otro pago del mismo monto, se confirma.
        $this->actingAs($this->cajera)->post(route('commercial.ventas.cobros.store', $this->venta), $datos + ['es_otro_pago' => 1])->assertSessionHasNoErrors();
        $this->assertSame(2, Cobro::where('venta_id', $this->venta->id)->count());
        $this->assertSame('pendiente', CobroSolicitud::first()->estado);
    }

    public function test_ya_lo_registre_liga_sin_cobrar_dos_veces(): void
    {
        $this->subir();
        $s = CobroSolicitud::firstOrFail();
        $this->actingAs($this->cajera)->post(route('commercial.ventas.cobros.store', $this->venta),
            ['monto' => 4000, 'fecha' => now()->toDateString(), 'metodo' => 'transferencia', 'es_otro_pago' => 1]);
        $cobro = Cobro::where('venta_id', $this->venta->id)->firstOrFail();

        // La pantalla ofrece ligarlo.
        $this->actingAs($this->cajera)->get(route('commercial.ventas.cobros.index', $this->venta))->assertOk()->assertSee('Ya lo registré · '.$cobro->folio);

        // El vendedor no puede.
        $this->actingAs($this->vendedor)->post(route('commercial.comprobantes.ligar', $s), ['cobro_id' => $cobro->id])->assertSessionHasErrors('cobro_id');

        $this->actingAs($this->cajera)->post(route('commercial.comprobantes.ligar', $s), ['cobro_id' => $cobro->id])->assertSessionHasNoErrors();
        $s->refresh();
        $this->assertSame('aprobado', $s->estado);
        $this->assertSame($cobro->id, $s->cobro_id);
        $this->assertSame(1, Cobro::where('venta_id', $this->venta->id)->count());
        $this->assertEquals(6000, $this->venta->fresh()->saldo());
        $this->assertSame(1, $cobro->evidencias()->count());   // el comprobante del vendedor pasa al cobro

        // Ya no se puede ligar otra vez ni aprobar.
        $this->actingAs($this->cajera)->post(route('commercial.comprobantes.aprobar', $s), ['nip' => '2468'])->assertSessionHasErrors('nip');
    }

    public function test_no_se_liga_a_un_cobro_de_otro_monto(): void
    {
        $this->subir();
        $this->actingAs($this->cajera)->post(route('commercial.ventas.cobros.store', $this->venta),
            ['monto' => 1500, 'fecha' => now()->toDateString(), 'metodo' => 'efectivo']);

        $this->actingAs($this->cajera)->post(route('commercial.comprobantes.ligar', CobroSolicitud::first()), ['cobro_id' => Cobro::first()->id])
            ->assertSessionHasErrors('cobro_id');
        $this->assertSame('pendiente', CobroSolicitud::first()->estado);
    }

    public function test_un_solo_boton_y_una_sola_ventana_por_persona(): void
    {
        $this->subir();
        $cuenta = fn (string $html, string $t) => substr_count($html, $t);

        // Vendedor: un solo "Registrar pago" (el que queda por aprobar), sin el de cobro directo.
        $html = $this->actingAs($this->vendedor)->get(route('commercial.ventas.cobros.index', $this->venta))->assertOk()->getContent();
        $this->assertSame(1, $cuenta($html, 'data-cp-abrir="cpSubir"'));
        $this->assertStringNotContainsString('data-cobrar>Registrar pago', $html);
        $this->assertStringNotContainsString('Subir pago', $html);

        // Quien aprueba: solo el de cobro directo; lo subido se aprueba dentro de esa misma ventana.
        $html = $this->actingAs($this->cajera)->get(route('commercial.ventas.cobros.index', $this->venta))->assertOk()->getContent();
        $this->assertStringNotContainsString('data-cp-abrir="cpSubir"', $html);
        $this->assertSame(1, $cuenta($html, 'data-cobrar>Registrar pago'));
        $this->assertStringContainsString('form="cbAprobar'.CobroSolicitud::first()->id.'"', $html);

        // Desde la venta, quien aprueba va a Cobranza con la ventana abierta.
        $this->actingAs($this->cajera)->get(route('commercial.ventas.show', $this->venta))->assertOk()
            ->assertSee(route('commercial.ventas.cobros.index', $this->venta).'?registrar=1', false);
    }

    public function test_pantallas(): void
    {
        $this->subir();
        $this->actingAs($this->vendedor)->get(route('commercial.ventas.show', $this->venta))->assertOk()->assertSee('Pagos por aprobar')->assertSee('Registrar pago · '.$this->venta->folio)->assertDontSee('Aprobar y registrar cobro');
        $this->actingAs($this->cajera)->get(route('commercial.ventas.cobros.index', $this->venta))->assertOk()->assertSee('Aprobar y registrar cobro');
        $this->actingAs($this->cajera)->get(route('commercial.cobranza.index'))->assertOk()->assertSee('Pagos por aprobar')->assertSee($this->venta->folio);
    }
}
