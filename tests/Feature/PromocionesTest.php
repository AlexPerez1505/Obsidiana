<?php

namespace Tests\Feature;

use App\Jobs\EnviarMensajeCampanaJob;
use App\Models\Campana;
use App\Models\CampanaDestinatario;
use App\Models\CampanaItem;
use App\Models\Cotizacion;
use App\Models\Cupon;
use App\Models\CuponUso;
use App\Models\Customer;
use App\Models\Paquete;
use App\Models\User;
use App\Models\Venta;
use App\Services\Promociones\MotorDeCupones;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Marketing y promociones: reglas de cupones en el servidor, reserva y
 * consumo en cotizaciones/ventas reales, campañas por WhatsApp (cola,
 * reintentos, frecuencia, consentimiento), webhooks y la oferta pública.
 */
class PromocionesTest extends TestCase
{
    // Transacciones (no RefreshDatabase): la base de pruebas se copia del
    // esquema actual porque una migración vieja impide recrearla desde cero.
    use DatabaseTransactions, UsaApi;

    private User $admin;

    private Customer $ana;

    private Customer $beto;

    private Paquete $kit;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'promociones.whatsapp.driver' => 'log',
            'promociones.numeros_prueba' => ['5215511112222'],
            'promociones.solo_numeros_prueba' => false,
            'promociones.whatsapp.app_secret' => 'secreto-de-prueba',
            'promociones.whatsapp.verify_token' => 'verificame',
        ]);

        $this->admin = $this->usuarioApi([], true);
        $this->ana = $this->clienteCon('Ana', '722 100 0001');
        $this->beto = $this->clienteCon('Beto', '722 100 0002');
        // Un paquete sin componentes: tiene precio y no mueve inventario.
        $this->kit = Paquete::create(['nombre' => 'Kit de diagnóstico', 'precio' => 1000, 'activo' => true]);
    }

    private function clienteCon(string $nombre, ?string $tel, bool $consentimiento = true): Customer
    {
        $c = Customer::create(['nombre' => $nombre, 'apellido' => 'Prueba', 'telefono' => $tel, 'activo' => true]);
        if ($consentimiento) {
            Customer::whereKey($c->id)->update(['promocion_confirmada_en' => now()->subDay(), 'recibe_promocion' => true]);
        }

        return $c->refresh();
    }

    private function cupon(array $attrs = []): Cupon
    {
        return Cupon::create($attrs + [
            'codigo' => 'BIENVENIDO10', 'nombre' => 'Bienvenida', 'tipo' => 'general',
            'descuento_tipo' => 'porcentaje', 'descuento_valor' => 10, 'compra_minima' => 1000,
            'tope_descuento' => 300, 'usos_por_cliente' => 1, 'aplica_a' => 'todo', 'estado' => 'activo',
        ]);
    }

    private function items(int $cantidad, float $precio = 1000): array
    {
        return [[
            'tipo_item' => 'paquete', 'paquete_id' => $this->kit->id, 'nombre' => 'Kit de diagnóstico',
            'cantidad' => $cantidad, 'precio_unitario' => $precio,
        ]];
    }

    private function venderCon(Customer $c, ?string $codigo, int $cantidad = 2, array $extra = [])
    {
        return $this->actingAs($this->admin)->post(route('commercial.ventas.store'), $extra + [
            'customer_id' => $c->id, 'modalidad' => 'contado', 'garantia_meses' => 6,
            'cupon_codigo' => $codigo, 'items' => $this->items($cantidad),
        ]);
    }

    private function evaluar(Cupon $cupon, Customer $c, array $items, float $manual = 0)
    {
        return app(MotorDeCupones::class)->evaluar($cupon->fresh('alcances'), $c, $items, $manual);
    }

    /* ======================= Reglas del cupón ======================= */

    public function test_bienvenido10_respeta_compra_minima_y_tope(): void
    {
        $cupon = $this->cupon();

        $r = $this->evaluar($cupon, $this->ana, $this->items(1, 500));
        $this->assertFalse($r->valido());
        $this->assertStringContainsString('compra mínima', $r->mensaje());

        $this->assertEquals(200.0, $this->evaluar($cupon, $this->ana, $this->items(2))->descuento);   // 10 % de 2,000
        $this->assertEquals(300.0, $this->evaluar($cupon, $this->ana, $this->items(5))->descuento);   // tope de 300
        $this->assertEquals(0.0, $this->evaluar($cupon, $this->ana, [['tipo_item' => 'paquete', 'paquete_id' => $this->kit->id, 'cantidad' => 3, 'precio_unitario' => 1000, 'es_regalo' => true]])->descuento);
    }

    public function test_vencido_no_iniciado_y_pausado_no_aplican(): void
    {
        $vencido = $this->cupon(['codigo' => 'VENCIDO', 'vence_en' => now()->subMinute()]);
        $futuro = $this->cupon(['codigo' => 'FUTURO', 'inicia_en' => now()->addDay()]);
        $pausado = $this->cupon(['codigo' => 'PAUSADO', 'estado' => 'pausado']);

        $this->assertStringContainsString('venció', $this->evaluar($vencido, $this->ana, $this->items(2))->mensaje());
        $this->assertStringContainsString('todavía no', $this->evaluar($futuro, $this->ana, $this->items(2))->mensaje());
        $this->assertStringContainsString('pausado', $this->evaluar($pausado, $this->ana, $this->items(2))->mensaje());
        $this->assertSame('vencido', $vencido->estadoReal());
    }

    public function test_cupon_exclusivo_solo_lo_usa_su_cliente(): void
    {
        $cupon = $this->cupon(['codigo' => 'ANA-PERSONAL', 'tipo' => 'exclusivo', 'cliente_id' => $this->ana->id]);

        $this->assertTrue($this->evaluar($cupon, $this->ana, $this->items(2))->valido());
        $r = $this->evaluar($cupon, $this->beto, $this->items(2));
        $this->assertFalse($r->valido());
        $this->assertStringContainsString('otro cliente', $r->mensaje());

        // Y en una venta real tampoco pasa por conocer el código.
        $this->venderCon($this->beto, 'ANA-PERSONAL')->assertSessionHasErrors('cupon_codigo');
        $this->assertSame(0, Venta::count());
    }

    public function test_no_acumulable_con_descuento_manual_y_primera_compra(): void
    {
        $cupon = $this->cupon(['codigo' => 'PRIMERA', 'solo_primera_compra' => true]);

        $this->assertStringContainsString('no se combina', $this->evaluar($cupon, $this->ana, $this->items(2), 50)->mensaje());

        $this->venderCon($this->ana, null)->assertRedirect();
        $this->assertStringContainsString('primera compra', $this->evaluar($cupon, $this->ana, $this->items(2))->mensaje());
        $this->assertTrue($this->evaluar($cupon, $this->beto, $this->items(2))->valido());
    }

    public function test_aplica_solo_a_productos_elegidos(): void
    {
        $otro = Paquete::create(['nombre' => 'Otro', 'precio' => 5000, 'activo' => true]);
        $cupon = $this->cupon(['codigo' => 'SOLOKIT', 'aplica_a' => 'seleccion', 'tope_descuento' => null]);
        $cupon->alcances()->create(['tipo' => 'paquete', 'ref_id' => $this->kit->id]);

        $items = array_merge($this->items(2), [['tipo_item' => 'paquete', 'paquete_id' => $otro->id, 'cantidad' => 1, 'precio_unitario' => 5000]]);
        $r = $this->evaluar($cupon, $this->ana, $items);
        $this->assertEquals(2000.0, $r->subtotalElegible);
        $this->assertEquals(200.0, $r->descuento);
    }

    /* ======================= Venta real: consumo, límites, cancelación ======================= */

    public function test_venta_con_cupon_lo_consume_y_el_servidor_calcula_el_total(): void
    {
        $this->cupon();

        // Aunque el navegador mande otros montos, el servidor recalcula todo.
        $this->venderCon($this->ana, 'bienvenido10', 3, ['total' => 1, 'cupon_descuento' => 99999])->assertRedirect();

        $venta = Venta::firstOrFail();
        $this->assertEquals(3000, (float) $venta->subtotal);
        $this->assertEquals(300, (float) $venta->cupon_descuento);
        $this->assertEquals(2700, (float) $venta->total);
        $this->assertSame('BIENVENIDO10', $venta->cupon_codigo);

        $uso = CuponUso::firstOrFail();
        $this->assertSame(CuponUso::CONSUMIDO, $uso->estado);
        $this->assertSame($venta->id, $uso->venta_id);

        // Un uso por cliente.
        $this->venderCon($this->ana, 'BIENVENIDO10')->assertSessionHasErrors('cupon_codigo');
        $this->assertSame(1, Venta::count());
    }

    public function test_limite_total_de_usos_marca_el_cupon_agotado(): void
    {
        $this->cupon(['usos_max' => 1]);

        $this->venderCon($this->ana, 'BIENVENIDO10')->assertRedirect()->assertSessionHasNoErrors();
        $this->venderCon($this->beto, 'BIENVENIDO10')->assertSessionHasErrors(['cupon_codigo' => 'El cupón ya se agotó.']);
        $this->assertSame('agotado', Cupon::first()->estadoReal());
    }

    public function test_cancelar_la_venta_revierte_el_cupon_y_se_puede_volver_a_usar(): void
    {
        $this->cupon();
        $this->venderCon($this->ana, 'BIENVENIDO10');
        $venta = Venta::firstOrFail();

        $venta->update(['estado' => 'cancelada']);

        $this->assertSame(CuponUso::REVERTIDO, CuponUso::first()->estado);
        $this->venderCon($this->ana, 'BIENVENIDO10')->assertSessionHasNoErrors();
        $this->assertSame(1, CuponUso::where('estado', CuponUso::CONSUMIDO)->count());
    }

    public function test_cotizacion_reserva_y_al_convertirla_en_venta_se_consume_la_misma_reserva(): void
    {
        $this->cupon(['usos_max' => 1]);

        $this->actingAs($this->admin)->post(route('commercial.cotizaciones.store'), [
            'customer_id' => $this->ana->id, 'modalidad' => 'contado', 'cupon_codigo' => 'BIENVENIDO10', 'items' => $this->items(2),
        ])->assertSessionHasNoErrors();

        $cot = Cotizacion::firstOrFail();
        $this->assertEquals(200, (float) $cot->cupon_descuento);
        $this->assertSame(CuponUso::RESERVADO, CuponUso::first()->estado);

        // La reserva aparta el único uso: otro cliente ya no puede.
        $this->venderCon($this->beto, 'BIENVENIDO10')->assertSessionHasErrors('cupon_codigo');

        // Al vender desde la cotización se consume ESA reserva (no se cuenta doble).
        $this->venderCon($this->ana, 'BIENVENIDO10', 2, ['cotizacion' => $cot->id])->assertSessionHasNoErrors();
        $this->assertSame(1, CuponUso::count());
        $this->assertSame(CuponUso::CONSUMIDO, CuponUso::first()->estado);
        $this->assertNotNull(CuponUso::first()->venta_id);
    }

    public function test_la_reserva_vencida_se_libera_y_deja_de_contar(): void
    {
        $this->cupon(['usos_max' => 1]);
        $this->actingAs($this->admin)->post(route('commercial.cotizaciones.store'), [
            'customer_id' => $this->ana->id, 'modalidad' => 'contado', 'cupon_codigo' => 'BIENVENIDO10', 'items' => $this->items(2),
        ]);

        $this->travel(config('promociones.reserva_horas') + 1)->hours();
        $this->artisan('promociones:despachar')->assertSuccessful();

        $this->assertSame(CuponUso::LIBERADO, CuponUso::first()->estado);
        $this->venderCon($this->beto, 'BIENVENIDO10')->assertSessionHasNoErrors();
    }

    public function test_con_el_cupon_bloqueado_el_segundo_comprador_ve_el_uso_del_primero(): void
    {
        // Simula dos compras que llegan juntas: la segunda entra al candado
        // cuando la primera ya registró su uso, y debe verlo.
        $this->cupon(['usos_max' => 1]);
        $motor = app(MotorDeCupones::class);

        DB::transaction(function () use ($motor) {
            $prep = $motor->preparar('BIENVENIDO10', $this->ana->id, $this->items(2), 0);
            CuponUso::create(['cupon_id' => $prep['cupon']->id, 'cliente_id' => $this->ana->id, 'estado' => CuponUso::CONSUMIDO, 'descuento' => 200]);
        });

        try {
            DB::transaction(fn () => $motor->preparar('BIENVENIDO10', $this->beto->id, $this->items(2), 0));
            $this->fail('Debió rechazar el segundo uso.');
        } catch (ValidationException $e) {
            $this->assertSame('El cupón ya se agotó.', $e->errors()['cupon_codigo'][0]);
        }
    }

    /* ======================= Campañas y WhatsApp ======================= */

    private function campana(array $attrs = []): Campana
    {
        $c = Campana::create($attrs + [
            'nombre' => 'Buen Fin', 'titulo_oferta' => '10 % en kits', 'mensaje' => 'Tu kit con 10 % de descuento.',
            'filtros' => ['modo' => 'segmento', 'segmento' => 'todos'], 'creado_por' => $this->admin->id, 'estado' => Campana::BORRADOR,
        ]);
        CampanaItem::create(['campana_id' => $c->id, 'tipo_item' => 'paquete', 'ref_id' => $this->kit->id]);

        return $c;
    }

    public function test_crear_campana_desde_el_panel_queda_en_borrador_con_bitacora(): void
    {
        $this->actingAs($this->admin)->post(route('promociones.campanas.store'), [
            'nombre' => 'Campaña nueva', 'mensaje' => 'Oferta', 'items' => ['paquete-'.$this->kit->id],
            'filtros' => ['modo' => 'segmento', 'segmento' => 'todos'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $c = Campana::firstOrFail();
        $this->assertSame(Campana::BORRADOR, $c->estado);
        $this->assertSame(1, $c->items()->count());
        $this->assertSame('creada', $c->eventos()->first()->accion);
    }

    public function test_audiencia_excluye_sin_consentimiento_bajas_sin_telefono_y_duplicados(): void
    {
        $this->clienteCon('Sin', '722 100 0003', false);
        $this->clienteCon('Repetido', '+52 1 722 100 0001');
        $this->clienteCon('SinTel', null);
        $baja = $this->clienteCon('Baja', '722 100 0004');
        Customer::whereKey($baja->id)->update(['promocion_revocada_en' => now()]);

        $r = app(\App\Services\Promociones\Audiencia::class)->calcular(['modo' => 'segmento', 'segmento' => 'todos']);

        $this->assertEqualsCanonicalizing([$this->ana->id, $this->beto->id], $r['elegibles']->pluck('id')->all());
        $this->assertSame(['sin_consentimiento' => 1, 'baja' => 1, 'sin_telefono' => 1, 'duplicado' => 1], $r['excluidos']);
    }

    public function test_enviar_campana_encola_un_mensaje_por_cliente_y_genera_codigos_personales(): void
    {
        $molde = $this->cupon(['codigo' => 'VIP', 'tipo' => 'exclusivo', 'cliente_id' => null]);
        $c = $this->campana(['cupon_id' => $molde->id, 'cupon_exclusivo' => true]);

        $this->actingAs($this->admin)->post(route('promociones.campanas.enviar', $c), ['cuando' => 'ahora'])->assertSessionHasNoErrors();

        $dest = $c->destinatarios()->get();
        $this->assertCount(2, $dest);
        // Con el driver "log" nada sale: queda simulado, no "enviado".
        $this->assertSame([CampanaDestinatario::SIMULADO], $dest->pluck('estado')->unique()->values()->all());
        $this->assertSame(Campana::COMPLETADA, $c->fresh()->estado);

        $codigos = Cupon::whereNotNull('origen_cupon_id')->get();
        $this->assertCount(2, $codigos);
        $this->assertEqualsCanonicalizing([$this->ana->id, $this->beto->id], $codigos->pluck('cliente_id')->all());
        $this->assertTrue($codigos->every(fn ($k) => $k->campana_id === $c->id && $k->usos_max === 1));

        // No se puede volver a mandar la misma campaña.
        $this->actingAs($this->admin)->post(route('promociones.campanas.enviar', $c), ['cuando' => 'ahora'])->assertSessionHasErrors('campana');
    }

    public function test_programar_y_el_comando_la_lanza_a_su_hora(): void
    {
        $c = $this->campana();
        $this->actingAs($this->admin)->post(route('promociones.campanas.enviar', $c), ['cuando' => 'programar', 'programada_para' => now()->addHour()->format('Y-m-d H:i')])
            ->assertSessionHasNoErrors();
        $this->assertSame(Campana::PROGRAMADA, $c->fresh()->estado);

        $this->artisan('promociones:despachar');
        $this->assertSame(0, $c->destinatarios()->count());

        $this->travel(2)->hours();
        $this->artisan('promociones:despachar');
        $this->assertSame(2, $c->destinatarios()->count());
    }

    public function test_pausar_deja_pendientes_y_cancelar_los_cancela(): void
    {
        $c = $this->campana(['estado' => Campana::PAUSADA, 'iniciada_en' => now()]);
        $d = CampanaDestinatario::create(['campana_id' => $c->id, 'cliente_id' => $this->ana->id, 'clave' => 'c'.$this->ana->id,
            'token' => CampanaDestinatario::nuevoToken(), 'telefono' => '527221000001', 'estado' => 'pendiente']);

        EnviarMensajeCampanaJob::dispatchSync($d->id);
        $this->assertSame('pendiente', $d->fresh()->estado);

        $this->actingAs($this->admin)->post(route('promociones.campanas.cancelar', $c))->assertSessionHasNoErrors();
        $this->assertSame('cancelado', $d->fresh()->estado);
        $this->assertSame(Campana::CANCELADA, $c->fresh()->estado);
    }

    private function destinatarioPendiente(?Campana $c = null, ?Customer $cliente = null): CampanaDestinatario
    {
        $c ??= $this->campana(['estado' => Campana::ENVIANDO, 'iniciada_en' => now()]);
        $cliente ??= $this->ana;

        return CampanaDestinatario::create(['campana_id' => $c->id, 'cliente_id' => $cliente->id, 'clave' => 'c'.$cliente->id,
            'token' => CampanaDestinatario::nuevoToken(), 'telefono' => $cliente->telefonoWhatsapp(), 'estado' => 'pendiente']);
    }

    private function modoMeta(): void
    {
        config(['promociones.whatsapp.driver' => 'meta', 'promociones.whatsapp.token' => 'tok', 'promociones.whatsapp.phone_number_id' => '123']);
    }

    public function test_envio_real_por_la_cloud_api_con_plantilla_variables_y_boton(): void
    {
        $this->modoMeta();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.OK1']]], 200)]);
        $d = $this->destinatarioPendiente();

        EnviarMensajeCampanaJob::dispatchSync($d->id);

        $d->refresh();
        $this->assertSame(CampanaDestinatario::ENVIADO, $d->estado);
        $this->assertSame('wamid.OK1', $d->whatsapp_message_id);

        Http::assertSent(function ($req) use ($d) {
            $t = $req['template'];
            $boton = collect($t['components'])->firstWhere('sub_type', 'url');

            return $req['to'] === '527221000001'
                && $t['name'] === config('promociones.whatsapp.plantilla')
                && collect($t['components'])->firstWhere('type', 'body')['parameters'][0]['text'] === 'Ana'
                && $boton['parameters'][0]['text'] === $d->token
                && $req->hasHeader('Authorization', 'Bearer tok');
        });
    }

    public function test_error_pasajero_se_reintenta_y_error_definitivo_falla_sin_reintentar(): void
    {
        $this->modoMeta();
        Http::fakeSequence('graph.facebook.com/*')
            ->push(['error' => ['code' => 130429, 'message' => 'Rate limit hit']], 400)
            ->push(['error' => ['code' => 131026, 'message' => 'Message undeliverable']], 400);

        $d = $this->destinatarioPendiente();
        EnviarMensajeCampanaJob::dispatchSync($d->id);
        $this->assertSame('pendiente', $d->fresh()->estado);   // queda para reintento
        $this->assertSame('130429', $d->fresh()->error_codigo);

        EnviarMensajeCampanaJob::dispatchSync($d->id);
        $this->assertSame(CampanaDestinatario::FALLO, $d->fresh()->estado);
        $this->assertSame('131026', $d->fresh()->error_codigo);
        Http::assertSentCount(2);
    }

    public function test_no_manda_dos_veces_ni_sin_consentimiento_ni_antes_de_la_frecuencia(): void
    {
        $this->modoMeta();
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

        // Ya lo tomó otro proceso: no se vuelve a mandar.
        $d = $this->destinatarioPendiente();
        $d->update(['estado' => CampanaDestinatario::ENVIANDO]);
        EnviarMensajeCampanaJob::dispatchSync($d->id);
        Http::assertNothingSent();

        // Se dio de baja después de armar la lista.
        $d2 = $this->destinatarioPendiente(null, $this->beto);
        Customer::whereKey($this->beto->id)->update(['promocion_revocada_en' => now()]);
        EnviarMensajeCampanaJob::dispatchSync($d2->id);
        $this->assertSame(CampanaDestinatario::OMITIDO_CONSENTIMIENTO, $d2->fresh()->estado);

        // Recibió otra promoción hace poco.
        $previa = $this->campana(['nombre' => 'Anterior', 'estado' => Campana::COMPLETADA]);
        CampanaDestinatario::create(['campana_id' => $previa->id, 'cliente_id' => $this->ana->id, 'clave' => 'c'.$this->ana->id,
            'estado' => CampanaDestinatario::ENTREGADO, 'enviado_en' => now()->subHours(5)]);
        $d3 = $this->destinatarioPendiente($this->campana(['nombre' => 'Otra', 'estado' => Campana::ENVIANDO]));
        EnviarMensajeCampanaJob::dispatchSync($d3->id);
        $this->assertSame(CampanaDestinatario::OMITIDO_FRECUENCIA, $d3->fresh()->estado);
        Http::assertNothingSent();
    }

    public function test_solo_numeros_de_prueba_simula_a_los_demas(): void
    {
        $this->modoMeta();
        config(['promociones.solo_numeros_prueba' => true]);
        Http::fake();
        $d = $this->destinatarioPendiente();

        EnviarMensajeCampanaJob::dispatchSync($d->id);

        $this->assertSame(CampanaDestinatario::SIMULADO, $d->fresh()->estado);
        Http::assertNothingSent();
    }

    public function test_prueba_solo_a_numeros_autorizados(): void
    {
        $c = $this->campana();

        $this->actingAs($this->admin)->post(route('promociones.campanas.prueba', $c), ['telefono' => '5299999999'])
            ->assertSessionHasErrors('campana');

        $this->actingAs($this->admin)->post(route('promociones.campanas.prueba', $c), ['telefono' => '5215511112222', 'cliente_id' => $this->ana->id])
            ->assertSessionHasNoErrors();

        $p = CampanaDestinatario::where('es_prueba', true)->firstOrFail();
        $this->assertSame('525511112222', $p->telefono); // 52 1 + 10 dígitos se normaliza a 52 + 10
        $this->assertSame($this->ana->id, $p->cliente_id);
        $this->assertNotNull($c->fresh()->prueba_enviada_en);
        // Las pruebas no cuentan en resultados.
        $this->assertSame(0, app(\App\Services\Promociones\Resultados::class)->deCampana($c)['destinatarios']);
    }

    /* ======================= Webhooks ======================= */

    private function webhook(array $payload, ?string $firma = null)
    {
        $cuerpo = json_encode($payload);
        $firma ??= 'sha256='.hash_hmac('sha256', $cuerpo, 'secreto-de-prueba');

        return $this->call('POST', route('webhooks.whatsapp.recibir'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $firma,
        ], $cuerpo);
    }

    private function estados(array $estados): array
    {
        return ['entry' => [['changes' => [['field' => 'messages', 'value' => ['statuses' => $estados]]]]]];
    }

    public function test_webhook_valida_firma_y_verificacion(): void
    {
        $this->webhook($this->estados([]), 'sha256=falsa')->assertForbidden();

        $this->get(route('webhooks.whatsapp.verificar', ['hub.mode' => 'subscribe', 'hub.verify_token' => 'verificame', 'hub.challenge' => '42']))
            ->assertOk()->assertSeeText('42');
        $this->get(route('webhooks.whatsapp.verificar', ['hub.mode' => 'subscribe', 'hub.verify_token' => 'otro', 'hub.challenge' => '42']))
            ->assertForbidden();
    }

    public function test_webhook_actualiza_estados_ignora_duplicados_y_no_retrocede(): void
    {
        $d = $this->destinatarioPendiente();
        $d->update(['estado' => 'enviado', 'whatsapp_message_id' => 'wamid.A', 'enviado_en' => now()]);
        $ts = now()->timestamp;

        $this->webhook($this->estados([['id' => 'wamid.A', 'status' => 'read', 'timestamp' => (string) $ts]]))->assertOk();
        $this->assertSame(CampanaDestinatario::LEIDO, $d->fresh()->estado);

        // Llega tarde el "delivered": no baja de leído.
        $this->webhook($this->estados([['id' => 'wamid.A', 'status' => 'delivered', 'timestamp' => (string) ($ts - 5)]]))->assertOk();
        $this->assertSame(CampanaDestinatario::LEIDO, $d->fresh()->estado);
        $this->assertNotNull($d->fresh()->entregado_en);

        // El mismo aviso repetido solo se registra una vez.
        $this->webhook($this->estados([['id' => 'wamid.A', 'status' => 'read', 'timestamp' => (string) $ts]]))->assertOk();
        $this->assertSame(2, \App\Models\MktWebhookEvento::count());
    }

    public function test_webhook_de_fallo_y_de_baja_por_boton(): void
    {
        $d = $this->destinatarioPendiente();
        $d->update(['estado' => 'enviado', 'whatsapp_message_id' => 'wamid.F']);

        $this->webhook($this->estados([['id' => 'wamid.F', 'status' => 'failed', 'timestamp' => '1',
            'errors' => [['code' => 131050, 'title' => 'User stopped marketing messages']]]]));
        $this->assertSame(CampanaDestinatario::FALLO, $d->fresh()->estado);
        $this->assertSame('131050', $d->fresh()->error_codigo);

        $this->webhook(['entry' => [['changes' => [['value' => ['messages' => [[
            'id' => 'wamid.IN1', 'from' => '5217221000002', 'type' => 'button', 'button' => ['payload' => 'PROMO_BAJA', 'text' => 'Ya no quiero promociones'],
        ]]]]]]]])->assertOk();

        $this->assertFalse($this->beto->fresh()->puedeRecibirPromociones());
        $this->assertTrue($this->ana->fresh()->puedeRecibirPromociones());
    }

    /* ======================= Oferta pública (enlace de WhatsApp) ======================= */

    public function test_flujo_completo_enlace_pedido_con_cupon_venta_y_resultados(): void
    {
        $molde = $this->cupon(['codigo' => 'VIP', 'tipo' => 'exclusivo', 'cliente_id' => null]);
        $c = $this->campana(['cupon_id' => $molde->id, 'cupon_exclusivo' => true]);
        $this->actingAs($this->admin)->post(route('promociones.campanas.enviar', $c), ['cuando' => 'ahora']);
        auth()->logout();

        $d = $c->destinatarios()->where('cliente_id', $this->ana->id)->firstOrFail();

        // 1. Abre el enlace: se cuenta el clic y ve su código.
        $this->get(route('promo.oferta', $d->token))->assertOk()->assertSee($d->cupon->codigo);
        $this->assertSame(1, $d->fresh()->clics);

        // 2. El servidor calcula: solo toma cantidades, no precios.
        $this->postJson(route('promo.calcular', $d->token), ['cantidades' => ['paquete-'.$this->kit->id => 3], 'precio' => 1])
            ->assertOk()->assertJson(['subtotal' => 3000, 'descuento' => 300, 'total' => 2700]);

        // 3. Con dígitos equivocados no confirma (enlace reenviado).
        $this->post(route('promo.confirmar', $d->token), ['cantidades' => ['paquete-'.$this->kit->id => 3], 'ultimos4' => '9999'])
            ->assertSessionHasErrors('ultimos4');
        $this->assertSame(0, Cotizacion::count());

        // 4. Confirma: nace una cotización real con el cupón reservado.
        $this->post(route('promo.confirmar', $d->token), ['cantidades' => ['paquete-'.$this->kit->id => 3], 'ultimos4' => '0001'])
            ->assertRedirect(route('promo.oferta', $d->token));
        $cot = Cotizacion::firstOrFail();
        $this->assertEquals(2700, (float) $cot->total);
        $this->assertSame($c->id, $cot->campana_id);
        $this->assertSame('aceptada', $cot->estado);
        $this->assertSame(CuponUso::RESERVADO, CuponUso::first()->estado);

        // 5. El vendedor la convierte en venta: se consume el cupón y se atribuye a la campaña.
        $this->venderCon($this->ana, $d->cupon->codigo, 3, ['cotizacion' => $cot->id])->assertSessionHasNoErrors();
        $r = app(\App\Services\Promociones\Resultados::class)->deCampana($c);
        $this->assertSame(1, $r['pedidos']);
        $this->assertSame(1, $r['cupones_usados']);
        $this->assertSame(1, $r['ventas']);
        $this->assertEquals(2700, $r['ingresos']);
        $this->assertEquals(300, $r['descontado']);
        $this->assertSame(1, $r['clics_unicos']);

        // 6. El código personal de Ana no le sirve a Beto.
        $this->venderCon($this->beto, $d->cupon->codigo)->assertSessionHasErrors('cupon_codigo');
    }

    public function test_baja_desde_el_enlace(): void
    {
        $c = $this->campana(['estado' => Campana::COMPLETADA]);
        $d = $this->destinatarioPendiente($c);

        $this->get(route('promo.baja', $d->token))->assertOk()->assertSee('Dejar de recibir', false);
        $this->post(route('promo.baja.confirmar', $d->token))->assertRedirect();

        $this->assertFalse($this->ana->fresh()->puedeRecibirPromociones());
        $this->assertSame('enlace', $this->ana->consentimientos()->first()->fuente);
    }

    public function test_token_inexistente_da_404(): void
    {
        $this->get('/p/NOEXISTEESTETOKEN123456789')->assertNotFound();
    }

    /* ======================= Permisos y pantallas ======================= */

    public function test_permisos_del_modulo(): void
    {
        $sinPermiso = $this->usuarioApi([]);
        $this->actingAs($sinPermiso)->get(route('promociones.campanas.index'))->assertForbidden();
        $this->actingAs($sinPermiso)->get(route('promociones.cupones.create'))->assertForbidden();

        $soloVer = $this->usuarioApi(['promociones.ver']);
        $c = $this->campana();
        $this->actingAs($soloVer)->get(route('promociones.campanas.show', $c))->assertOk();
        $this->actingAs($soloVer)->post(route('promociones.campanas.enviar', $c), ['cuando' => 'ahora'])->assertForbidden();
    }

    public function test_las_pantallas_cargan(): void
    {
        $cupon = $this->cupon();
        $c = $this->campana(['cupon_id' => $cupon->id]);
        $this->actingAs($this->admin);

        foreach ([
            route('promociones.inicio'), route('promociones.inicio', ['periodo' => '7d']), route('promociones.campanas.index'),
            route('promociones.campanas.create'), route('promociones.campanas.show', $c), route('promociones.campanas.edit', $c),
            route('promociones.cupones.index'), route('promociones.configuracion'),
            route('commercial.clientes.show', $this->ana), route('commercial.cotizaciones.create'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        // Las pantallas viejas de cupón llevan al panel con el detalle o el asistente abierto.
        $this->get(route('promociones.cupones.show', $cupon))->assertRedirect(route('promociones.cupones.index', ['ver' => $cupon->id]));
        $this->get(route('promociones.cupones.edit', $cupon))->assertRedirect(route('promociones.cupones.index', ['editar' => $cupon->id]));
    }

    /* ======================= Datos del panel (JSON) ======================= */

    public function test_listado_de_cupones_filtra_por_estado_calculado_busca_y_ordena(): void
    {
        $this->cupon();                                                         // activo
        $this->cupon(['codigo' => 'VIEJO', 'vence_en' => now()->subDay()]);   // vencido
        $this->cupon(['codigo' => 'LUEGO', 'inicia_en' => now()->addDay()]);  // programado
        $agotado = $this->cupon(['codigo' => 'UNO', 'usos_max' => 1]);
        CuponUso::create(['cupon_id' => $agotado->id, 'cliente_id' => $this->ana->id, 'estado' => CuponUso::CONSUMIDO, 'descuento' => 10]);
        $this->actingAs($this->admin);

        $r = $this->getJson(route('promociones.datos.cupones'))->assertOk();
        $this->assertSame(['todos' => 4, 'activo' => 1, 'programado' => 1, 'vencido' => 1, 'agotado' => 1, 'pausado' => 0], $r->json('conteos'));

        $this->getJson(route('promociones.datos.cupones', ['estado' => 'agotado']))->assertJsonPath('data.0.codigo', 'UNO')->assertJsonPath('data.0.pct', 100);
        $this->getJson(route('promociones.datos.cupones', ['q' => 'viej']))->assertJsonCount(1, 'data');
        $this->getJson(route('promociones.datos.cupones', ['orden' => 'codigo', 'dir' => 'asc']))->assertJsonPath('data.0.codigo', 'BIENVENIDO10');
        $this->getJson(route('promociones.datos.cupones', ['con_usos' => 1]))->assertJsonPath('total', 1);
        $this->getJson(route('promociones.datos.cupones.show', $agotado))->assertOk()
            ->assertJsonPath('resultados.usados', 1)->assertJsonPath('form.usos_max', 1);
    }

    public function test_crear_por_json_valida_y_responde_errores_por_campo(): void
    {
        $this->actingAs($this->admin);

        $this->postJson(route('promociones.cupones.store'), ['nombre' => 'X', 'tipo' => 'general', 'descuento_tipo' => 'porcentaje',
            'descuento_valor' => 150, 'usos_por_cliente' => 1, 'aplica_a' => 'seleccion', 'estado' => 'activo'])
            ->assertStatus(422)->assertJsonValidationErrors(['descuento_valor', 'alcance']);

        $this->postJson(route('promociones.cupones.store'), ['codigo' => 'nuevo-1', 'nombre' => 'Nuevo', 'tipo' => 'general', 'descuento_tipo' => 'monto',
            'descuento_valor' => 500, 'compra_minima' => 5000, 'usos_por_cliente' => 1, 'aplica_a' => 'seleccion',
            'alcance' => ['paquete-'.$this->kit->id], 'estado' => 'activo', 'acumulable' => true])
            ->assertCreated()->assertJsonPath('codigo', 'NUEVO-1');

        $c = Cupon::where('codigo', 'NUEVO-1')->firstOrFail();
        $this->assertSame(1, $c->alcances()->count());
        $this->assertTrue($c->acumulable);
    }

    public function test_duplicar_y_acciones_en_lote(): void
    {
        $a = $this->cupon();
        $b = $this->cupon(['codigo' => 'OTRO']);
        $this->actingAs($this->admin);

        $r = $this->postJson(route('promociones.cupones.duplicar', $a))->assertOk();
        $copia = Cupon::findOrFail($r->json('id'));
        $this->assertSame('pausado', $copia->estado);
        $this->assertNotSame($a->codigo, $copia->codigo);

        $this->postJson(route('promociones.cupones.lote'), ['ids' => [$a->id, $b->id, $copia->id], 'accion' => 'pausar'])
            ->assertOk()->assertJsonPath('cambiados', 2);
        $this->assertSame(['pausado'], Cupon::pluck('estado')->unique()->values()->all());

        $soloVer = $this->usuarioApi(['promociones.ver']);
        $this->actingAs($soloVer)->postJson(route('promociones.cupones.lote'), ['ids' => [$a->id], 'accion' => 'activar'])->assertForbidden();
    }

    public function test_datos_de_campanas_calendario_y_segmentos(): void
    {
        $c = $this->campana(['estado' => Campana::PROGRAMADA, 'programada_para' => now()->addDays(2)]);
        $this->actingAs($this->admin);

        $this->getJson(route('promociones.datos.campanas', ['estado' => 'activas']))->assertOk()->assertJsonPath('data.0.id', $c->id)->assertJsonPath('conteos.programada', 1);
        $this->getJson(route('promociones.datos.campanas.show', $c))->assertOk()->assertJsonPath('puede.enviar', true);
        $this->getJson(route('promociones.datos.campanas.calendario', ['mes' => now()->addDays(2)->format('Y-m')]))
            ->assertOk()->assertJsonPath('eventos.0.tipo', 'programada');
        $this->getJson(route('promociones.datos.campanas.segmentos'))->assertOk()->assertJsonPath('todos', 2);

        $this->postJson(route('promociones.campanas.pausar', $c))->assertOk()->assertJsonStructure(['mensaje']);
        $this->assertSame(Campana::PAUSADA, $c->fresh()->estado);
    }

    public function test_crear_campana_programada_desde_el_creador(): void
    {
        $this->actingAs($this->admin)->post(route('promociones.campanas.store'), [
            'nombre' => 'Programada', 'mensaje' => 'Oferta', 'items' => ['paquete-'.$this->kit->id],
            'filtros' => ['modo' => 'segmento', 'segmento' => 'frecuentes', 'min_compras' => 2],
            'programar_para' => now()->addDay()->format('Y-m-d H:i'),
        ])->assertSessionHasNoErrors();

        $c = Campana::firstOrFail();
        $this->assertSame(Campana::PROGRAMADA, $c->estado);
        $this->assertSame(2, $c->filtros['min_compras']);
    }

    public function test_resumen_no_inventa_variacion_sin_periodo_anterior(): void
    {
        $this->cupon();
        $this->venderCon($this->ana, 'BIENVENIDO10');

        $k = app(\App\Services\Promociones\Indicadores::class)->calcular('7d');
        $this->assertEquals(1, $k['usados']['valor']);
        $this->assertNull($k['usados']['variacion']);
        $this->assertCount(7, $k['usados']['serie']);
    }
}
