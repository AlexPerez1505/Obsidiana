<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_agenda_route_renders_existing_calendar_view(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('marketing.agenda.index'));

        $response->assertOk();
        $response->assertViewIs('structure.gestion_marketing.calendario.calendario');
        $response->assertSee(route('marketing.inicio'), false);
        $response->assertDontSee('href="#" data-tip="Inicio"', false);
    }

    public function test_marketing_inicio_route_renders_dashboard(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('marketing.inicio'));

        $response->assertOk();
        $response->assertViewIs('structure.gestion_marketing.inicio.menu_marketing');
    }
}
