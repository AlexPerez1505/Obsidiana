<?php

namespace Tests\Feature\Api\V1\Comercial;

use App\Models\ClienteSeguimiento;
use App\Models\Customer;
use App\Models\TokenApi;
use App\Models\User;
use App\Services\Seguimientos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class SeguimientosTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function cliente(User $asesor): Customer
    {
        return Customer::create([
            'nombre' => 'Héctor',
            'apellido' => 'Ruiz',
            'telefono' => (string) random_int(5500000000, 5599999999),
            'direccion' => 'Centro',
            'etapa' => 'prospecto',
            'activo' => true,
            'recibe_promocion' => false,
            'asesor_id' => $asesor->id,
        ]);
    }

    private function seguimiento(Customer $cliente, User $responsable, array $atributos = []): ClienteSeguimiento
    {
        return $cliente->seguimientos()->create($atributos + [
            'user_id' => $responsable->id,
            'tipo' => 'llamada',
            'fecha' => now()->toDateString(),
            'created_by' => $responsable->id,
        ]);
    }

    public function test_programar_sin_ver_todos_queda_a_nombre_propio(): void
    {
        $yo = $this->usuarioApi(['clientes.editar']);
        $otro = $this->usuarioApi();
        $cliente = $this->cliente($yo);

        $this->conToken($yo)->postJson("/api/v1/clientes/{$cliente->id}/seguimientos", [
            'tipo' => 'visita',
            'fecha' => now()->addWeek()->toDateString(),
            'nota' => 'Llevar catálogo',
            'user_id' => $otro->id,
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Seguimiento programado. Se avisará el día que toque.')
            ->assertJsonPath('data.user_id', $yo->id)
            ->assertJsonPath('data.responsable.id', $yo->id)
            ->assertJsonPath('data.fecha', now()->addWeek()->toDateString())
            ->assertJsonPath('data.tipo_label', 'Visitarlo');
    }

    public function test_programar_con_ver_todos_respeta_el_responsable(): void
    {
        $jefe = $this->usuarioApi(['clientes.editar', 'clientes.ver_todos']);
        $asesor = $this->usuarioApi();
        $cliente = $this->cliente($asesor);

        $this->conToken($jefe)->postJson("/api/v1/clientes/{$cliente->id}/seguimientos", [
            'tipo' => 'llamada',
            'fecha' => now()->toDateString(),
            'user_id' => $asesor->id,
        ])->assertCreated()->assertJsonPath('data.user_id', $asesor->id)->assertJsonPath('data.created_by', $jefe->id);
    }

    public function test_fecha_pasada_da_422_y_otro_asesor_403(): void
    {
        $yo = $this->usuarioApi(['clientes.editar']);
        $mio = $this->cliente($yo);
        $ajeno = $this->cliente($this->usuarioApi());

        $peticion = $this->conToken($yo);

        $peticion->postJson("/api/v1/clientes/{$mio->id}/seguimientos", [
            'tipo' => 'llamada',
            'fecha' => now()->subDay()->toDateString(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.fecha.0', 'La fecha del seguimiento no puede ser anterior a hoy.');

        $peticion->postJson("/api/v1/clientes/{$ajeno->id}/seguimientos", [
            'tipo' => 'llamada',
            'fecha' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame(0, ClienteSeguimiento::count());
    }

    public function test_sin_editar_o_con_llave_recortada_da_403(): void
    {
        $soloVer = $this->usuarioApi(['clientes.ver']);
        $cliente = $this->cliente($soloVer);
        $datos = ['tipo' => 'llamada', 'fecha' => now()->toDateString()];

        $this->conToken($soloVer)->postJson("/api/v1/clientes/{$cliente->id}/seguimientos", $datos)->assertForbidden();

        $editor = $this->usuarioApi(['clientes.ver', 'clientes.editar']);
        $deEditor = $this->cliente($editor);
        $this->conToken($editor, ['clientes.ver'], TokenApi::TIPO_INTEGRACION)
            ->postJson("/api/v1/clientes/{$deEditor->id}/seguimientos", $datos)->assertForbidden();
    }

    public function test_marcar_hecho_guarda_quien_y_apaga_el_aviso(): void
    {
        $yo = $this->usuarioApi(['clientes.editar']);
        $cliente = $this->cliente($yo);
        $seguimiento = $this->seguimiento($cliente, $yo);

        // El recordatorio de hoy deja una notificación en la campana.
        app(Seguimientos::class)->recordarPendientes();
        $this->assertSame(1, $yo->unreadNotifications()->count());

        $this->conToken($yo)->postJson("/api/v1/clientes/{$cliente->id}/seguimientos/{$seguimiento->id}/hecho", [
            'resultado' => 'Pidió cotización para enero',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Seguimiento marcado como hecho.')
            ->assertJsonPath('data.estado', 'hecho')
            ->assertJsonPath('data.hecho_por', $yo->id)
            ->assertJsonPath('data.hecho_por_usuario.id', $yo->id)
            ->assertJsonPath('data.resultado', 'Pidió cotización para enero');

        $this->assertNotNull($seguimiento->fresh()->hecho_en);
        $this->assertSame(0, $yo->unreadNotifications()->count());
    }

    public function test_reprogramar_vuelve_a_avisar(): void
    {
        $yo = $this->usuarioApi(['clientes.editar']);
        $cliente = $this->cliente($yo);
        $seguimiento = $this->seguimiento($cliente, $yo, ['notificado_en' => now()]);
        $nueva = now()->addDays(10)->toDateString();

        $this->conToken($yo)->postJson("/api/v1/clientes/{$cliente->id}/seguimientos/{$seguimiento->id}/reprogramar", [
            'fecha' => $nueva,
        ])
            ->assertOk()
            ->assertJsonPath('data.fecha', $nueva)
            ->assertJsonPath('data.notificado_en', null)
            ->assertJsonPath('message', 'Seguimiento movido al '.now()->addDays(10)->format('d/m/Y').'.');

        $this->conToken($yo)->postJson("/api/v1/clientes/{$cliente->id}/seguimientos/{$seguimiento->id}/reprogramar", [
            'fecha' => now()->subDay()->toDateString(),
        ])->assertStatus(422)->assertJsonPath('errors.fecha.0', 'La fecha nueva no puede ser anterior a hoy.');
    }

    public function test_eliminar_y_seguimiento_de_otro_cliente_da_404(): void
    {
        $yo = $this->usuarioApi(['clientes.editar']);
        $cliente = $this->cliente($yo);
        $otroCliente = $this->cliente($yo);
        $seguimiento = $this->seguimiento($cliente, $yo);

        $peticion = $this->conToken($yo);

        $peticion->deleteJson("/api/v1/clientes/{$otroCliente->id}/seguimientos/{$seguimiento->id}")->assertNotFound();
        $this->assertDatabaseHas('cliente_seguimientos', ['id' => $seguimiento->id]);

        $peticion->deleteJson("/api/v1/clientes/{$cliente->id}/seguimientos/{$seguimiento->id}")
            ->assertOk()->assertJsonPath('message', 'Seguimiento eliminado.');
        $this->assertDatabaseMissing('cliente_seguimientos', ['id' => $seguimiento->id]);
    }
}
