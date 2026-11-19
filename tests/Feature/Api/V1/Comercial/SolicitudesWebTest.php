<?php

namespace Tests\Feature\Api\V1\Comercial;

use App\Http\Controllers\Api\V1\Comercial\SolicitudWebController;
use App\Http\Resources\Api\V1\Comercial\SolicitudWebResource;
use App\Models\TiendaSolicitud;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class SolicitudesWebTest extends TestCase
{
    public function test_rutas_privadas_exigen_los_permisos_de_la_web(): void
    {
        foreach (['index', 'show', 'update'] as $accion) {
            $ruta = Route::getRoutes()->getByName('api.v1.solicitudes-web.'.$accion);
            $this->assertNotNull($ruta);
            $this->assertContains('auth:sanctum', $ruta->gatherMiddleware());
            $this->assertContains('api.cuenta', $ruta->gatherMiddleware());
            $this->assertContains('can:tienda.ver', $ruta->gatherMiddleware());
            if ($accion === 'update') {
                $this->assertContains('can:tienda.atender', $ruta->gatherMiddleware());
            }
        }
        $this->assertNotNull(Route::getRoutes()->getByName('api.v1.tienda.solicitudes'));
        $this->assertNotNull(Route::getRoutes()->getByName('commercial.solicitudes-web.index'));
    }

    public function test_no_se_puede_consultar_ni_actualizar_sin_token(): void
    {
        $this->getJson('/api/v1/solicitudes-web')->assertUnauthorized();
        $this->getJson('/api/v1/solicitudes-web/1')->assertUnauthorized();
        $this->putJson('/api/v1/solicitudes-web/1', ['estado' => 'cotizada'])->assertUnauthorized();
    }

    public function test_listado_paginado_filtra_y_conserva_metricas_globales(): void
    {
        config(['database.connections.solicitudes_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::setDefaultConnection('solicitudes_test');
        Schema::create('tienda_solicitudes', function (Blueprint $table) {
            $table->id();
            foreach (['folio', 'tipo', 'estado', 'nombre', 'empresa', 'telefono', 'correo', 'ciudad', 'especialidad', 'motivo', 'mensaje', 'articulos', 'nota_interna', 'atendida_en', 'ip'] as $columna) {
                $table->text($columna)->nullable();
            }
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('atendida_por')->nullable();
            $table->timestamps();
        });
        foreach ([['Ana', 'nueva'], ['Beatriz', 'cotizada'], ['Carlos', 'nueva']] as $i => [$nombre, $estado]) {
            TiendaSolicitud::create(['folio' => 'WEB-2026-000'.$i, 'nombre' => $nombre, 'estado' => $estado, 'tipo' => 'cotizacion', 'articulos' => [['nombre' => 'Equipo', 'cantidad' => 2]]]);
        }
        $controller = new SolicitudWebController;
        $request = Request::create('/?per_page=1&estado=nueva&tipo=cotizacion');
        app()->instance('request', $request);
        $datos = $controller->index($request)->response()->getData(true);
        $this->assertCount(1, $datos['data']);
        $this->assertSame(2, $datos['meta']['total']);
        $this->assertSame(2, $datos['meta']['last_page']);
        $this->assertSame(['total' => 3, 'sin_atender' => 2, 'cotizadas' => 1], $datos['resumen']);
        $this->assertCount(4, $datos['estados']);
        $request = Request::create('/?buscar=Beatriz&con_cliente=0&desde=2000-01-01&hasta=2099-12-31');
        app()->instance('request', $request);
        $datos = $controller->index($request)->response()->getData(true);
        $this->assertCount(1, $datos['data']);
        $this->assertSame('Beatriz', $datos['data'][0]['nombre']);
        $this->assertArrayNotHasKey('ip', $datos['data'][0]);
    }

    public function test_recurso_no_expone_ip_y_describe_contacto_y_articulos(): void
    {
        $solicitud = new TiendaSolicitud([
            'folio' => 'WEB-2026-0001', 'nombre' => 'Ana', 'tipo' => 'cotizacion',
            'estado' => 'nueva', 'ip' => '127.0.0.1',
            'articulos' => [['nombre' => 'Equipo', 'cantidad' => 2, 'marca' => 'Marca', 'modelo' => 'Modelo', 'fuente' => 'producto', 'disponible' => true]],
        ]);
        $solicitud->setRelation('cliente', null)->setRelation('atendidaPor', null);
        $datos = (new SolicitudWebResource($solicitud))->resolve();
        $this->assertArrayNotHasKey('ip', $datos);
        $this->assertSame(2, $datos['total_piezas']);
        $this->assertSame('Nueva', $datos['estado_label']);
        $this->assertSame('2 × Equipo', $datos['resumen']);
        $solicitud->fill(['tipo' => 'contacto', 'motivo' => 'renta', 'mensaje' => 'Necesito información']);
        $datos = (new SolicitudWebResource($solicitud))->resolve();
        $this->assertSame('Renta de equipo', $datos['motivo_label']);
        $this->assertSame('Contacto: Renta de equipo', $datos['resumen']);
    }

    public function test_actualizar_estado_registra_atencion_sin_aceptar_campos_ajenos(): void
    {
        $solicitud = Mockery::mock(TiendaSolicitud::class)->makePartial();
        $solicitud->setRawAttributes(['id' => 1, 'estado' => 'nueva', 'nombre' => 'Original'], true);
        $solicitud->shouldReceive('save')->once()->andReturnTrue();
        $solicitud->shouldReceive('load')->once()->andReturnSelf();
        $usuario = new User;
        $usuario->id = 7;
        $request = Request::create('/', 'PUT', ['estado' => 'en_proceso', 'nota_interna' => 'Llamar mañana', 'nombre' => 'No cambiar']);
        $request->setUserResolver(fn () => $usuario);
        (new SolicitudWebController)->update($request, $solicitud);
        $this->assertSame(7, $solicitud->atendida_por);
        $this->assertNotNull($solicitud->atendida_en);
        $this->assertSame('Original', $solicitud->nombre);
        $this->assertSame('Llamar mañana', $solicitud->nota_interna);
    }

    public function test_guardar_nota_no_reasigna_la_atencion(): void
    {
        $solicitud = Mockery::mock(TiendaSolicitud::class)->makePartial();
        $solicitud->setRawAttributes(['id' => 1, 'estado' => 'en_proceso', 'atendida_por' => 4], true);
        $solicitud->shouldReceive('save')->once()->andReturnTrue();
        $solicitud->shouldReceive('load')->once()->andReturnSelf();
        $request = Request::create('/', 'PUT', ['estado' => 'en_proceso', 'nota_interna' => 'Actualizada']);
        (new SolicitudWebController)->update($request, $solicitud);
        $this->assertSame(4, $solicitud->atendida_por);
    }

    public function test_rechaza_estados_invalidos(): void
    {
        $this->expectException(ValidationException::class);
        (new SolicitudWebController)->update(Request::create('/', 'PUT', ['estado' => 'inventado']), new TiendaSolicitud);
    }

    public function test_rechaza_notas_mayores_a_dos_mil_caracteres(): void
    {
        $this->expectException(ValidationException::class);
        (new SolicitudWebController)->update(Request::create('/', 'PUT', ['estado' => 'nueva', 'nota_interna' => str_repeat('x', 2001)]), new TiendaSolicitud);
    }
}
