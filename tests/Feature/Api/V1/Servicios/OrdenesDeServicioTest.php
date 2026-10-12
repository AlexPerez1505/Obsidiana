<?php

namespace Tests\Feature\Api\V1\Servicios;

use App\Models\Customer;
use App\Models\ExternalTechnician;
use App\Models\Service;
use App\Models\ServiceInvitation;
use App\Models\ServiceTracking;
use App\Models\TokenApi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class OrdenesDeServicioTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function capturista(): User
    {
        // Nombre fijo: "Joel" o "Icelda" lo volverían técnico y moverían los catálogos.
        return $this->usuarioApi(['servicios.ver', 'servicios.crear'], atributos: ['name' => 'Capturista de servicios']);
    }

    private function cliente(): Customer
    {
        return Customer::create(['nombre' => 'Hospital', 'apellido' => 'Ángeles', 'telefono' => '5512345678']);
    }

    /** Orden registrada por la misma API, para no armar a mano sus pasos. */
    private function orden(User $user, array $datos = []): Service
    {
        $res = $this->conToken($user)->post('/api/v1/servicios', $datos + [
            'mantenimiento_interno' => '1',
            'tipo_equipo' => 'Gastroscopio',
        ])->assertCreated();

        return Service::findOrFail($res->json('data.id'));
    }

    public function test_crea_una_orden_interna_con_equipo_refacciones_y_evidencia(): void
    {
        Storage::fake('public');
        $user = $this->capturista();
        $tecnico = $this->usuarioApi(atributos: ['name' => 'Joel Técnico']);

        $res = $this->conToken($user)->post('/api/v1/servicios', [
            'mantenimiento_interno' => '1',
            'internal_technician_id' => $tecnico->id,
            'tipo_equipo' => 'Gastroscopio',
            'marca' => 'Fujinon',
            'modelo' => 'EG-760R',
            'serie' => 'SN-123',
            'evidencia_1' => UploadedFile::fake()->image('frente.jpg'),
            'refacciones' => [
                ['nombre' => 'Válvula de succión', 'cantidad' => 2, 'precio_unitario' => 150.5],
            ],
        ]);

        $id = $res->json('data.id');

        $res->assertCreated()
            ->assertJsonPath('message', "Servicio OS-{$id} creado.")
            ->assertJsonPath('data.service_number', "OS-{$id}")
            ->assertJsonPath('data.service_type', 'interno')
            ->assertJsonPath('data.status', 'registrado')
            ->assertJsonPath('data.registered_by', $user->id)
            ->assertJsonPath('data.internal_technician.name', 'Joel Técnico')
            ->assertJsonPath('data.current_step.code', 'autorizacion_admin')
            ->assertJsonPath('data.service_equipment.serial_number', 'SN-123')
            ->assertJsonPath('data.spare_parts.0.subtotal', 301)
            ->assertJsonPath('data.service_trackings.0.status', 'pendiente')
            ->assertJsonMissingPath('data.qr_token')
            ->assertJsonMissingPath('data.service_trackings.0.qr_token');

        $this->assertStringContainsString('/storage/evidencias/', $res->json('data.service_equipment.evidence_1_url'));
        $this->assertCount(1, Storage::disk('public')->files('evidencias'));
        $this->assertDatabaseHas('service_equipment', ['service_id' => $id, 'product_code' => 'PRD-'.$res->json('data.service_equipment.id')]);
    }

    public function test_acepta_service_type_y_exige_cliente_en_externo(): void
    {
        $user = $this->capturista();

        $this->conToken($user)->postJson('/api/v1/servicios', ['service_type' => 'externo', 'tipo_equipo' => 'Torre'])
            ->assertStatus(422)
            ->assertJsonPath('errors.customer_id.0', 'Selecciona un cliente para el servicio externo.');

        $cliente = $this->cliente();

        $this->conToken($user)->postJson('/api/v1/servicios', [
            'service_type' => 'externo',
            'tipo_equipo' => 'Torre',
            'customer_id' => $cliente->id,
        ])->assertCreated()
            ->assertJsonPath('data.service_type', 'externo')
            ->assertJsonPath('data.customer.nombre', $cliente->nombre)
            ->assertJsonPath('data.current_step.code', 'salida_foranea');
    }

    public function test_sin_tipo_ni_equipo_da_422_en_espanol(): void
    {
        $user = $this->capturista();

        $this->conToken($user)->postJson('/api/v1/servicios', ['tipo_equipo' => 'Torre'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Selecciona el tipo de servicio.')
            ->assertJsonStructure(['errors' => ['mantenimiento_interno']]);

        $this->conToken($user)->postJson('/api/v1/servicios', ['mantenimiento_interno' => '1'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['tipo_equipo']]);

        $this->assertDatabaseCount('services', 0);
    }

    public function test_lista_filtra_y_pagina_el_historial(): void
    {
        $user = $this->capturista();
        $uno = $this->orden($user, ['serie' => 'ABC-1']);
        $dos = $this->orden($user);
        $dos->update(['status' => 'en_progreso']);

        $this->conToken($user)->getJson('/api/v1/servicios')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $dos->id);

        $this->conToken($user)->getJson('/api/v1/servicios?status=registrado')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $uno->id);

        $this->conToken($user)->getJson('/api/v1/servicios?buscar=ABC')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $uno->id);

        $this->conToken($user)->getJson('/api/v1/servicios/aprobaciones')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $uno->id);

        $this->conToken($user)->getJson('/api/v1/servicios?status=otro')->assertStatus(422);
    }

    public function test_detalle_trae_equipo_y_seguimiento(): void
    {
        $user = $this->capturista();
        $orden = $this->orden($user, ['firma' => 'data:image/png;base64,AAA']);

        $this->conToken($user)->getJson("/api/v1/servicios/{$orden->id}")
            ->assertOk()
            ->assertJsonPath('data.signature', 'data:image/png;base64,AAA')
            ->assertJsonPath('data.registered_by_user.id', $user->id)
            ->assertJsonPath('data.service_equipment.type_text', 'Gastroscopio')
            ->assertJsonPath('data.service_trackings.0.service_step.code', 'autorizacion_admin');

        $this->conToken($user)->getJson('/api/v1/servicios/999999')
            ->assertNotFound()->assertJsonPath('codigo', 'no_encontrado');
    }

    public function test_aprobar_y_denegar_solo_lo_registrado(): void
    {
        $user = $this->capturista();
        $aprobada = $this->orden($user);
        $denegada = $this->orden($user);

        $this->conToken($user)->postJson("/api/v1/servicios/{$aprobada->id}/aprobar")
            ->assertOk()
            ->assertJsonPath('data.status', 'en_progreso')
            ->assertJsonPath('message', "Orden {$aprobada->service_number} aprobada.");

        $this->conToken($user)->postJson("/api/v1/servicios/{$aprobada->id}/denegar")
            ->assertStatus(409)
            ->assertJsonPath('message', 'La orden no puede denegarse en su estado actual.');

        $this->conToken($user)->postJson("/api/v1/servicios/{$denegada->id}/denegar")
            ->assertOk()->assertJsonPath('data.status', 'cancelado');

        $this->conToken($user)->postJson("/api/v1/servicios/{$denegada->id}/aprobar")
            ->assertStatus(409)->assertJsonPath('codigo', 'conflicto');
    }

    public function test_renovar_qr_solo_en_externos(): void
    {
        $user = $this->capturista();
        $interna = $this->orden($user);
        $externa = $this->orden($user, [
            'mantenimiento_interno' => null,
            'mantenimiento_externo' => '1',
            'customer_id' => $this->cliente()->id,
        ]);
        $viejo = $externa->qr_token;

        $this->conToken($user)->postJson("/api/v1/servicios/{$externa->id}/renovar-qr")
            ->assertOk()
            ->assertJsonPath('message', 'QR renovado. Válido por 24 horas.')
            ->assertJsonMissingPath('data.qr_token');

        $nuevo = $externa->fresh()->qr_token;
        $this->assertNotSame($viejo, $nuevo);
        $this->assertSame($nuevo, ServiceTracking::where('service_id', $externa->id)->where('status', 'pendiente')->value('qr_token'));

        $this->conToken($user)->postJson("/api/v1/servicios/{$interna->id}/renovar-qr")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Solo se pueden renovar QR de mantenimientos externos.');
    }

    public function test_invitacion_devuelve_el_enlace_publico(): void
    {
        $user = $this->capturista();

        $res = $this->conToken($user)->postJson('/api/v1/servicios/invitaciones')->assertCreated();

        $invitacion = ServiceInvitation::sole();
        $res->assertJsonPath('data.token', $invitacion->token)
            ->assertJsonPath('data.link', route('public.nueva_orden', $invitacion))
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.invited_by', $user->id);
        $this->assertTrue($invitacion->expires_at->isFuture());
    }

    public function test_tecnicos_externos_alta_y_listado(): void
    {
        Storage::fake('public');
        $user = $this->capturista();

        $this->conToken($user)->postJson('/api/v1/servicios/tecnicos-externos', ['name' => 'Taller', 'phone' => 'abc'])
            ->assertStatus(422)
            ->assertJsonPath('errors.phone.0', 'El teléfono solo puede contener números, espacios y los caracteres + - ( ).');

        $res = $this->conToken($user)->post('/api/v1/servicios/tecnicos-externos', [
            'name' => 'Endoscopía del Bajío',
            'phone' => '+52 (477) 123-4567',
            'specialty' => 'Endoscopios',
            'photo' => UploadedFile::fake()->image('logo.png'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Endoscopía del Bajío')
            ->assertJsonPath('data.is_active', true);

        $this->assertStringContainsString('/storage/external_technicians/', $res->json('data.photo_url'));
        $this->assertCount(1, Storage::disk('public')->files('external_technicians'));

        ExternalTechnician::create(['name' => 'Inactivo', 'is_active' => false]);

        $this->conToken($user)->getJson('/api/v1/servicios/tecnicos-externos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.specialty', 'Endoscopios');
    }

    public function test_catalogos_para_la_nueva_orden(): void
    {
        $user = $this->capturista();
        $joel = $this->usuarioApi(atributos: ['name' => 'Joel Pérez']);
        $this->usuarioApi(atributos: ['name' => 'Ana López']);
        ExternalTechnician::create(['name' => 'Taller Norte', 'is_active' => true]);

        $res = $this->conToken($user)->getJson('/api/v1/servicios/catalogos')
            ->assertOk()
            ->assertJsonStructure(['data' => ['tipos_equipo', 'marcas', 'equipos', 'tecnicos_internos', 'tecnicos_externos']])
            ->assertJsonPath('data.tecnicos_externos.0.name', 'Taller Norte');

        $this->assertSame([$joel->id], array_column($res->json('data.tecnicos_internos'), 'id'));
    }

    public function test_sin_permiso_da_403(): void
    {
        $nadie = $this->usuarioApi();
        $lector = $this->usuarioApi(['servicios.ver']);
        $orden = $this->orden($this->capturista());

        $this->conToken($nadie)->getJson('/api/v1/servicios')
            ->assertForbidden()->assertJsonPath('codigo', 'sin_permiso');

        $this->conToken($lector)->getJson('/api/v1/servicios')->assertOk();
        $this->conToken($lector)->postJson('/api/v1/servicios', ['mantenimiento_interno' => '1', 'tipo_equipo' => 'X'])->assertForbidden();
        $this->conToken($lector)->postJson("/api/v1/servicios/{$orden->id}/aprobar")->assertForbidden();
        $this->conToken($lector)->postJson('/api/v1/servicios/invitaciones')->assertForbidden();
        $this->conToken($lector)->getJson('/api/v1/servicios/catalogos')->assertForbidden();

        $this->assertSame('registrado', $orden->fresh()->status);
    }

    public function test_la_llave_recortada_no_puede_crear_aunque_el_dueno_si(): void
    {
        $admin = $this->usuarioApi(admin: true);
        $orden = $this->orden($admin);

        $llave = fn () => $this->conToken($admin, ['servicios.ver'], TokenApi::TIPO_INTEGRACION);

        $llave()->getJson("/api/v1/servicios/{$orden->id}")->assertOk();
        $llave()->postJson("/api/v1/servicios/{$orden->id}/aprobar")->assertForbidden();
        $llave()->getJson('/api/v1/marketing/tareas')->assertForbidden();

        $this->assertSame('registrado', $orden->fresh()->status);
    }
}
