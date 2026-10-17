<?php

namespace Tests\Feature\Api\V1\Almacen;

use App\Models\Customer;
use App\Models\OrdenSalida;
use App\Models\OrdenSalidaEvidencia;
use App\Models\TokenApi;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Evidencia de las órdenes de salida por la API: mismas reglas que en la
 * web (sin tope de fotos, siempre se suman; video por pedazos; nada en una
 * orden cancelada) y los mismos permisos.
 */
class OrdenSalidaEvidenciaTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private string $disco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disco = config('filesystems.fotos_disk', 'public');

        // Carpeta propia en vez de Storage::fake: la de Storage::fake la
        // comparten los procesos de pruebas que corren a la vez.
        $raiz = storage_path('framework/testing/disks/ordenes-salida-evidencia-api');
        (new Filesystem)->cleanDirectory($raiz);

        Storage::set($this->disco, Storage::createLocalDriver([
            'root' => $raiz,
            'url' => '/storage',
        ]));
    }

    private function orden(?string $estado = null): OrdenSalida
    {
        $vendedor = $this->usuarioApi();
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);

        $venta = Venta::create([
            'folio' => 'V-OS-'.uniqid(),
            'customer_id' => $customer->id,
            'seller_id' => $vendedor->id,
            'modalidad' => 'contado',
            'estado' => 'confirmada',
            'total_contrato' => 100,
            'created_by' => $vendedor->id,
        ]);

        $orden = OrdenSalida::create([
            'folio' => OrdenSalida::siguienteFolio(),
            'venta_id' => $venta->id,
            'estado' => $estado ?? OrdenSalida::PENDIENTE,
            'created_by' => $vendedor->id,
        ]);

        $orden->items()->create(['nombre' => 'Endoscopio', 'cantidad' => 1, 'requiere_emplayado' => false, 'orden' => 0]);

        return $orden->fresh();
    }

    private function almacenista(): User
    {
        return $this->usuarioApi(['salidas.ver', 'salidas.preparar']);
    }

    /** @return list<UploadedFile> */
    private function fotos(int $cuantas): array
    {
        return array_map(fn ($n) => UploadedFile::fake()->image("foto-{$n}.jpg", 30, 30), range(1, $cuantas));
    }

    private function pedazo(string $uploadId, int $index, int $total, string $contenido, string $extension = 'mp4'): array
    {
        return [
            'chunk' => UploadedFile::fake()->createWithContent('chunk', $contenido),
            'upload_id' => $uploadId,
            'index' => $index,
            'total' => $total,
            'extension' => $extension,
            'nombre' => 'carga.'.$extension,
        ];
    }

    public function test_las_fotos_se_suman_en_cada_peticion_y_se_listan(): void
    {
        $orden = $this->orden();
        $user = $this->almacenista();
        $api = $this->conToken($user);

        $api->getJson("/api/v1/ordenes-salida/{$orden->id}/evidencias")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('conteo.fotos', 0);

        // Una sola, luego varias en una petición, luego otra tanda: se suman.
        $api->post("/api/v1/ordenes-salida/{$orden->id}/evidencias/fotos", ['fotos' => $this->fotos(1)])
            ->assertCreated()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('message', 'Foto agregada.')
            ->assertJsonPath('data.0.tipo', 'foto')
            ->assertJsonPath('data.0.subido_por', $user->id)
            ->assertJsonPath('data.0.subido_por_usuario.id', $user->id)
            ->assertJsonPath('data.0.nombre_original', 'foto-1.jpg')
            ->assertJsonPath('data.0.puede_borrar', true)
            ->assertJsonPath('conteo.fotos', 1);

        $api->post("/api/v1/ordenes-salida/{$orden->id}/evidencias/fotos", ['fotos' => $this->fotos(7)])
            ->assertCreated()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('message', '7 fotos agregadas.')
            ->assertJsonPath('conteo.fotos', 8);

        $api->post("/api/v1/ordenes-salida/{$orden->id}/evidencias/fotos", ['fotos' => $this->fotos(7)])
            ->assertCreated()
            ->assertJsonPath('conteo.fotos', 15);

        $this->assertSame(15, $orden->evidencias()->count());
        foreach ($orden->evidencias as $foto) {
            Storage::disk($this->disco)->assertExists($foto->path);
        }

        $lista = $api->getJson("/api/v1/ordenes-salida/{$orden->id}/evidencias")
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('conteo.fotos', 15)
            ->assertJsonPath('conteo.videos', 0)
            ->assertJsonStructure(['data' => [['id', 'tipo', 'url', 'subido_por', 'subido_por_usuario', 'created_at']]]);

        // URL completa: la app no tiene página contra la cual resolver una relativa.
        $this->assertStringStartsWith('http', $lista->json('data.0.url'));
        $this->assertStringEndsWith($orden->evidencias->first()->path, $lista->json('data.0.url'));

        // El detalle de la orden la trae también.
        $api->getJson("/api/v1/ordenes-salida/{$orden->id}")
            ->assertOk()
            ->assertJsonCount(15, 'data.evidencias')
            ->assertJsonPath('data.conteo_evidencias.fotos', 15)
            ->assertJsonPath('data.evidencias.0.tipo', 'foto');
    }

    public function test_el_video_por_pedazos_queda_registrado(): void
    {
        $orden = $this->orden();
        $api = $this->conToken($this->almacenista());
        $url = "/api/v1/ordenes-salida/{$orden->id}/evidencias/video";

        $api->post($url, $this->pedazo('api-video-0001', 0, 2, 'PRIMERO-'))
            ->assertOk()
            ->assertExactJson(['status' => 'chunk_recibido']);

        $res = $api->post($url, $this->pedazo('api-video-0001', 1, 2, 'SEGUNDO'))
            ->assertCreated()
            ->assertJsonPath('status', 'listo')
            ->assertJsonPath('message', 'Video agregado.')
            ->assertJsonPath('data.tipo', 'video')
            ->assertJsonPath('data.nombre_original', 'carga.mp4')
            ->assertJsonPath('data.tamano', 15)
            ->assertJsonPath('conteo.videos', 1);

        $video = OrdenSalidaEvidencia::findOrFail($res->json('data.id'));
        $this->assertSame('PRIMERO-SEGUNDO', Storage::disk($this->disco)->get($video->path));
        $this->assertStringStartsWith("inventario/salidas/evidencias/{$orden->id}/", $video->path);

        // Otro video, otro upload_id: se suma.
        $api->post($url, $this->pedazo('api-video-0002', 0, 1, 'OTRO', 'mov'))->assertCreated();

        $api->getJson("/api/v1/ordenes-salida/{$orden->id}/evidencias")
            ->assertJsonPath('conteo.videos', 2)
            ->assertJsonPath('conteo.fotos', 0);
    }

    public function test_validaciones(): void
    {
        $orden = $this->orden();
        $api = $this->conToken($this->almacenista());

        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/evidencias/fotos", [])
            ->assertStatus(422)
            ->assertJsonPath('errors.fotos.0', 'Manda al menos una foto en fotos[].');

        $api->post("/api/v1/ordenes-salida/{$orden->id}/evidencias/fotos", [
            'fotos' => [UploadedFile::fake()->create('nota.txt', 5, 'text/plain')],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fotos.0' => 'Alguno de los archivos no es una imagen (JPG, PNG o WEBP).']);

        $api->post("/api/v1/ordenes-salida/{$orden->id}/evidencias/video", $this->pedazo('api-video-mala', 0, 1, 'X', 'avi'))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['extension']]);

        $this->assertSame(0, $orden->evidencias()->count());
    }

    public function test_quitar_borra_el_registro_y_el_archivo(): void
    {
        $orden = $this->orden();
        $otra = $this->orden();
        $api = $this->conToken($this->almacenista());

        $api->post("/api/v1/ordenes-salida/{$orden->id}/evidencias/fotos", ['fotos' => $this->fotos(2)])->assertCreated();
        [$una, $dos] = $orden->evidencias()->get()->all();

        // La evidencia tiene que ser de la orden de la ruta.
        $api->deleteJson("/api/v1/ordenes-salida/{$otra->id}/evidencias/{$una->id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Esa evidencia no es de esta orden.');

        $api->deleteJson("/api/v1/ordenes-salida/{$orden->id}/evidencias/{$una->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Foto eliminada.')
            ->assertJsonPath('conteo.fotos', 1);

        $this->assertModelMissing($una);
        Storage::disk($this->disco)->assertMissing($una->path);
        Storage::disk($this->disco)->assertExists($dos->path);
    }

    public function test_permisos(): void
    {
        $orden = $this->orden();
        $almacen = $this->almacenista();

        $this->conToken($almacen)->post("/api/v1/ordenes-salida/{$orden->id}/evidencias/fotos", ['fotos' => $this->fotos(1)])->assertCreated();
        $foto = $orden->evidencias()->firstOrFail();

        // Sin salidas.ver no ve nada.
        $this->conToken($this->usuarioApi())->getJson("/api/v1/ordenes-salida/{$orden->id}/evidencias")->assertForbidden();

        // Con ver: lista, pero no sube ni quita lo de otros.
        $soloVer = $this->conToken($this->usuarioApi(['salidas.ver']));
        $soloVer->getJson("/api/v1/ordenes-salida/{$orden->id}/evidencias")
            ->assertOk()
            ->assertJsonPath('data.0.puede_borrar', false);
        $soloVer->post("/api/v1/ordenes-salida/{$orden->id}/evidencias/fotos", ['fotos' => $this->fotos(1)])
            ->assertForbidden()
            ->assertJsonPath('codigo', 'sin_permiso');
        $soloVer->post("/api/v1/ordenes-salida/{$orden->id}/evidencias/video", $this->pedazo('api-sin-permiso', 0, 1, 'X'))
            ->assertForbidden();
        $soloVer->deleteJson("/api/v1/ordenes-salida/{$orden->id}/evidencias/{$foto->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo quien la subió o quien prepara salidas puede quitar esta evidencia.');

        // Una llave de solo lectura de quien la subió tampoco la quita: el token recorta.
        $this->conToken($almacen, ['salidas.ver'], TokenApi::TIPO_INTEGRACION)
            ->deleteJson("/api/v1/ordenes-salida/{$orden->id}/evidencias/{$foto->id}")
            ->assertForbidden();

        $this->assertModelExists($foto);
        $this->assertSame(1, $orden->evidencias()->count());
    }

    public function test_cancelada_da_409_y_entregada_si_acepta(): void
    {
        $user = $this->almacenista();
        $api = $this->conToken($user);

        $cancelada = $this->orden(OrdenSalida::CANCELADA);
        $foto = $cancelada->evidencias()->create(['tipo' => 'foto', 'path' => 'inventario/salidas/evidencias/y.jpg', 'subido_por' => $user->id]);

        $api->post("/api/v1/ordenes-salida/{$cancelada->id}/evidencias/fotos", ['fotos' => $this->fotos(1)])
            ->assertStatus(409)
            ->assertJsonPath('codigo', 'conflicto')
            ->assertJsonPath('message', 'Esta orden está cancelada: ya no se le agrega evidencia.');
        $api->post("/api/v1/ordenes-salida/{$cancelada->id}/evidencias/video", $this->pedazo('api-cancelada-1', 0, 1, 'X'))
            ->assertStatus(409);
        $api->deleteJson("/api/v1/ordenes-salida/{$cancelada->id}/evidencias/{$foto->id}")
            ->assertStatus(409)
            ->assertJsonPath('codigo', 'conflicto');

        $this->assertSame(1, $cancelada->evidencias()->count());

        // Entregada sí: la foto de la entrega a veces llega después.
        $entregada = $this->orden(OrdenSalida::ENTREGADA);
        $api->post("/api/v1/ordenes-salida/{$entregada->id}/evidencias/fotos", ['fotos' => $this->fotos(2)])
            ->assertCreated()
            ->assertJsonPath('conteo.fotos', 2);
    }
}
