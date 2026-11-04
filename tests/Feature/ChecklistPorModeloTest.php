<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\InventoryMovement;
use App\Models\Subtype;
use App\Services\Inventario\ChecklistConIa;
use App\Services\Inventario\EntradasDeInventario;
use App\Services\Inventario\FotoPorPedazos;
use App\Support\ChecklistRecepcion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class ChecklistPorModeloTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private function equipo(): array
    {
        config(['services.openai.api_key' => null]);
        Http::preventStrayRequests();
        $tipo = EquipmentType::create(['name' => 'PRUEBA CHECKLIST '.Str::uuid()]);
        $subtipo = Subtype::create(['equipment_type_id' => $tipo->id, 'name' => 'PRUEBA '.Str::uuid()]);
        $marca = Brand::create(['name' => 'PRUEBA '.Str::uuid()]);
        $modelo = EquipmentModel::create(['brand_id' => $marca->id, 'subtype_id' => $subtipo->id, 'name' => 'MODELO CON LLAVE']);

        return ['equipment_type_id' => $tipo->id, 'subtype_id' => $subtipo->id, 'brand_id' => $marca->id, 'equipment_model_id' => $modelo->id];
    }

    private function manual(string $texto = 'Incluye llave azul'): array
    {
        return ['r' => 'no', 'texto' => $texto, 'grupo' => 'Agregados a mano', 'manda' => 'mantenimiento', 'nota' => 'Esta pieza llegó sin llave'];
    }

    private function consultar(array $equipo): array
    {
        return app(ChecklistConIa::class)->para($equipo['equipment_type_id'], $equipo['subtype_id'], $equipo['brand_id'], $equipo['equipment_model_id']);
    }

    public function test_recuerda_la_pregunta_del_modelo_pero_no_su_respuesta_ni_notas(): void
    {
        $equipo = $this->equipo();
        app(ChecklistConIa::class)->recordarAdicionales($equipo['equipment_model_id'], ['manual_llave' => $this->manual()]);
        $resultado = $this->consultar($equipo);
        $this->assertFalse($resultado['ia']);
        $this->assertSame(1, $resultado['guardados']);
        $puntos = $resultado['grupos']['agregados_modelo']['puntos'];
        $this->assertCount(1, $puntos);
        $punto = array_values($puntos)[0];
        $this->assertSame(['texto' => 'Incluye llave azul', 'manda' => 'mantenimiento'], $punto);
        $this->assertStringStartsWith('manual_', array_key_first($puntos));
        $guardado = EquipmentModel::findOrFail($equipo['equipment_model_id'])->checklist_recepcion_adicional;
        $this->assertSame($puntos, $guardado);
        $this->assertArrayNotHasKey('r', array_values($guardado)[0]);
        $this->assertArrayNotHasKey('nota', array_values($guardado)[0]);
    }

    public function test_separa_modelos_aunque_tengan_el_mismo_nombre(): void
    {
        $a = $this->equipo();
        $b = $this->equipo();
        app(ChecklistConIa::class)->recordarAdicionales($a['equipment_model_id'], ['manual_llave' => $this->manual()]);
        $this->assertSame(1, $this->consultar($a)['guardados']);
        $this->assertSame(0, $this->consultar($b)['guardados']);
        $this->assertArrayNotHasKey('agregados_modelo', $this->consultar($b)['grupos']);
    }

    public function test_acumula_puntos_sin_duplicar_mayusculas_o_espacios(): void
    {
        $equipo = $this->equipo();
        $servicio = app(ChecklistConIa::class);
        $servicio->recordarAdicionales($equipo['equipment_model_id'], ['manual_llave' => $this->manual()]);
        $servicio->recordarAdicionales($equipo['equipment_model_id'], [
            'manual_otra_llave' => $this->manual('  INCLUYE   LLAVE AZUL  '),
            'manual_tapa' => $this->manual('Incluye tapa protectora'),
            'carcasa' => $this->manual('Punto general que no debe recordarse'),
        ]);
        $this->assertSame(2, $this->consultar($equipo)['guardados']);
    }

    public function test_no_guarda_puntos_sin_respuesta_valida_ni_sin_modelo(): void
    {
        $equipo = $this->equipo();
        $servicio = app(ChecklistConIa::class);
        $servicio->recordarAdicionales(null, ['manual_llave' => $this->manual()]);
        $servicio->recordarAdicionales($equipo['equipment_model_id'], ['manual_llave' => array_replace($this->manual(), ['r' => 'inventada'])]);
        $this->assertSame(0, $this->consultar($equipo)['guardados']);
    }

    public function test_combina_los_puntos_recordados_con_el_checklist_ia_en_cache(): void
    {
        $equipo = $this->equipo();
        app(ChecklistConIa::class)->recordarAdicionales($equipo['equipment_model_id'], ['manual_llave' => $this->manual()]);
        config(['services.openai.api_key' => 'clave-simulada-no-utilizada']);
        Cache::put('checklist-ia:v2:'.implode('-', array_values($equipo)), ChecklistRecepcion::grupos());
        $resultado = $this->consultar($equipo);
        $this->assertTrue($resultado['ia']);
        $this->assertSame(1, $resultado['guardados']);
        $this->assertArrayHasKey('fisico', $resultado['grupos']);
    }

    public function test_entrada_usada_recuerda_el_punto_y_la_siguiente_consulta_no_marca_respuestas(): void
    {
        $equipo = $this->equipo();
        $user = $this->usuarioApi([], admin: true);
        $user->setAttribute('ficha_completada', true);
        $disco = config('filesystems.fotos_disk', 'public');
        $anterior = Storage::disk($disco);
        Storage::set($disco, Storage::createLocalDriver(['root' => sys_get_temp_dir().'/obsidiana-checklist-'.Str::uuid()]));
        try {
            $foto = FotoPorPedazos::CARPETA.'/foto_checklist.jpg';
            Storage::disk($disco)->put($foto, 'foto de prueba');
            $datos = $equipo + ['condicion' => 'usado', 'cantidad' => 1, 'movement_date' => now()->toDateString(), 'estado_general' => 'bueno',
                'checklist' => ['manual_llave' => $this->manual()],
                'firma' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
                'unidades' => [['no_serie' => 'PRUEBA-'.Str::uuid(), 'fotos_paths' => [$foto]]]];
            $this->actingAs($user)->post(route('inventory.movimientos.store'), $datos)->assertRedirect(route('inventory.movimientos.index'));
            $movimiento = InventoryMovement::whereHas('seriales.producto', fn ($q) => $q->where('equipment_model_id', $equipo['equipment_model_id']))->firstOrFail();
            $this->assertSame('no', $movimiento->checklist_recepcion['manual_llave']['r']);
            $resultado = $this->getJson(route('inventory.movimientos.checklistIa', $equipo))->assertOk()->json();
            $punto = array_values($resultado['grupos']['agregados_modelo']['puntos'])[0];
            $this->assertSame('Incluye llave azul', $punto['texto']);
            $this->assertArrayNotHasKey('r', $punto);
            $this->assertArrayNotHasKey('nota', $punto);
        } finally {
            Storage::disk($disco)->deleteDirectory('');
            Storage::set($disco, $anterior);
        }
    }

    public function test_entrada_rechazada_no_modifica_los_puntos_del_modelo(): void
    {
        $equipo = $this->equipo();
        $user = $this->usuarioApi([], admin: true);
        $user->setAttribute('ficha_completada', true);
        $this->actingAs($user)->post(route('inventory.movimientos.store'), $equipo + [
            'condicion' => 'usado', 'cantidad' => 1, 'movement_date' => now()->toDateString(), 'estado_general' => 'bueno',
            'checklist' => ['manual_llave' => $this->manual()], 'firma' => 'pendiente', 'unidades' => [['no_serie' => '']],
        ])->assertSessionHasErrors('unidades.0.no_serie');
        $this->assertNull(EquipmentModel::findOrFail($equipo['equipment_model_id'])->checklist_recepcion_adicional);
    }
}
