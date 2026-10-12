<?php

namespace Tests\Feature\Api\V1\Cuenta;

use App\Mail\VerificationCodeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    /** Un PNG de 1x1, como lo mandaría el lienzo de firma. */
    private const FIRMA = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    public function test_ver_mi_perfil_sin_permisos_especiales(): void
    {
        $user = $this->usuarioApi();

        $this->conToken($user)->getJson('/api/v1/perfil')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.tiene_firma', false)
            ->assertJsonMissingPath('data.password');
    }

    public function test_cambiar_nombre_sin_tocar_el_correo(): void
    {
        Mail::fake();
        $user = $this->usuarioApi();

        $this->conToken($user)->patchJson('/api/v1/perfil', ['name' => 'Nuevo Nombre', 'email' => $user->email])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo Nombre')
            ->assertJsonPath('correo_por_verificar', false)
            ->assertJsonPath('message', 'Perfil actualizado.');

        $this->assertNotNull($user->fresh()->email_verified_at);
        Mail::assertNothingSent();
    }

    public function test_cambiar_el_correo_pide_verificarlo_de_nuevo(): void
    {
        Mail::fake();
        $user = $this->usuarioApi();
        $peticion = $this->conToken($user);

        $peticion->patchJson('/api/v1/perfil', ['name' => $user->name, 'email' => 'nuevo@obsidiana.test'])
            ->assertOk()
            ->assertJsonPath('correo_por_verificar', true)
            ->assertJsonPath('message', 'Cambiaste tu correo. Te enviamos un código para verificar el nuevo.');

        $user->refresh();
        $this->assertSame('nuevo@obsidiana.test', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->verification_code);
        Mail::assertSent(VerificationCodeMail::class, fn ($m) => $m->hasTo('nuevo@obsidiana.test'));

        // Hasta verificarlo, la API lo detiene.
        $this->app['auth']->forgetGuards();
        $peticion->getJson('/api/v1/perfil')->assertForbidden()->assertJsonPath('codigo', 'correo_no_verificado');
    }

    public function test_el_correo_no_se_repite(): void
    {
        $user = $this->usuarioApi();
        $otro = $this->usuarioApi();

        $this->conToken($user)->patchJson('/api/v1/perfil', ['name' => 'X', 'email' => $otro->email])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['email']]);
    }

    public function test_cambiar_contrasena_pide_la_actual(): void
    {
        $user = $this->usuarioApi();

        $this->conToken($user)->putJson('/api/v1/perfil/contrasena', [
            'current_password' => 'no-es', 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['current_password']]);

        $this->conToken($user)->putJson('/api/v1/perfil/contrasena', [
            'current_password' => 'password', 'password' => 'nueva-clave-1', 'password_confirmation' => 'otra',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['password']]);

        $this->conToken($user)->putJson('/api/v1/perfil/contrasena', [
            'current_password' => 'password', 'password' => 'nueva-clave-1', 'password_confirmation' => 'nueva-clave-1',
        ])->assertOk()->assertJsonPath('message', 'Contraseña actualizada.');

        $this->assertTrue(Hash::check('nueva-clave-1', $user->fresh()->password));
    }

    public function test_registrar_reemplazar_y_quitar_la_firma(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));
        $disco = Storage::disk(config('filesystems.fotos_disk', 'public'));
        $user = $this->usuarioApi();

        $this->conToken($user)->postJson('/api/v1/perfil/firma', ['firma' => self::FIRMA])
            ->assertOk()
            ->assertJsonPath('data.tiene_firma', true)
            ->assertJsonPath('message', 'Firma registrada. Ya se va a cargar sola cuando tengas que firmar.');

        $primera = $user->fresh()->firma_path;
        $disco->assertExists($primera);

        $this->conToken($user)->postJson('/api/v1/perfil/firma', ['firma' => self::FIRMA])->assertOk();
        $segunda = $user->fresh()->firma_path;
        $this->assertNotSame($primera, $segunda);
        $disco->assertMissing($primera);

        $this->conToken($user)->postJson('/api/v1/perfil/firma', ['firma' => 'no-es-imagen'])
            ->assertStatus(422)
            ->assertJsonPath('errors.firma.0', 'La firma no es válida. Vuelve a trazarla e intenta de nuevo.');
        $this->assertSame($segunda, $user->fresh()->firma_path);

        $this->conToken($user)->postJson('/api/v1/perfil/firma', [])
            ->assertStatus(422)->assertJsonPath('errors.firma.0', 'Traza tu firma en el recuadro antes de guardarla.');

        $this->conToken($user)->deleteJson('/api/v1/perfil/firma')
            ->assertOk()->assertJsonPath('message', 'Se quitó tu firma registrada.');
        $this->assertNull($user->fresh()->firma_path);
        $disco->assertMissing($segunda);
    }
}
