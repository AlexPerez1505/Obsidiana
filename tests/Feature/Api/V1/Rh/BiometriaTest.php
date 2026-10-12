<?php

namespace Tests\Feature\Api\V1\Rh;

use App\Models\BiometricDevice;
use App\Models\BiometricEnrollment;
use App\Models\EmployeeBiometric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class BiometriaTest extends TestCase
{
    use AyudasRh, RefreshDatabase, UsaApi;

    public function test_crear_lector_entrega_la_llave_una_sola_vez(): void
    {
        $rh = $this->usuarioApi(['asistencia.biometria']);
        $api = $this->conToken($rh);

        $res = $api->postJson('/api/v1/rh/biometria/dispositivos', ['name' => 'Recepción', 'code' => 'RECEPCION-01', 'location' => 'Planta baja']);

        $res->assertCreated()
            ->assertJsonPath('message', 'Lector «Recepción» creado.')
            ->assertJsonPath('data.code', 'RECEPCION-01')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonMissingPath('data.api_key_hash')
            ->assertJsonMissingPath('data.kiosk_token');

        $llave = $res->json('api_key');
        $d = BiometricDevice::firstOrFail();
        $this->assertStringStartsWith('bk_', $llave);
        $this->assertStringContainsString($d->kiosk_token, $res->json('pantalla_url'));
        $this->assertTrue(BiometricDevice::porLlave($llave)->is($d));

        // Después ya no se vuelve a ver ni la llave ni el token de la pantalla
        foreach (['/api/v1/rh/biometria/dispositivos', '/api/v1/rh/biometria/dispositivos/'.$d->id] as $url) {
            $cuerpo = $api->getJson($url)->assertOk()->getContent();
            $this->assertStringNotContainsString($llave, $cuerpo);
            $this->assertStringNotContainsString($d->kiosk_token, $cuerpo);
            $this->assertStringNotContainsString($d->api_key_hash, $cuerpo);
            $this->assertStringNotContainsString('api_key', $cuerpo);
        }

        $api->postJson('/api/v1/rh/biometria/dispositivos', ['name' => 'Otro', 'code' => 'RECEPCION-01'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['code']]);
    }

    public function test_regenerar_llave_invalida_la_anterior_y_alternar_desactiva(): void
    {
        $rh = $this->usuarioApi(['asistencia.biometria']);
        [$d, $vieja] = BiometricDevice::crear(['name' => 'Almacén', 'code' => 'ALM-01', 'status' => 'active']);
        $api = $this->conToken($rh);

        $nueva = $api->postJson("/api/v1/rh/biometria/dispositivos/{$d->id}/llave")
            ->assertOk()
            ->assertJsonPath('message', 'Llave regenerada: el agente instalado dejará de funcionar hasta que se actualice.')
            ->json('api_key');

        $this->assertNotSame($vieja, $nueva);
        $this->assertNull(BiometricDevice::porLlave($vieja));
        $this->assertNotNull(BiometricDevice::porLlave($nueva));

        $api->postJson("/api/v1/rh/biometria/dispositivos/{$d->id}/alternar")
            ->assertOk()->assertJsonPath('data.status', 'inactive')->assertJsonPath('message', 'Lector desactivado.');
        $this->assertNull(BiometricDevice::porLlave($nueva));

        $api->getJson('/api/v1/rh/biometria/dispositivos?status=inactive')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.checadas_hoy', 0);
    }

    public function test_solicitar_registro_reemplaza_la_pendiente_y_se_puede_cancelar(): void
    {
        $rh = $this->usuarioApi(['asistencia.biometria']);
        $emp = $this->usuarioApi(atributos: ['name' => 'Iván Ruiz']);
        [$d] = BiometricDevice::crear(['name' => 'Recepción', 'code' => 'REC-01', 'status' => 'active']);
        $api = $this->conToken($rh);

        $primera = $api->postJson('/api/v1/rh/biometria/inscripciones', ['user_id' => $emp->id, 'device_id' => $d->id, 'finger_index' => 1])
            ->assertCreated()
            ->assertJsonPath('message', 'Solicitud enviada al lector. Iván Ruiz ya puede colocar el dedo.')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.dedo', 'Índice derecho')
            ->assertJsonPath('data.vigente', true)
            ->json('data.id');

        $segunda = $api->postJson('/api/v1/rh/biometria/inscripciones', ['user_id' => $emp->id, 'device_id' => $d->id, 'finger_index' => 6])
            ->assertCreated()->json('data.id');

        $this->assertSame('cancelled', BiometricEnrollment::find($primera)->status);
        $api->getJson('/api/v1/rh/biometria/inscripciones?estado=vigentes')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $segunda);

        $api->postJson("/api/v1/rh/biometria/inscripciones/{$segunda}/cancelar")
            ->assertOk()->assertJsonPath('data.status', 'cancelled')->assertJsonPath('data.message', 'Cancelada por RH.');
        $api->getJson("/api/v1/rh/biometria/inscripciones/{$segunda}")->assertOk()->assertJsonPath('data.vigente', false);

        $api->postJson('/api/v1/rh/biometria/inscripciones', ['user_id' => $emp->id, 'device_id' => 999, 'finger_index' => 12])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['device_id', 'finger_index']]);
    }

    public function test_las_plantillas_de_huella_nunca_salen_por_la_api(): void
    {
        $rh = $this->usuarioApi(['asistencia.biometria', 'asistencia.ver']);
        $emp = $this->empleado(['name' => 'Sara Gil']);
        $huella = EmployeeBiometric::create([
            'user_id' => $emp->id, 'finger_index' => 1, 'template' => 'PLANTILLA-SECRETA-123', 'template_format' => 'zkteco',
        ]);
        $cifrada = $huella->getAttributes()['template'];
        $api = $this->conToken($rh);

        foreach (['/api/v1/rh/biometria/huellas', '/api/v1/rh/asistencia/empleados/'.$emp->id] as $url) {
            $cuerpo = $api->getJson($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('PLANTILLA-SECRETA-123', $cuerpo);
            $this->assertStringNotContainsString($cifrada, $cuerpo);
            $this->assertStringNotContainsString('"template"', $cuerpo);
        }

        $api->getJson('/api/v1/rh/biometria/huellas?buscar=Sara')->assertOk()
            ->assertJsonPath('data.0.huellas_count', 1)
            ->assertJsonPath('data.0.huellas.0.dedo', 'Índice derecho')
            ->assertJsonPath('data.0.huellas.0.template_format', 'zkteco');

        $api->deleteJson('/api/v1/rh/biometria/huellas/'.$huella->id)->assertOk()->assertJsonPath('message', 'Huella eliminada de Sara Gil.');
        $this->assertDatabaseCount('employee_biometrics', 0);
    }

    public function test_biometria_pide_su_propio_permiso(): void
    {
        $api = $this->conToken($this->usuarioApi(['asistencia.ver', 'asistencia.gestionar']));

        $api->getJson('/api/v1/rh/biometria/dispositivos')->assertForbidden();
        $api->postJson('/api/v1/rh/biometria/dispositivos', ['name' => 'X', 'code' => 'X-1'])->assertForbidden();
        $api->getJson('/api/v1/rh/biometria/huellas')->assertForbidden();
        $this->assertDatabaseCount('biometric_devices', 0);
    }
}
