<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Congress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El congreso sí ocupa su cuadro cada día que dura, pero el texto cambia:
 * el primer día avisa el montaje (con su hora), el último el desmontaje
 * (con su hora), y los días de en medio solo muestran el nombre, sin hora.
 */
class AgendaCongresoTest extends TestCase
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

    public function test_congreso_de_varios_dias_muestra_montaje_desmontaje_y_nombre_en_medio(): void
    {
        $user = $this->usuarioAprobado();
        $categoria = Category::create(['nombre' => 'Endoscopia']);

        Congress::create([
            'nombre' => 'Congreso de Prueba',
            'categoria_id' => $categoria->id,
            'fecha_inicio' => '2026-09-10',
            'fecha_finalizacion' => '2026-09-14',
            'hora_montaje' => '08:00',
            'hora_desmontaje' => '18:00',
        ]);

        $response = $this->actingAs($user)->get(route('admin.agenda.index', ['month' => '2026-09']));

        $response->assertOk();

        $events = $response->viewData('events');

        // Día 10 (montaje): título con "Montaje" y su hora.
        $diaMontaje = collect($events[10])->firstWhere('source', 'congress');
        $this->assertSame('Montaje: Congreso de Prueba', $diaMontaje['title']);
        $this->assertSame('08:00', $diaMontaje['time_value']);

        // Días 11, 12, 13 (intermedios): el cuadro sí aparece, pero solo con
        // el nombre, sin "Montaje"/"Desmontaje" ni hora.
        foreach ([11, 12, 13] as $dia) {
            $diaIntermedio = collect($events[$dia])->firstWhere('source', 'congress');
            $this->assertNotNull($diaIntermedio, "El día {$dia} debe mostrar el cuadro del congreso.");
            $this->assertSame('Congreso de Prueba', $diaIntermedio['title']);
            $this->assertSame('', $diaIntermedio['time_value']);
        }

        // Día 14 (desmontaje): título con "Desmontaje" y su hora.
        $diaDesmontaje = collect($events[14])->firstWhere('source', 'congress');
        $this->assertSame('Desmontaje: Congreso de Prueba', $diaDesmontaje['title']);
        $this->assertSame('18:00', $diaDesmontaje['time_value']);

        // El mes debe mostrarse en español.
        $this->assertSame('Septiembre 2026', $response->viewData('monthLabel'));
    }

    public function test_congreso_de_un_solo_dia_combina_montaje_y_desmontaje(): void
    {
        $user = $this->usuarioAprobado();
        $categoria = Category::create(['nombre' => 'Endoscopia']);

        Congress::create([
            'nombre' => 'Congreso de un Dia',
            'categoria_id' => $categoria->id,
            'fecha_inicio' => '2026-09-20',
            'fecha_finalizacion' => '2026-09-20',
            'hora_montaje' => '07:00',
            'hora_desmontaje' => '20:00',
        ]);

        $response = $this->actingAs($user)->get(route('admin.agenda.index', ['month' => '2026-09']));

        $events = $response->viewData('events');
        $congresosEseDia = collect($events[20])->where('source', 'congress');

        $this->assertCount(1, $congresosEseDia, 'Un congreso de un solo día debe generar un único cuadro, no dos.');
        $this->assertSame('Montaje y desmontaje: Congreso de un Dia', $congresosEseDia->first()['title']);
    }
}
