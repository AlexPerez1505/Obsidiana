<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Congress;
use App\Models\Producto;
use App\Models\User;
use App\Notifications\CongresoPiezasSinRegresar;
use App\Services\RegresoDeCongresos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Cuando el congreso ya pasó, alguien tiene que decir qué pasó con el
 * equipo: si volvió al almacén o si se vendió allá. El sistema lo pide,
 * insiste cada semana mientras siga pendiente, y deja de insistir cuando
 * ya no queda nada.
 */
class AvisoRegresoCongresoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ], $overrides));
    }

    private function congresoTerminado(): Congress
    {
        $categoria = Category::firstOrCreate(['nombre' => 'Endoscopia']);

        return Congress::create([
            'nombre' => 'Congreso de Endoscopia 2026',
            'categoria_id' => $categoria->id,
            'fecha_inicio' => now()->subDays(6)->toDateString(),
            'fecha_finalizacion' => now()->subDays(3)->toDateString(),
            'hora_montaje' => '08:00',
            'hora_desmontaje' => '18:00',
        ]);
    }

    private function piezaEn(Congress $congress): \App\Models\ProductoSerial
    {
        $producto = Producto::create([
            'tipo_equipo' => 'Endoscopio',
            'marca' => 'Olympus',
            'modelo' => 'X1',
            'stock' => 0,
        ]);

        $producto->agregarUnidades(1, ['SN-CONGRESO']);

        $pieza = $producto->seriales()->first();
        $pieza->update(['congress_id' => $congress->id, 'enviado_a_congreso_en' => now()->subDays(6)]);

        return $pieza;
    }

    public function test_avisa_a_los_usuarios_asignados_al_congreso(): void
    {
        Notification::fake();

        $congress = $this->congresoTerminado();
        $this->piezaEn($congress);

        $vendedor = $this->usuario();
        $ajeno = $this->usuario();
        $congress->notifiedUsers()->attach($vendedor->id);

        $avisados = app(RegresoDeCongresos::class)->avisarPendientes();

        $this->assertSame(1, $avisados);
        Notification::assertSentTo($vendedor, CongresoPiezasSinRegresar::class);
        Notification::assertNotSentTo($ajeno, CongresoPiezasSinRegresar::class);
    }

    public function test_si_nadie_esta_asignado_el_pendiente_se_va_a_los_administradores(): void
    {
        Notification::fake();

        $congress = $this->congresoTerminado();
        $this->piezaEn($congress);

        $admin = $this->usuario(['is_admin' => true]);
        $noAdmin = $this->usuario(['is_admin' => false]);

        app(RegresoDeCongresos::class)->avisarPendientes();

        Notification::assertSentTo($admin, CongresoPiezasSinRegresar::class);
        Notification::assertNotSentTo($noAdmin, CongresoPiezasSinRegresar::class);
    }

    public function test_no_avisa_dos_veces_seguidas_pero_insiste_a_la_semana(): void
    {
        Notification::fake();

        $congress = $this->congresoTerminado();
        $this->piezaEn($congress);
        $this->usuario(['is_admin' => true]);

        $servicio = app(RegresoDeCongresos::class);

        $this->assertSame(1, $servicio->avisarPendientes());
        // Al día siguiente todavía no: sería ruido diario.
        $this->assertSame(0, $servicio->avisarPendientes());

        // Una semana después sigue pendiente, así que vuelve a pedirlo.
        $congress->forceFill(['aviso_regreso_en' => now()->subDays(8)])->save();
        $this->assertSame(1, $servicio->avisarPendientes());
    }

    public function test_un_congreso_que_aun_no_termina_no_se_avisa(): void
    {
        Notification::fake();

        $categoria = Category::firstOrCreate(['nombre' => 'Endoscopia']);
        $congress = Congress::create([
            'nombre' => 'Congreso que viene',
            'categoria_id' => $categoria->id,
            'fecha_inicio' => now()->addDay()->toDateString(),
            'fecha_finalizacion' => now()->addDays(3)->toDateString(),
            'hora_montaje' => '08:00',
            'hora_desmontaje' => '18:00',
        ]);

        $this->piezaEn($congress);
        $this->usuario(['is_admin' => true]);

        $this->assertSame(0, app(RegresoDeCongresos::class)->avisarPendientes());
        Notification::assertNothingSent();
    }

    public function test_si_ya_no_queda_nada_alla_deja_de_insistir(): void
    {
        Notification::fake();

        $congress = $this->congresoTerminado();
        $pieza = $this->piezaEn($congress);
        $this->usuario(['is_admin' => true]);

        $servicio = app(RegresoDeCongresos::class);
        $servicio->avisarPendientes();

        // Se regresó al almacén: el pendiente se cerró.
        $pieza->update(['congress_id' => null, 'enviado_a_congreso_en' => null]);
        $congress->forceFill(['aviso_regreso_en' => now()->subDays(8)])->save();

        $this->assertSame(0, $servicio->avisarPendientes());
        // Y la marca se limpia, para poder volver a avisar si se reasignan piezas.
        $this->assertNull($congress->fresh()->aviso_regreso_en);
    }

    public function test_una_pieza_vendida_en_el_congreso_ya_no_es_pendiente(): void
    {
        Notification::fake();

        $congress = $this->congresoTerminado();
        $pieza = $this->piezaEn($congress);
        $this->usuario(['is_admin' => true]);

        // Se vendió allá: el equipo ya no tiene que regresar.
        $pieza->update(['vendido' => true, 'vendido_en' => now(), 'estado' => 'vendido']);

        $this->assertSame(0, app(RegresoDeCongresos::class)->avisarPendientes());
        Notification::assertNothingSent();
    }

    public function test_el_respaldo_solo_corre_una_vez_por_intervalo(): void
    {
        Notification::fake();
        Cache::flush();

        $congress = $this->congresoTerminado();
        $this->piezaEn($congress);
        $admin = $this->usuario(['is_admin' => true]);

        $servicio = app(RegresoDeCongresos::class);

        $servicio->avisarSiToca();
        // El segundo disparo queda bloqueado por el candado.
        $servicio->avisarSiToca();

        Notification::assertSentToTimes($admin, CongresoPiezasSinRegresar::class, 1);
    }

    public function test_el_comando_programado_corre_sin_errores(): void
    {
        Notification::fake();

        $congress = $this->congresoTerminado();
        $this->piezaEn($congress);
        $this->usuario(['is_admin' => true]);

        $this->artisan('congresos:avisar-regresos')
            ->expectsOutputToContain('Se avisó de 1 congreso(s)')
            ->assertSuccessful();
    }
}
