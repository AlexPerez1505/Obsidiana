<?php

namespace Tests\Feature;

use App\Models\EmployeeDocument;
use App\Models\Role;
use App\Models\User;
use App\Services\Administracion\CuentasDeUsuario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class UsuariosRhArchivosTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
        config(['filesystems.fotos_disk' => 'public']);
    }

    private function usuario(array $permisos = [], bool $admin = false): User
    {
        $user = $this->usuarioApi($permisos, admin: $admin);
        $user->setAttribute('ficha_completada', true);
        return $user;
    }

    private function foto(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('foto.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('identificacion.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
    }

    public function test_foto_rh_aparece_en_listado_expediente_y_junto_al_nombre(): void
    {
        $user = $this->usuario(admin: true);
        $this->actingAs($user)->post(route('admin.users.hrProfile.update'), ['user_id' => $user->id, 'avatar' => $this->foto()])->assertSessionHasNoErrors()->assertRedirect();
        $user->refresh();
        Storage::disk('public')->assertExists($user->avatar);
        $this->assertSame(Storage::disk('public')->url($user->avatar), $user->avatarUrl());
        $html = $this->get(route('admin.users.index'))->assertOk()->getContent();
        $this->assertStringContainsString('src="'.$user->avatarUrl().'"', $html);
        $this->assertMatchesRegularExpression('/id="dd-user"[\s\S]*?<img src="'.preg_quote($user->avatarUrl(), '/').'"/', $html);
        $this->get(route('admin.users.show', $user))->assertOk()->assertSee($user->avatarUrl(), escape: false);
    }

    public function test_sin_foto_se_mantiene_la_inicial_y_no_se_borra_la_foto_al_guardar_sin_archivo(): void
    {
        $user = $this->usuario(admin: true);
        $this->assertNull($user->avatarUrl());
        $html = $this->actingAs($user)->get(route('admin.users.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="dd-user"[\s\S]*?<span class="avatar"[^>]*>\s*[^<]+\s*<\/span>/', $html);
        $user->forceFill(['avatar' => 'avatars/prueba.png'])->save();
        Storage::disk('public')->put($user->avatar, 'imagen de prueba');
        $this->post(route('admin.users.hrProfile.update'), ['user_id' => $user->id])->assertSessionHasNoErrors();
        $this->assertSame('avatars/prueba.png', $user->fresh()->avatar);
        Storage::disk('public')->assertExists('avatars/prueba.png');
    }

    public function test_no_cambia_la_foto_si_se_rechaza_la_asignacion_de_admin(): void
    {
        $user = $this->usuario(['usuarios.editar']);
        $rol = Role::firstOrCreate(['name' => User::ROL_ADMIN], ['label' => 'Administrador']);
        $this->actingAs($user)->post(route('admin.users.hrProfile.update'), ['user_id' => $user->id, 'roles' => [$rol->id], 'avatar' => $this->foto()])->assertSessionHasErrors('roles');
        $this->assertNull($user->fresh()->avatar);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_subir_archivos_exige_usuarios_editar(): void
    {
        $user = $this->usuario(['usuarios.ver']);
        $this->actingAs($user)->post(route('admin.users.hrProfile.update'), ['user_id' => $user->id, 'avatar' => $this->foto(), 'ine_file' => $this->pdf()])->assertForbidden();
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_documento_nuevo_es_privado_y_permite_ver_y_descargar(): void
    {
        $user = $this->usuario(admin: true);
        $this->actingAs($user)->post(route('admin.users.hrProfile.update'), ['user_id' => $user->id, 'ine_file' => $this->pdf()])->assertSessionHasNoErrors()->assertRedirect();
        $doc = $user->employeeDocuments()->where('name', 'INE')->firstOrFail();
        Storage::disk('local')->assertExists($doc->file_path);
        Storage::disk('public')->assertMissing($doc->file_path);
        $url = route('admin.users.documento', ['user' => $user, 'documento' => $doc->id]);
        $ver = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith('inline;', $ver->headers->get('Content-Disposition'));
        $this->assertStringContainsString('private', $ver->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $ver->headers->get('Cache-Control'));
        $descarga = $this->get($url.'?descargar=1')->assertOk();
        $this->assertStringStartsWith('attachment;', $descarga->headers->get('Content-Disposition'));
    }

    public function test_documentos_anteriores_siguen_disponibles_y_no_se_mezclan_usuarios(): void
    {
        $user = $this->usuario(admin: true);
        $otro = $this->usuario();
        $path = 'employee_documents/'.$user->id.'/anterior.pdf';
        Storage::disk('public')->put($path, "%PDF-1.4\n%%EOF");
        $doc = EmployeeDocument::create(['user_id' => $user->id, 'name' => 'INE', 'file_path' => $path]);
        $this->actingAs($user)->get(route('admin.users.documento', ['user' => $user, 'documento' => $doc->id]))->assertOk();
        $this->get(route('admin.users.documento', ['user' => $otro, 'documento' => $doc->id]))->assertNotFound();
        Storage::disk('public')->delete($path);
        $this->get(route('admin.users.documento', ['user' => $user, 'documento' => $doc->id]))->assertNotFound();
    }

    public function test_api_subida_y_descarga_respetan_permiso_y_alcance_del_token(): void
    {
        $editor = $this->usuario(['usuarios.editar', 'usuarios.ver', 'clientes.ver']);
        $otro = $this->usuario();
        $res = $this->conToken($editor)->post('/api/v1/usuarios/'.$otro->id.'/documentos', ['ine_file' => $this->pdf()], ['Accept' => 'application/json'])->assertCreated();
        $url = $res->json('data.0.file_url');
        $this->assertStringContainsString('/api/v1/usuarios/'.$otro->id.'/documentos/', $url);
        $this->conToken($editor)->get($url)->assertOk();
        $this->conToken($editor, ['clientes.ver'], \App\Models\TokenApi::TIPO_INTEGRACION)->getJson($url)->assertForbidden();
        $sin = $this->usuario();
        $this->conToken($sin)->getJson($url)->assertForbidden();
    }

    public function test_limite_de_20_mb_por_documento_y_5_mb_para_foto(): void
    {
        foreach (array_keys(CuentasDeUsuario::DOCUMENTOS) as $campo) {
            $this->assertTrue(Validator::make([$campo => UploadedFile::fake()->create('doc.pdf', 20480, 'application/pdf')], CuentasDeUsuario::REGLAS_DOCUMENTOS)->passes());
            $this->assertTrue(Validator::make([$campo => UploadedFile::fake()->create('doc.pdf', 20481, 'application/pdf')], CuentasDeUsuario::REGLAS_DOCUMENTOS)->fails());
        }
        $this->assertTrue(Validator::make(['avatar' => UploadedFile::fake()->create('foto.png', 5121, 'image/png')], CuentasDeUsuario::REGLAS_RH)->fails());
    }

    public function test_acepta_formatos_de_imagen_y_office_y_rechaza_ejecutables(): void
    {
        foreach (['heic' => 'image/heic', 'heif' => 'image/heif', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'] as $ext => $mime) {
            $v = Validator::make(['ine_file' => UploadedFile::fake()->create('documento.'.$ext, 10, $mime)], CuentasDeUsuario::REGLAS_DOCUMENTOS);
            $this->assertTrue($v->passes(), $ext.': '.json_encode($v->errors()->all()));
        }
        $falso = UploadedFile::fake()->createWithContent('falso.pdf', '<?php echo 1;');
        $archivo = new UploadedFile($falso->getPathname(), 'falso.pdf', 'application/pdf', null, true);
        $this->assertTrue(Validator::make(['ine_file' => $archivo], CuentasDeUsuario::REGLAS_DOCUMENTOS)->fails());
    }

    public function test_office_ooxml_se_valida_por_su_contenido_real(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive no está disponible para construir el archivo de prueba.');
        }
        foreach (['docx' => 'word/document.xml', 'xlsx' => 'xl/workbook.xml', 'pptx' => 'ppt/presentation.xml'] as $ext => $documento) {
            $path = tempnam(sys_get_temp_dir(), 'rh-office-');
            try {
                $zip = new \ZipArchive;
                $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/></Types>');
                $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>');
                $zip->addFromString($documento, '<?xml version="1.0"?><document/>');
                $zip->close();
                $archivo = new UploadedFile($path, 'documento.'.$ext, null, null, true);
                $v = Validator::make(['ine_file' => $archivo], CuentasDeUsuario::REGLAS_DOCUMENTOS);
                $this->assertTrue($v->passes(), $ext.' detectado como '.$archivo->getMimeType());
            } finally {
                unlink($path);
            }
        }
    }

    public function test_urls_de_avatar_anteriores_no_se_convierten_en_rutas_rotas(): void
    {
        $user = $this->usuario();
        foreach (['https://example.test/foto.png', '/storage/avatars/foto.png'] as $url) {
            $user->avatar = $url;
            $this->assertSame($url, $user->avatarUrl());
        }
    }
}
