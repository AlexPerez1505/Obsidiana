<?php

namespace Tests\Feature\Api\V1\Comercial;

use App\Models\ClienteSeguimiento;
use App\Models\Cotizacion;
use App\Models\Customer;
use App\Models\FichaTecnica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * La lógica de clientes, seguimientos y cotizaciones se sacó de los
 * controladores web a servicios para compartirla con la API. Esto cuida
 * que la web responda exactamente como antes: mismos redirects, flashes,
 * errores y JSON de sus llamadas AJAX.
 */
class WebComercialSigueIgualTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function cliente(User $asesor, array $atributos = []): Customer
    {
        return Customer::create($atributos + [
            'nombre' => 'Marta',
            'apellido' => 'Solís',
            'telefono' => '5512345678',
            'direccion' => 'Coyoacán',
            'etapa' => 'prospecto',
            'activo' => true,
            'recibe_promocion' => false,
            'asesor_id' => $asesor->id,
        ]);
    }

    public function test_alta_web_redirige_y_por_ajax_responde_lo_de_siempre(): void
    {
        $yo = $this->usuarioApi(['clientes.crear', 'clientes.ver']);

        $this->actingAs($yo)->post(route('commercial.clientes.store'), [
            'nombre' => 'Pablo', 'apellido' => 'Núñez', 'telefono' => ' 5598765432 ', 'direccion' => 'Centro',
            'rfc' => 'nupa900101xyz', 'recibe_promocion' => '1',
            'seguimiento_fecha' => now()->addDay()->toDateString(),
        ])
            ->assertRedirect(route('commercial.clientes.index'))
            ->assertSessionHas('status', 'Cliente guardado correctamente.');

        $pablo = Customer::where('telefono', '5598765432')->firstOrFail();
        $this->assertSame('NUPA900101XYZ', $pablo->rfc);
        $this->assertTrue($pablo->recibe_promocion);
        $this->assertSame($yo->id, $pablo->asesor_id);
        $this->assertSame('llamada', $pablo->seguimientos()->first()->tipo);

        // Modal de cotizaciones (AJAX).
        $this->actingAs($yo)->postJson(route('commercial.clientes.store'), [
            'nombre' => 'Ema', 'apellido' => 'Gil', 'telefono' => '5511112222', 'direccion' => 'Sur',
        ])
            ->assertOk()
            ->assertExactJson(['id' => Customer::max('id'), 'nombre' => 'Ema', 'apellido' => 'Gil', 'telefono' => '5511112222']);

        // Repetido: por AJAX 422 con el cliente que ya existe; por form, de regreso con error.
        $this->actingAs($yo)->postJson(route('commercial.clientes.store'), [
            'nombre' => 'Otro', 'apellido' => 'Más', 'telefono' => '55 1111 2222', 'direccion' => 'Sur',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ya hay un cliente registrado con ese número de teléfono.')
            ->assertJsonPath('cliente_similar.motivo', 'telefono')
            ->assertJsonPath('cliente_similar.cliente.nombre', 'Ema Gil');

        $this->actingAs($yo)->from(route('commercial.clientes.create'))->post(route('commercial.clientes.store'), [
            'nombre' => 'Otro', 'apellido' => 'Más', 'telefono' => '5511112222', 'direccion' => 'Sur',
        ])
            ->assertRedirect(route('commercial.clientes.create'))
            ->assertSessionHasErrors(['telefono' => 'Ya hay un cliente registrado con ese número de teléfono.'])
            ->assertSessionHas('cliente_similar');

        $this->actingAs($yo)->getJson(route('commercial.clientes.similar', ['gmail' => 'nadie@example.com']))
            ->assertExactJson(['similar' => null]);
    }

    public function test_edicion_web_sigue_igual(): void
    {
        $yo = $this->usuarioApi(['clientes.editar']);
        $cliente = $this->cliente($yo, ['recibe_promocion' => true]);

        $this->actingAs($yo)->put(route('commercial.clientes.update', $cliente), [
            'nombre' => 'Marta', 'apellido' => 'Solís', 'telefono' => '5512345678', 'direccion' => 'Tlalpan',
            'recibe_promocion' => '0', 'activo' => '0',
        ])
            ->assertRedirect(route('commercial.clientes.index'))
            ->assertSessionHas('status', 'Cliente actualizado correctamente.');

        $cliente->refresh();
        $this->assertSame('Tlalpan', $cliente->direccion);
        $this->assertFalse($cliente->recibe_promocion);
        $this->assertFalse($cliente->activo);
        $this->assertSame('prospecto', $cliente->etapa);

        $this->actingAs($yo)->post(route('commercial.clientes.categories.store'), ['nombre' => 'Laboratorio'])
            ->assertForbidden();
    }

    public function test_seguimientos_web_siguen_igual(): void
    {
        $yo = $this->usuarioApi(['clientes.editar']);
        $cliente = $this->cliente($yo);
        $ficha = route('commercial.clientes.show', $cliente).'#seguimientos';

        $this->actingAs($yo)->post(route('commercial.clientes.seguimientos.store', $cliente), [
            'tipo' => 'demo', 'fecha' => now()->toDateString(),
        ])->assertRedirect($ficha)->assertSessionHas('status', 'Seguimiento programado. Se avisará el día que toque.');

        $seguimiento = ClienteSeguimiento::firstOrFail();
        $nueva = now()->addDays(5);

        $this->actingAs($yo)->post(route('commercial.clientes.seguimientos.reprogramar', [$cliente, $seguimiento]), [
            'fecha' => $nueva->toDateString(),
        ])->assertRedirect($ficha)->assertSessionHas('status', 'Seguimiento movido al '.$nueva->format('d/m/Y').'.');

        $this->actingAs($yo)->post(route('commercial.clientes.seguimientos.hecho', [$cliente, $seguimiento]), [
            'resultado' => 'Listo',
        ])->assertRedirect($ficha)->assertSessionHas('status', 'Seguimiento marcado como hecho.');
        $this->assertSame($yo->id, $seguimiento->fresh()->hecho_por);

        $this->actingAs($yo)->post(route('commercial.clientes.convertir', $cliente))
            ->assertRedirect($ficha)->assertSessionHas('status', 'Marta Solís ahora es cliente.');

        $this->actingAs($yo)->delete(route('commercial.clientes.seguimientos.destroy', [$cliente, $seguimiento]))
            ->assertRedirect($ficha)->assertSessionHas('status', 'Seguimiento eliminado.');
        $this->assertSame(0, ClienteSeguimiento::count());
    }

    public function test_cotizacion_web_edita_descarga_y_busca_como_antes(): void
    {
        $yo = $this->usuarioApi(['cotizaciones.crear', 'cotizaciones.editar', 'cotizaciones.ver']);
        $cliente = $this->cliente($yo);
        FichaTecnica::create(['titulo' => 'Ficha A', 'activo' => true]);
        $item = ['tipo_item' => 'equipo', 'nombre' => 'Monitor', 'cantidad' => 1, 'precio_unitario' => 1000];

        $this->actingAs($yo)->post(route('commercial.cotizaciones.store'), [
            'customer_id' => $cliente->id, 'modalidad' => 'contado', 'items' => [$item],
        ])->assertSessionHas('status', 'Cotización COT-'.now()->year.'-0001 guardada correctamente.');

        $cot = Cotizacion::firstOrFail();

        $this->actingAs($yo)->put(route('commercial.cotizaciones.update', $cot), [
            'customer_id' => $cliente->id, 'modalidad' => 'contado', 'aplica_iva' => '1', 'items' => [$item],
        ])
            ->assertRedirect(route('commercial.cotizaciones.show', $cot))
            ->assertSessionHas('status', "Cotización {$cot->folio} actualizada.");
        $this->assertEquals(1160, (float) $cot->fresh()->total);

        $pdf = $this->actingAs($yo)->get(route('commercial.cotizaciones.pdf', $cot));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame("inline; filename=\"{$cot->folio}.pdf\"", $pdf->headers->get('Content-Disposition'));

        $this->actingAs($yo)->getJson(route('commercial.cotizaciones.clientes.buscar', ['q' => 'Marta']))
            ->assertOk()
            ->assertJsonPath('0.nombre', 'Marta Solís')
            ->assertJsonPath('0.cotizaciones', 1)
            ->assertJsonPath('0.compras', 0)
            ->assertJsonPath('0.ultima_compra', null);

        $this->actingAs($yo)->getJson(route('commercial.cotizaciones.fichas.buscar'))
            ->assertOk()->assertJsonPath('0.titulo', 'Ficha A');
    }
}
