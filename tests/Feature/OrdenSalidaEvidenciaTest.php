<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OrdenSalida;
use App\Models\OrdenSalidaEvidencia;
use App\Models\Permission;
use App\Models\User;
use App\Models\Venta;
use App\Services\OrdenesDeSalida;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Evidencia de la orden de salida en la pantalla: fotos sin tope (una por
 * petición, siempre se suman), videos por pedazos, y quitar con su archivo.
 */
class OrdenSalidaEvidenciaTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private string $disco;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disco = config('filesystems.fotos_disk', 'public');

        /*
        | Como Storage::fake, pero en una carpeta propia: la de Storage::fake
        | la comparten todos los procesos de pruebas que corren a la vez en
        | este árbol, y uno la borra mientras otro la usa.
        */
        $raiz = storage_path('framework/testing/disks/ordenes-salida-evidencia-web');
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

    private function subirFoto(User $user, OrdenSalida $orden, string $nombre = 'foto.jpg')
    {
        return $this->actingAs($user)->postJson(route('inventory.salidas.evidencias.foto', $orden), [
            'foto' => UploadedFile::fake()->image($nombre, 30, 30),
        ]);
    }

    private function pedazo(OrdenSalida $orden, User $user, string $uploadId, int $index, int $total, string $contenido)
    {
        return $this->actingAs($user)->postJson(route('inventory.salidas.evidencias.videoChunk', $orden), [
            'chunk' => UploadedFile::fake()->createWithContent('chunk', $contenido),
            'upload_id' => $uploadId,
            'index' => $index,
            'total' => $total,
            'extension' => 'mp4',
            'nombre' => 'entrega.mp4',
        ]);
    }

    public function test_las_fotos_se_suman_sin_tope_y_salen_en_la_galeria(): void
    {
        $orden = $this->orden();
        $user = $this->almacenista();

        // Tres tandas de cinco, cada foto en su propia petición, como la pantalla.
        foreach (range(1, 3) as $tanda) {
            foreach (range(1, 5) as $n) {
                $this->subirFoto($user, $orden, "tanda{$tanda}-{$n}.jpg")
                    ->assertCreated()
                    ->assertJsonPath('status', 'listo')
                    ->assertJsonPath('evidencia.tipo', 'foto')
                    ->assertJsonPath('conteo.fotos', ($tanda - 1) * 5 + $n);
            }
        }

        $this->assertSame(15, $orden->evidencias()->count(), 'Cada tanda se suma: nunca reemplaza a la anterior.');

        $evidencia = $orden->evidencias()->first();
        $this->assertSame(OrdenSalidaEvidencia::FOTO, $evidencia->tipo);
        $this->assertSame($user->id, $evidencia->subido_por);
        $this->assertSame('tanda1-1.jpg', $evidencia->nombre_original);
        $this->assertStringStartsWith("inventario/salidas/evidencias/{$orden->id}/", $evidencia->path);
        Storage::disk($this->disco)->assertExists($evidencia->path);

        $this->actingAs($user)->get(route('inventory.salidas.show', $orden))
            ->assertOk()
            ->assertSee('15 fotos, 0 videos')
            ->assertSee($evidencia->url(), false)
            ->assertSee($user->name)
            ->assertSee(route('inventory.salidas.evidencias.foto', $orden), false);
    }

    public function test_la_respuesta_trae_el_cuadro_listo_para_la_galeria(): void
    {
        $orden = $this->orden();
        $user = $this->almacenista();

        $res = $this->subirFoto($user, $orden)->assertCreated();

        $evidencia = $orden->evidencias()->firstOrFail();
        $this->assertStringContainsString('data-evidencia="'.$evidencia->id.'"', $res->json('html'));
        $this->assertStringContainsString(route('inventory.salidas.evidencias.destroy', [$orden, $evidencia]), $res->json('html'));
    }

    public function test_el_video_por_pedazos_queda_como_evidencia(): void
    {
        $orden = $this->orden();
        $user = $this->almacenista();

        $this->pedazo($orden, $user, 'video-uno-1234', 0, 3, 'AAA')->assertOk()->assertJsonPath('status', 'chunk_recibido');
        $this->pedazo($orden, $user, 'video-uno-1234', 1, 3, 'BBB')->assertOk()->assertJsonPath('status', 'chunk_recibido');
        $this->assertSame(0, $orden->evidencias()->count(), 'Hasta el último pedazo no hay video que registrar.');

        $this->pedazo($orden, $user, 'video-uno-1234', 2, 3, 'CCC')
            ->assertCreated()
            ->assertJsonPath('status', 'listo')
            ->assertJsonPath('evidencia.tipo', 'video')
            ->assertJsonPath('conteo.videos', 1);

        $video = $orden->evidencias()->firstOrFail();
        $this->assertSame(OrdenSalidaEvidencia::VIDEO, $video->tipo);
        $this->assertSame('entrega.mp4', $video->nombre_original);
        $this->assertSame(9, $video->tamano);
        $this->assertStringStartsWith("inventario/salidas/evidencias/{$orden->id}/video_", $video->path);
        $this->assertSame('AAABBBCCC', Storage::disk($this->disco)->get($video->path));
        $this->assertFalse(Storage::disk($this->disco)->exists('inventario/tmp_videos/video-uno-1234'), 'Los pedazos se limpian.');

        // Un segundo video se suma al primero.
        $this->pedazo($orden, $user, 'video-dos-1234', 0, 1, 'ZZZ')->assertCreated();
        $this->assertSame(2, $orden->evidencias()->where('tipo', OrdenSalidaEvidencia::VIDEO)->count());

        $this->actingAs($user)->get(route('inventory.salidas.show', $orden))
            ->assertOk()
            ->assertSee('0 fotos, 2 videos')
            ->assertSee('<video src="'.$video->url().'" controls', false);
    }

    public function test_quitar_borra_el_registro_y_el_archivo(): void
    {
        $orden = $this->orden();
        $user = $this->almacenista();

        $this->subirFoto($user, $orden)->assertCreated();
        $this->subirFoto($user, $orden)->assertCreated();
        [$una, $otra] = $orden->evidencias()->get()->all();

        $this->actingAs($user)->deleteJson(route('inventory.salidas.evidencias.destroy', [$orden, $una]))
            ->assertOk()
            ->assertJsonPath('message', 'Foto eliminada.')
            ->assertJsonPath('conteo.fotos', 1);

        $this->assertModelMissing($una);
        Storage::disk($this->disco)->assertMissing($una->path);
        Storage::disk($this->disco)->assertExists($otra->path);

        // Sin JSON (formulario normal) regresa a la hoja.
        $this->actingAs($user)->from(route('inventory.salidas.show', $orden))
            ->delete(route('inventory.salidas.evidencias.destroy', [$orden, $otra]))
            ->assertRedirect(route('inventory.salidas.show', $orden));
        Storage::disk($this->disco)->assertMissing($otra->path);
    }

    public function test_la_evidencia_tiene_que_ser_de_la_orden(): void
    {
        $orden = $this->orden();
        $otra = $this->orden();
        $user = $this->almacenista();

        $this->subirFoto($user, $otra)->assertCreated();
        $ajena = $otra->evidencias()->firstOrFail();

        $this->actingAs($user)->deleteJson(route('inventory.salidas.evidencias.destroy', [$orden, $ajena]))->assertNotFound();
        $this->assertModelExists($ajena);
    }

    public function test_permisos(): void
    {
        $orden = $this->orden();
        $almacen = $this->almacenista();
        $soloVer = $this->usuarioApi(['salidas.ver']);

        $this->subirFoto($almacen, $orden)->assertCreated();
        $foto = $orden->evidencias()->firstOrFail();

        // Ver sí: la galería sale, pero sin botones para subir ni quitar.
        $this->actingAs($soloVer)->get(route('inventory.salidas.show', $orden))
            ->assertOk()
            ->assertSee($foto->url(), false)
            ->assertDontSee(route('inventory.salidas.evidencias.foto', $orden), false)
            ->assertDontSee(route('inventory.salidas.evidencias.destroy', [$orden, $foto]), false);

        // Subir no, ni foto ni video.
        $this->subirFoto($soloVer, $orden)->assertForbidden();
        $this->pedazo($orden, $soloVer, 'sin-permiso-12', 0, 1, 'AAA')->assertForbidden();

        // Quitar la de otro, tampoco.
        $this->actingAs($soloVer)->deleteJson(route('inventory.salidas.evidencias.destroy', [$orden, $foto]))->assertForbidden();
        $this->assertModelExists($foto);

        // Sin salidas.ver no entra.
        $this->actingAs($this->usuarioApi())->get(route('inventory.salidas.show', $orden))->assertForbidden();

        $this->assertSame(1, $orden->evidencias()->count());
    }

    public function test_quien_la_subio_la_puede_quitar_aunque_ya_no_prepare_salidas(): void
    {
        $orden = $this->orden();
        $user = $this->almacenista();

        $this->subirFoto($user, $orden)->assertCreated();
        $foto = $orden->evidencias()->firstOrFail();

        // Le quitan el permiso de preparar; la suya la sigue pudiendo quitar.
        $user->permissions()->detach(Permission::where('name', 'salidas.preparar')->value('id'));
        $user = $user->fresh(); // instancia nueva: los permisos se memorizan por instancia

        $this->assertFalse($user->can('salidas.preparar'));

        $this->actingAs($user)->deleteJson(route('inventory.salidas.evidencias.destroy', [$orden, $foto]))->assertOk();
        $this->assertModelMissing($foto);
    }

    public function test_cancelada_no_acepta_evidencia_pero_entregada_si(): void
    {
        $user = $this->almacenista();

        $cancelada = $this->orden(OrdenSalida::CANCELADA);
        $this->subirFoto($user, $cancelada)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Esta orden está cancelada: ya no se le agrega evidencia.');
        $this->pedazo($cancelada, $user, 'cancelada-1234', 0, 1, 'AAA')->assertStatus(409);
        $this->assertSame(0, $cancelada->evidencias()->count());

        // La foto que ya tenía se queda: tampoco se quita.
        $foto = $cancelada->evidencias()->create(['tipo' => 'foto', 'path' => 'inventario/salidas/evidencias/x.jpg', 'subido_por' => $user->id]);
        $this->actingAs($user)->deleteJson(route('inventory.salidas.evidencias.destroy', [$cancelada, $foto]))->assertStatus(409);
        $this->assertModelExists($foto);

        $this->actingAs($user)->get(route('inventory.salidas.show', $cancelada))
            ->assertOk()
            ->assertDontSee(route('inventory.salidas.evidencias.foto', $cancelada), false);

        // La foto de la entrega a veces llega después de firmar.
        $entregada = $this->orden(OrdenSalida::ENTREGADA);
        $this->subirFoto($user, $entregada)->assertCreated();
        $this->assertSame(1, $entregada->evidencias()->count());
    }

    public function test_validaciones_de_la_foto(): void
    {
        $orden = $this->orden();
        $user = $this->almacenista();

        $this->actingAs($user)->postJson(route('inventory.salidas.evidencias.foto', $orden), [
            'foto' => UploadedFile::fake()->create('contrato.pdf', 20, 'application/pdf'),
        ])->assertStatus(422)->assertJsonPath('errors.foto.0', 'El archivo no es una imagen (JPG, PNG o WEBP).');

        $this->actingAs($user)->postJson(route('inventory.salidas.evidencias.foto', $orden), [
            'foto' => UploadedFile::fake()->image('enorme.jpg')->size(10241),
        ])->assertStatus(422)->assertJsonPath('errors.foto.0', 'Cada foto puede pesar hasta 10MB.');

        $this->actingAs($user)->postJson(route('inventory.salidas.evidencias.foto', $orden), [])
            ->assertStatus(422)->assertJsonPath('errors.foto.0', 'Elige una foto.');

        $this->assertSame(0, $orden->evidencias()->count());
    }

    public function test_al_borrar_la_orden_se_van_los_archivos(): void
    {
        $user = $this->almacenista();

        $orden = $this->orden();
        $this->subirFoto($user, $orden)->assertCreated();
        $this->pedazo($orden, $user, 'borrar-orden-1', 0, 1, 'AAA')->assertCreated();
        $paths = $orden->evidencias()->pluck('path')->all();

        $orden->delete();

        $this->assertSame(0, OrdenSalidaEvidencia::count());
        foreach ($paths as $path) {
            Storage::disk($this->disco)->assertMissing($path);
        }

        // Igual cuando la orden se va con la venta eliminada.
        $otra = $this->orden();
        $this->subirFoto($user, $otra)->assertCreated();
        $path = $otra->evidencias()->value('path');

        app(OrdenesDeSalida::class)->cancelarDe($otra->venta);

        $this->assertModelMissing($otra);
        Storage::disk($this->disco)->assertMissing($path);
    }

    public function test_la_hoja_impresa_trae_el_conteo(): void
    {
        $orden = $this->orden();
        $user = $this->almacenista();

        $this->subirFoto($user, $orden)->assertCreated();
        $this->subirFoto($user, $orden)->assertCreated();
        $this->subirFoto($user, $orden)->assertCreated();
        $this->pedazo($orden, $user, 'hoja-impresa-1', 0, 1, 'AAA')->assertCreated();

        $html = view('structure.gestion_Inventario.ordenes_salida.pdf', [
            'orden' => $orden->fresh(['venta.customer', 'venta.seller', 'items', 'preparadaPor', 'entregadaPor', 'creator']),
            'firmaEntrega' => null,
            'firmaRecibe' => null,
        ])->render();

        $this->assertStringContainsString('Evidencia', $html);
        $this->assertStringContainsString('3 fotos, 1 video', $html);
        $this->assertStringNotContainsString('<img src="/storage/inventario/salidas/evidencias', $html, 'En la hoja solo va el conteo, sin imágenes.');

        $res = $this->actingAs($user)->get(route('inventory.salidas.pdf', $orden));
        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
    }
}
