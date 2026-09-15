<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppSender;
use App\Jobs\EnviarConfirmacionPromocionJob;
use App\Jobs\EnviarMensajeCampanaJob;
use App\Models\Campana;
use App\Models\CampanaDestinatario;
use App\Models\Customer;
use App\Models\PromoConfirmacion;
use App\Models\User;
use App\Support\WhatsApp\WhatsAppEnvioResultado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Cubre el flujo completo de promociones: el asesor autoriza, se manda
 * la confirmación, el cliente contesta, y solo entonces puede
 * calificar para una campaña. Nada de esto se puede saltar desde el
 * controlador.
 */
class PromocionesTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioAprobado(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function test_autorizar_registra_quien_y_cuando_sin_confirmar_todavia(): void
    {
        $user = $this->usuarioAprobado();
        $cliente = Customer::create(['nombre' => 'Ana', 'apellido' => 'López', 'telefono' => '5511112222']);

        $this->actingAs($user)
            ->post(route('commercial.promociones.autorizar', $cliente))
            ->assertRedirect();

        $cliente->refresh();
        $this->assertNotNull($cliente->promocion_autorizada_en);
        $this->assertSame($user->id, $cliente->promocion_autorizada_por);
        $this->assertNull($cliente->promocion_confirmada_en);
        $this->assertFalse($cliente->puedeRecibirPromociones());
        $this->assertTrue($cliente->promocionPendienteDeConfirmar());
    }

    public function test_enviar_confirmaciones_encola_un_job_por_cliente_pendiente_y_no_a_los_demas(): void
    {
        Bus::fake();
        $user = $this->usuarioAprobado();

        $pendiente = Customer::create([
            'nombre' => 'Pendiente', 'apellido' => 'Uno', 'telefono' => '5511110001',
            'promocion_autorizada_en' => now(), 'promocion_autorizada_por' => $user->id,
        ]);
        $yaConfirmado = Customer::create([
            'nombre' => 'Confirmado', 'apellido' => 'Dos', 'telefono' => '5511110002',
            'promocion_autorizada_en' => now(), 'promocion_autorizada_por' => $user->id,
            'promocion_confirmada_en' => now(),
        ]);
        $nuncaAutorizado = Customer::create(['nombre' => 'Nadie', 'apellido' => 'Tres', 'telefono' => '5511110003']);

        $this->actingAs($user)
            ->post(route('commercial.promociones.enviarConfirmaciones'))
            ->assertRedirect();

        Bus::assertDispatched(EnviarConfirmacionPromocionJob::class, fn ($job) => $job->clienteId === $pendiente->id);
        Bus::assertNotDispatched(EnviarConfirmacionPromocionJob::class, fn ($job) => $job->clienteId === $yaConfirmado->id);
        Bus::assertNotDispatched(EnviarConfirmacionPromocionJob::class, fn ($job) => $job->clienteId === $nuncaAutorizado->id);
    }

    public function test_no_reenvia_confirmacion_si_ya_se_mando_hace_menos_de_3_dias(): void
    {
        Bus::fake();
        $user = $this->usuarioAprobado();

        $cliente = Customer::create([
            'nombre' => 'Reciente', 'apellido' => 'Uno', 'telefono' => '5511110004',
            'promocion_autorizada_en' => now(), 'promocion_autorizada_por' => $user->id,
        ]);
        PromoConfirmacion::create([
            'cliente_id' => $cliente->id,
            'canal' => 'whatsapp',
            'mensaje_enviado_en' => now()->subDay(),
        ]);

        $this->actingAs($user)->post(route('commercial.promociones.enviarConfirmaciones'));

        Bus::assertNotDispatched(EnviarConfirmacionPromocionJob::class);
    }

    public function test_job_de_confirmacion_manda_por_whatsapp_y_registra_el_intento(): void
    {
        $user = $this->usuarioAprobado();
        $cliente = Customer::create([
            'nombre' => 'Luis', 'apellido' => 'Pérez', 'telefono' => '5511110005',
            'promocion_autorizada_en' => now(), 'promocion_autorizada_por' => $user->id,
        ]);

        $this->mock(WhatsAppSender::class, function ($mock) {
            $mock->shouldReceive('enviar')
                ->once()
                ->andReturn(WhatsAppEnvioResultado::exitoso('wamid-123'));
        });

        (new EnviarConfirmacionPromocionJob($cliente->id))->handle(app(WhatsAppSender::class));

        $this->assertDatabaseHas('promo_confirmaciones', [
            'cliente_id' => $cliente->id,
            'whatsapp_message_id' => 'wamid-123',
        ]);
    }

    public function test_registrar_respuesta_si_confirma_al_cliente(): void
    {
        $user = $this->usuarioAprobado();
        $cliente = Customer::create([
            'nombre' => 'Marta', 'apellido' => 'Ruiz', 'telefono' => '5511110006',
            'promocion_autorizada_en' => now(), 'promocion_autorizada_por' => $user->id,
        ]);
        PromoConfirmacion::create(['cliente_id' => $cliente->id, 'canal' => 'whatsapp', 'mensaje_enviado_en' => now()]);

        $this->actingAs($user)
            ->post(route('commercial.promociones.registrarRespuesta', $cliente), ['respuesta' => 'si'])
            ->assertRedirect();

        $cliente->refresh();
        $this->assertNotNull($cliente->promocion_confirmada_en);
        $this->assertTrue($cliente->puedeRecibirPromociones());
    }

    public function test_registrar_respuesta_no_revoca_al_cliente(): void
    {
        $user = $this->usuarioAprobado();
        $cliente = Customer::create([
            'nombre' => 'Jorge', 'apellido' => 'Díaz', 'telefono' => '5511110007',
            'promocion_autorizada_en' => now(), 'promocion_autorizada_por' => $user->id,
        ]);

        $this->actingAs($user)->post(route('commercial.promociones.registrarRespuesta', $cliente), ['respuesta' => 'no']);

        $cliente->refresh();
        $this->assertNotNull($cliente->promocion_revocada_en);
        $this->assertFalse($cliente->puedeRecibirPromociones());
    }

    public function test_revocar_manualmente_saca_al_cliente_de_futuras_campanas(): void
    {
        $user = $this->usuarioAprobado();
        $cliente = Customer::create([
            'nombre' => 'Sofía', 'apellido' => 'Torres', 'telefono' => '5511110008',
            'promocion_confirmada_en' => now(),
        ]);
        $this->assertTrue($cliente->puedeRecibirPromociones());

        $this->actingAs($user)->post(route('commercial.promociones.revocar', $cliente))->assertRedirect();

        $cliente->refresh();
        $this->assertFalse($cliente->puedeRecibirPromociones());
    }

    public function test_webhook_interpreta_si_y_confirma_sin_sesion(): void
    {
        $user = $this->usuarioAprobado();
        $cliente = Customer::create([
            'nombre' => 'Pablo', 'apellido' => 'Vega', 'telefono' => '5511110009',
            'promocion_autorizada_en' => now(), 'promocion_autorizada_por' => $user->id,
        ]);

        $response = $this->postJson(route('webhooks.whatsapp'), [
            'telefono' => '5511110009',
            'texto' => 'Si',
        ]);

        $response->assertOk()->assertJson(['procesado' => true, 'respuesta' => 'si']);

        $cliente->refresh();
        $this->assertNotNull($cliente->promocion_confirmada_en);
    }

    public function test_crear_campana_la_deja_como_borrador(): void
    {
        $user = $this->usuarioAprobado();

        $response = $this->actingAs($user)->post(route('commercial.promociones.campanas.store'), [
            'nombre' => 'Promo de prueba',
            'mensaje' => 'Hola, tenemos una promoción para ti.',
        ]);

        $this->assertDatabaseHas('campanas', [
            'nombre' => 'Promo de prueba',
            'estado' => Campana::ESTADO_BORRADOR,
        ]);
        $campana = Campana::first();
        $response->assertRedirect(route('commercial.promociones.campanas.show', $campana));
    }

    public function test_lanzar_campana_solo_manda_a_clientes_confirmados_y_encola_por_cada_uno(): void
    {
        Bus::fake();
        $user = $this->usuarioAprobado();

        $confirmado = Customer::create(['nombre' => 'Confirmado', 'apellido' => 'A', 'telefono' => '5511110010', 'promocion_confirmada_en' => now()]);
        Customer::create(['nombre' => 'SinConfirmar', 'apellido' => 'B', 'telefono' => '5511110011']);
        Customer::create(['nombre' => 'Revocado', 'apellido' => 'C', 'telefono' => '5511110012', 'promocion_confirmada_en' => now(), 'promocion_revocada_en' => now()]);

        $campana = Campana::create([
            'nombre' => 'Campaña test', 'mensaje' => 'Hola', 'creado_por' => $user->id, 'estado' => Campana::ESTADO_BORRADOR,
        ]);

        $this->actingAs($user)
            ->post(route('commercial.promociones.campanas.lanzar', $campana))
            ->assertRedirect();

        $campana->refresh();
        $this->assertSame(Campana::ESTADO_EN_COLA, $campana->estado);
        $this->assertSame(1, $campana->destinatarios()->count());
        $this->assertDatabaseHas('campana_destinatarios', ['campana_id' => $campana->id, 'cliente_id' => $confirmado->id]);

        Bus::assertDispatched(EnviarMensajeCampanaJob::class);
    }

    public function test_no_se_puede_lanzar_una_campana_sin_destinatarios_calificados(): void
    {
        $user = $this->usuarioAprobado();
        Customer::create(['nombre' => 'SinConfirmar', 'apellido' => 'B', 'telefono' => '5511110013']);

        $campana = Campana::create([
            'nombre' => 'Campaña vacía', 'mensaje' => 'Hola', 'creado_por' => $user->id, 'estado' => Campana::ESTADO_BORRADOR,
        ]);

        $response = $this->actingAs($user)->post(route('commercial.promociones.campanas.lanzar', $campana));

        $response->assertSessionHasErrors('campana');
        $campana->refresh();
        $this->assertSame(Campana::ESTADO_BORRADOR, $campana->estado);
    }

    public function test_job_de_campana_excluye_a_un_cliente_que_se_dio_de_baja_despues_de_encolarse(): void
    {
        $user = $this->usuarioAprobado();
        $cliente = Customer::create(['nombre' => 'Tardio', 'apellido' => 'X', 'telefono' => '5511110014', 'promocion_confirmada_en' => now()]);

        $campana = Campana::create(['nombre' => 'C', 'mensaje' => 'Hola', 'creado_por' => $user->id, 'estado' => Campana::ESTADO_EN_COLA]);
        $destinatario = CampanaDestinatario::create([
            'campana_id' => $campana->id,
            'cliente_id' => $cliente->id,
            'estado' => CampanaDestinatario::ESTADO_PENDIENTE,
        ]);

        // Se da de baja justo antes de que le toque su turno en la cola.
        $cliente->update(['promocion_revocada_en' => now()]);

        (new EnviarMensajeCampanaJob($destinatario->id))->handle(app(WhatsAppSender::class));

        $destinatario->refresh();
        $this->assertSame(CampanaDestinatario::ESTADO_EXCLUIDO, $destinatario->estado);
    }

    public function test_job_de_campana_marca_enviado_cuando_el_whatsapp_sender_tiene_exito(): void
    {
        $user = $this->usuarioAprobado();
        $cliente = Customer::create(['nombre' => 'Ok', 'apellido' => 'Y', 'telefono' => '5511110015', 'promocion_confirmada_en' => now()]);
        $campana = Campana::create(['nombre' => 'C', 'mensaje' => 'Hola', 'creado_por' => $user->id, 'estado' => Campana::ESTADO_EN_COLA]);
        $destinatario = CampanaDestinatario::create([
            'campana_id' => $campana->id, 'cliente_id' => $cliente->id, 'estado' => CampanaDestinatario::ESTADO_PENDIENTE,
        ]);

        $this->mock(WhatsAppSender::class, function ($mock) {
            $mock->shouldReceive('enviar')->once()->andReturn(WhatsAppEnvioResultado::exitoso('wamid-999'));
        });

        (new EnviarMensajeCampanaJob($destinatario->id))->handle(app(WhatsAppSender::class));

        $destinatario->refresh();
        $this->assertSame(CampanaDestinatario::ESTADO_ENVIADO, $destinatario->estado);
        $this->assertSame('wamid-999', $destinatario->whatsapp_message_id);
    }
}
