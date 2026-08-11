<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceStep;
use App\Models\ServiceTracking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QrFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Sembrar los 14 pasos del flujo externo (igual que ServiceStepsSeeder)
        $steps = [
            ['name' => 'Registro de servicio', 'slug' => 'registro-servicio', 'purpose' => 'registro', 'order' => 1, 'requires_qr' => false],
            ['name' => 'Llenado de información del equipo', 'slug' => 'llenado-equipo', 'purpose' => 'equipo', 'order' => 2, 'requires_qr' => false],
            ['name' => 'Generación de QR', 'slug' => 'generacion-qr', 'purpose' => 'generar_qr', 'order' => 3, 'requires_qr' => false],
            ['name' => 'Aprobación por autoridades', 'slug' => 'aprobacion-autoridades', 'purpose' => 'aprobacion', 'order' => 4, 'requires_qr' => false, 'requires_approval' => true],
            ['name' => 'Entrada del equipo', 'slug' => 'entrada-equipo', 'purpose' => 'entrada', 'order' => 5, 'requires_qr' => true],
            ['name' => 'Salida foránea', 'slug' => 'salida-foranea', 'purpose' => 'salida', 'order' => 6, 'requires_qr' => true],
            ['name' => 'Notificación de llegada del técnico', 'slug' => 'notificacion-llegada', 'purpose' => 'llegada', 'order' => 7, 'requires_qr' => true],
            ['name' => 'Llenado de mantenimiento', 'slug' => 'llenado-mantenimiento', 'purpose' => 'mantenimiento', 'order' => 8, 'requires_qr' => true],
            ['name' => 'Notificación de finalizado', 'slug' => 'notificacion-finalizado', 'purpose' => 'finalizado', 'order' => 9, 'requires_qr' => true],
            ['name' => 'Regreso foráneo', 'slug' => 'regreso-foraneo', 'purpose' => 'regreso', 'order' => 10, 'requires_qr' => true],
            ['name' => 'Generación de OS', 'slug' => 'generacion-os', 'purpose' => 'os', 'order' => 11, 'requires_qr' => false],
            ['name' => 'Validación de OS', 'slug' => 'validacion-os', 'purpose' => 'validacion_os', 'order' => 12, 'requires_qr' => false, 'requires_approval' => true],
            ['name' => 'Escaneo antes de salir con el cliente', 'slug' => 'escaneo-salida-cliente', 'purpose' => 'salida_cliente', 'order' => 13, 'requires_qr' => true],
            ['name' => 'Cliente feliz', 'slug' => 'cliente-feliz', 'purpose' => 'entrega', 'order' => 14, 'requires_qr' => false],
        ];

        foreach ($steps as $step) {
            ServiceStep::create($step + ['service_type' => 'externo', 'description' => null]);
        }

        Storage::fake('public');
    }

    /** @test */
    public function el_qr_se_genera_al_registrar_se_valida_en_la_ruta_y_se_guarda_en_el_servicio(): void
    {
        // --- Arrange: admin, cliente y técnico externo ---
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'email_verified_at' => now(),
        ]);

        $customer = Customer::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'telefono' => '5551234567',
            'gmail' => 'juan@example.com',
            'activo' => true,
        ]);

        $techId = DB::table('tecnico_externo')->insertGetId([
            'nombre' => 'Carlos',
            'apellidos' => 'López',
            'telefono' => '5559876543',
            'domicilio' => 'Calle 1',
            'correo' => 'carlos@example.com',
            'especialidad' => 'Electrónica',
            'empresa' => 'TechExt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstStep = ServiceStep::where('service_type', 'externo')->orderBy('order')->first();

        // --- 1. Generación del QR al registrar el servicio ---
        $response = $this->actingAs($admin)->postJson(
            route('gestion.servicios.historial.nueva_orden.store'),
            [
                'customer_id' => $customer->id,
                'mantenimiento_externo' => 1,
                'external_technician_id' => $techId,
                'tipo_equipo' => 'Laptop',
                'subtipo' => 'Gamer',
                'marca' => 'Lenovo',
                'modelo' => 'Legion',
                'serie' => 'SN-001',
                'descripcion_equipo' => 'No enciende',
            ]
        );

        $response->assertOk()->assertJsonStructure([
            'id', 'service_number', 'qr_token', 'qr_url', 'show_url', 'approvals_url', 'menu_url',
        ]);

        $payload = $response->json();
        $this->assertNotEmpty($payload['qr_token'], 'El QR token debe generarse al registrar');
        $this->assertNotEmpty($payload['qr_url'], 'La URL del QR debe construirse');

        // El servicio queda persistido con el QR y el paso actual
        $this->assertDatabaseHas('services', [
            'id' => $payload['id'],
            'qr_token' => $payload['qr_token'],
            'current_step_id' => $firstStep->id,
            'status' => 'registrado',
        ]);

        // El tracking del primer paso queda guardado con el mismo QR token
        $this->assertDatabaseHas('service_trackings', [
            'service_id' => $payload['id'],
            'service_step_id' => $firstStep->id,
            'status' => 'pendiente',
            'qr_token' => $payload['qr_token'],
        ]);

        // --- 2. Validación en la ruta: GET /qr/{token} abre el paso pendiente ---
        $showResponse = $this->get(route('qr.show', $payload['qr_token']));
        $showResponse->assertOk();
        $showResponse->assertSee($firstStep->name, false);

        // Un token inexistente debe dar 404
        $this->get(route('qr.show', 'token-inexistente'))->assertNotFound();

        // --- 3. Guardado: POST /qr/{token} completa el paso y avanza al siguiente ---
        $updateResponse = $this->post(route('qr.update', $payload['qr_token']), [
            'notes' => 'Paso de registro completado en test',
        ]);

        // El primer tracking debe quedar completado y guardado en el servicio
        $this->assertDatabaseHas('service_trackings', [
            'service_id' => $payload['id'],
            'service_step_id' => $firstStep->id,
            'status' => 'completado',
            'performed_by' => $admin->id,
            'notes' => 'Paso de registro completado en test',
        ]);
        $this->assertNotNull(
            ServiceTracking::where('service_id', $payload['id'])
                ->where('service_step_id', $firstStep->id)
                ->value('finished_at'),
            'El tracking completado debe registrar finished_at'
        );

        // El servicio avanza al segundo paso
        $secondStep = ServiceStep::where('service_type', 'externo')
            ->where('order', '>', $firstStep->order)
            ->orderBy('order')
            ->first();

        $service = Service::find($payload['id']);
        $this->assertEquals($secondStep->id, $service->current_step_id, 'El servicio debe avanzar al paso 2');
        $this->assertEquals('en_progreso', $service->status, 'El servicio debe quedar en_progreso');
        $this->assertNotEmpty($service->qr_token, 'El servicio debe recibir un nuevo QR token para el paso 2');

        // Se crea un nuevo tracking pendiente para el paso 2
        $this->assertDatabaseHas('service_trackings', [
            'service_id' => $payload['id'],
            'service_step_id' => $secondStep->id,
            'status' => 'pendiente',
            'qr_token' => $service->qr_token,
        ]);

        // El redirect del QrController apunta al nuevo QR
        $updateResponse->assertRedirect(route('qr.show', $service->qr_token));
    }

    /** @test */
    public function un_qr_expirado_no_se_puede_validar(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => User::STATUS_APPROVED,
            'email_verified_at' => now(),
        ]);

        $customer = Customer::create([
            'nombre' => 'Ana',
            'apellido' => 'Gómez',
            'activo' => true,
        ]);

        $response = $this->actingAs($admin)->postJson(
            route('gestion.servicios.historial.nueva_orden.store'),
            [
                'customer_id' => $customer->id,
                'mantenimiento_externo' => 1,
            ]
        )->assertOk();

        $token = $response->json('qr_token');

        // Caducar el QR manualmente
        ServiceTracking::where('qr_token', $token)->update(['qr_expires_at' => now()->subMinute()]);
        Service::where('qr_token', $token)->update(['qr_expires_at' => now()->subMinute()]);

        $this->withExceptionHandling()->get(route('qr.show', $token))->assertForbidden();
    }
}
