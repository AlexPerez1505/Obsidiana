<?php

namespace Tests\Feature;

use App\Models\EquipmentType;
use App\Models\MaterialRequest;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioAprobado(bool $admin = true): User
    {
        return User::factory()->create([
            'is_admin' => $admin,
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function test_materiales_muestra_solicitudes_y_productos_reales(): void
    {
        $admin = $this->usuarioAprobado();
        $solicitante = $this->usuarioAprobado(false);
        $tipo = EquipmentType::create(['name' => 'Equipo Real '.uniqid()]);

        Producto::create([
            'equipment_type_id' => $tipo->id,
            'tipo_equipo' => $tipo->name,
            'marca' => 'CableCo',
            'modelo' => 'HDMI-4K',
            'precio' => 250,
            'stock' => 7,
        ]);

        MaterialRequest::create([
            'folio' => 'SOL-0099',
            'category' => 'Sistemas / TI',
            'material_name' => 'Cable HDMI',
            'quantity' => 3,
            'unit' => 'Pieza',
            'required_date' => now()->addDays(2)->toDateString(),
            'urgency' => 'Urgente',
            'status' => MaterialRequest::STATUS_PENDING,
            'requested_by' => $solicitante->id,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.materials.index'));

        $response->assertOk();
        $response->assertSee('SOL-0099');
        $response->assertSee('Cable HDMI');
        $response->assertSee($solicitante->name);
        $response->assertSee('HDMI-4K');
        $response->assertSee('Stock: 7');
    }

    public function test_enviar_solicitud_crea_registro_pendiente_en_base(): void
    {
        $user = $this->usuarioAprobado();

        $this->actingAs($user)->post(route('admin.materials.store'), [
            'category' => 'Sistemas / TI',
            'material_name' => 'Cable HDMI',
            'quantity' => 2,
            'unit' => 'Pieza',
            'required_date' => now()->addDay()->toDateString(),
            'urgency' => 'Normal',
            'justification' => 'Se requiere para instalacion.',
            'intent' => 'submit',
        ])->assertRedirect(route('admin.materials.index'));

        $request = MaterialRequest::firstOrFail();

        $this->assertSame(MaterialRequest::STATUS_PENDING, $request->status);
        $this->assertSame($user->id, $request->requested_by);
        $this->assertNotNull($request->submitted_at);
        $this->assertDatabaseHas('material_requests', [
            'material_name' => 'Cable HDMI',
            'quantity' => 2,
            'status' => MaterialRequest::STATUS_PENDING,
        ]);
    }

    public function test_guardar_borrador_crea_registro_borrador_en_base(): void
    {
        $user = $this->usuarioAprobado();

        $this->actingAs($user)->post(route('admin.materials.store'), [
            'category' => 'Compras',
            'material_name' => 'Guantes nitrilo',
            'quantity' => 5,
            'unit' => 'Caja',
            'required_date' => now()->addWeek()->toDateString(),
            'urgency' => 'Programada',
            'intent' => 'draft',
        ])->assertRedirect(route('admin.materials.index'));

        $request = MaterialRequest::firstOrFail();

        $this->assertSame(MaterialRequest::STATUS_DRAFT, $request->status);
        $this->assertSame($user->id, $request->requested_by);
        $this->assertNull($request->submitted_at);
    }

    public function test_admin_puede_aprobar_solicitud_pendiente(): void
    {
        $admin = $this->usuarioAprobado();
        $solicitud = MaterialRequest::create([
            'folio' => 'SOL-0100',
            'category' => 'Almacen',
            'material_name' => 'Cajas',
            'quantity' => 4,
            'unit' => 'Caja',
            'urgency' => 'Normal',
            'status' => MaterialRequest::STATUS_PENDING,
            'requested_by' => $admin->id,
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('admin.materials.review', $solicitud), [
            'decision' => 'approve',
        ])->assertRedirect(route('admin.materials.index'));

        $solicitud->refresh();

        $this->assertSame(MaterialRequest::STATUS_APPROVED, $solicitud->status);
        $this->assertSame($admin->id, $solicitud->reviewed_by);
        $this->assertSame(4, $solicitud->approved_quantity);
    }
}
