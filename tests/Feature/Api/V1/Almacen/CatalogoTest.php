<?php

namespace Tests\Feature\Api\V1\Almacen;

use App\Models\Brand;
use App\Models\Category;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Refaccion;
use App\Models\Subtype;
use App\Models\TokenApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Catálogo (categorías, tipo → subtipo → marca → modelo, alta rápida) y
 * refacciones por la API, con las mismas reglas que la web.
 */
class CatalogoTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function catalogo()
    {
        return $this->conToken($this->usuarioApi(['inventario.catalogo']));
    }

    public function test_categorias_crud_y_nombre_unico(): void
    {
        $api = $this->catalogo();

        $id = $api->postJson('/api/v1/catalogo/categorias', ['nombre' => 'Endoscopia'])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Endoscopia')
            ->json('data.id');

        $api->postJson('/api/v1/catalogo/categorias', ['nombre' => 'Endoscopia'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['nombre']]);

        $api->putJson("/api/v1/catalogo/categorias/{$id}", ['nombre' => 'Urología'])
            ->assertOk()->assertJsonPath('data.nombre', 'Urología');

        $api->getJson('/api/v1/catalogo/categorias?buscar=uro')->assertJsonCount(1, 'data');

        $api->deleteJson("/api/v1/catalogo/categorias/{$id}")->assertOk();
        $this->assertDatabaseCount('categorias', 0);
    }

    public function test_tipos_subtipos_marcas_y_modelos(): void
    {
        $api = $this->catalogo();

        $tipo = $api->postJson('/api/v1/catalogo/tipos', ['name' => 'Torre', 'requiere_emplayado' => true])
            ->assertCreated()
            ->assertJsonPath('data.name', 'TORRE')
            ->assertJsonPath('data.requiere_emplayado', true)
            ->json('data.id');

        $api->postJson('/api/v1/catalogo/tipos', ['name' => 'torre'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Ya existe un tipo de equipo con ese nombre.');

        // Sin la casilla, queda en falso, igual que en la web.
        $api->putJson("/api/v1/catalogo/tipos/{$tipo}", ['name' => 'Torre'])
            ->assertOk()->assertJsonPath('data.requiere_emplayado', false);

        $subtipo = $api->postJson('/api/v1/catalogo/subtipos', ['equipment_type_id' => $tipo, 'name' => 'Laparoscopia'])
            ->assertCreated()->json('data.id');
        $api->postJson('/api/v1/catalogo/subtipos', ['equipment_type_id' => $tipo, 'name' => 'Laparoscopia'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Ese subtipo ya existe dentro del tipo elegido.');

        $marca = $api->postJson('/api/v1/catalogo/marcas', ['subtype_id' => $subtipo, 'name' => 'Storz'])
            ->assertCreated()->assertJsonPath('data.subtypes.0.id', $subtipo)->json('data.id');
        $api->postJson('/api/v1/catalogo/marcas', ['subtype_id' => $subtipo, 'name' => 'Storz'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Esa marca ya está dada de alta en ese subtipo.');

        $modelo = $api->postJson('/api/v1/catalogo/modelos', ['subtype_id' => $subtipo, 'brand_id' => $marca, 'name' => 'Image1'])
            ->assertCreated()->json('data.id');
        $api->postJson('/api/v1/catalogo/modelos', ['subtype_id' => $subtipo, 'brand_id' => $marca, 'name' => 'Image1'])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Ese modelo ya existe para esa marca y subtipo.');

        // La cascada de los formularios.
        $api->getJson("/api/v1/catalogo/subtipos?equipment_type_id={$tipo}")->assertJsonCount(1, 'data');
        $api->getJson("/api/v1/catalogo/marcas?subtype_id={$subtipo}")->assertJsonPath('data.0.name', 'STORZ');
        $api->getJson("/api/v1/catalogo/modelos?brand_id={$marca}&subtype_id={$subtipo}")->assertJsonPath('data.0.id', $modelo);
        $api->getJson('/api/v1/catalogo/tipos')->assertJsonPath('data.0.subtypes_count', 1);

        // Mover la marca de subtipo se lleva sus modelos.
        $otro = Subtype::create(['equipment_type_id' => $tipo, 'name' => 'Artroscopia']);
        $api->putJson("/api/v1/catalogo/marcas/{$marca}", [
            'subtype_id' => $otro->id, 'subtype_anterior' => $subtipo, 'name' => 'Karl Storz',
        ])->assertOk()->assertJsonPath('data.name', 'KARL STORZ');
        $this->assertSame($otro->id, EquipmentModel::find($modelo)->subtype_id);

        // Quitarla del único subtipo la retira del catálogo con sus modelos.
        $api->deleteJson("/api/v1/catalogo/marcas/{$marca}?subtype_id={$otro->id}")->assertOk();
        $this->assertNull(Brand::find($marca));
        $this->assertNull(EquipmentModel::find($modelo));

        $api->deleteJson("/api/v1/catalogo/tipos/{$tipo}")->assertOk();
        $this->assertSame(0, Subtype::count());
    }

    public function test_alta_rapida_reutiliza_lo_que_existe(): void
    {
        $api = $this->catalogo();
        $datos = [
            'equipment_type_name' => 'Monitor', 'subtype_name' => 'Signos vitales',
            'brand_name' => 'Mindray', 'equipment_model_name' => 'iMEC 10',
        ];

        $api->postJson('/api/v1/catalogo/equipos', $datos)
            ->assertCreated()
            ->assertJsonPath('data.equipment_type.name', 'MONITOR')
            ->assertJsonPath('data.equipment_model.name', 'IMEC 10');
        $api->postJson('/api/v1/catalogo/equipos', $datos)->assertCreated();

        $this->assertSame(1, EquipmentType::count());
        $this->assertSame(1, Brand::count());

        $api->getJson('/api/v1/catalogo/equipos')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('totales.totalModels', 1);

        $api->postJson('/api/v1/catalogo/equipos', [])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['equipment_type_name', 'subtype_name', 'brand_name', 'equipment_model_name']]);

        // La cascada por nombre no pide permiso, igual que en la web.
        $this->conToken($this->usuarioApi())
            ->getJson('/api/v1/catalogo/equipos/subtipos?equipment_type_name=monitor')
            ->assertOk()->assertJsonPath('data.0.name', 'SIGNOS VITALES');
    }

    public function test_permisos_del_catalogo(): void
    {
        Category::create(['nombre' => 'Endoscopia']);

        $this->conToken($this->usuarioApi(['inventario.ver']))->getJson('/api/v1/catalogo/categorias')->assertForbidden();

        $llave = $this->conToken($this->usuarioApi(['inventario.catalogo']), ['inventario.ver'], TokenApi::TIPO_INTEGRACION);
        $llave->getJson('/api/v1/catalogo/tipos')->assertForbidden();
    }

    public function test_refacciones(): void
    {
        Subtype::create([
            'equipment_type_id' => EquipmentType::create(['name' => 'Torre'])->id,
            'name' => 'Laparoscopia',
        ]);

        $registra = $this->conToken($this->usuarioApi(['inventario.ver', 'inventario.registrar']));

        $registra->getJson('/api/v1/refacciones/catalogos')
            ->assertOk()->assertJsonPath('data.subtypes.0', 'LAPAROSCOPIA');

        $id = $registra->postJson('/api/v1/refacciones', [
            'name' => 'Cable de luz', 'subtype' => 'LAPAROSCOPIA', 'stock' => 4, 'compatible_with' => 'Storz',
        ])
            ->assertCreated()
            ->assertJsonPath('data.stock', 4)
            ->assertJsonPath('message', 'Refacción guardada correctamente.')
            ->json('data.id');

        $registra->postJson('/api/v1/refacciones', ['name' => 'Sin subtipo'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['subtype']]);

        $registra->getJson('/api/v1/refacciones')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('resumen.totalStock', 4)
            ->assertJsonPath('resumen.totalCompatible', 1);

        // Borrar pide inventario.eliminar.
        $registra->deleteJson("/api/v1/refacciones/{$id}")->assertForbidden();
        $this->conToken($this->usuarioApi(['inventario.eliminar']))->deleteJson("/api/v1/refacciones/{$id}")->assertOk();
        $this->assertSame(0, Refaccion::count());

        $this->conToken($this->usuarioApi())->getJson('/api/v1/refacciones')->assertForbidden();
    }

    /**
     * La web usa la misma lógica (se sacó de closures y del controlador):
     * tiene que seguir respondiendo igual que antes.
     */
    public function test_la_web_sigue_igual_tras_mover_la_logica(): void
    {
        $admin = $this->usuarioApi([], true);
        $tipo = EquipmentType::create(['name' => 'Torre']);
        Subtype::create(['equipment_type_id' => $tipo->id, 'name' => 'Laparoscopia']);

        $this->actingAs($admin)->post(route('configuracion.refaciones.store'), [
            'name' => 'Cable', 'subtype_name' => 'LAPAROSCOPIA', 'stock' => 2,
        ])->assertRedirect(route('configuracion.refaciones.index'))
            ->assertSessionHas('status', 'Refacción guardada correctamente.');
        $this->assertSame('LAPAROSCOPIA', Refaccion::firstOrFail()->subtype);

        $this->actingAs($admin)->get(route('configuracion.refaciones.index'))
            ->assertOk()->assertSee('Cable');
        $this->actingAs($admin)->get(route('configuracion.refaciones.create'))
            ->assertOk()->assertSee('LAPAROSCOPIA');

        // Un subtipo repetido regresa al formulario con el error en "name".
        $this->actingAs($admin)
            ->from(route('configuracion.catalogos.index'))
            ->post(route('configuracion.catalogo_equipo.subtipos.store'), ['equipment_type_id' => $tipo->id, 'name' => 'Laparoscopia'])
            ->assertRedirect(route('configuracion.catalogos.index'))
            ->assertSessionHasErrors(['name' => 'Ese subtipo ya existe dentro del tipo elegido.']);

        $this->actingAs($admin)->post(route('configuracion.catalogo_equipo.tipos.store'), ['name' => 'Monitor', 'requiere_emplayado' => 1])
            ->assertRedirect(route('configuracion.catalogos.index'))
            ->assertSessionHas('status', 'Tipo de equipo guardado correctamente.');
        $this->assertTrue(EquipmentType::where('name', 'MONITOR')->value('requiere_emplayado'));

        $this->actingAs($admin)->post(route('configuracion.tipos_equipo.store'), [
            'equipment_type_name' => 'Monitor', 'subtype_name' => 'Signos', 'brand_name' => 'Mindray', 'equipment_model_name' => 'X',
        ])->assertRedirect(route('configuracion.tipos_equipo.index'));
        $this->assertSame(2, EquipmentType::count());
    }
}
