<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\EquipmentType;
use App\Models\InventoryMovement;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\Subtype;
use App\Services\Inventario\EntradasDeInventario;
use App\Services\Inventario\ProductosDeInventario;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

class InventarioFiltrosCatalogoTest extends TestCase
{
    use DatabaseTransactions, UsaApi;

    private function catalogo(): array
    {
        $tipo = EquipmentType::create(['name' => 'FILTRO '.Str::uuid()]);
        $subtipo = Subtype::create(['equipment_type_id' => $tipo->id, 'name' => 'SUBTIPO '.Str::uuid()]);
        $marca = Brand::create(['name' => 'MARCA '.Str::uuid()]);
        $subtipo->brands()->attach($marca);
        return ['equipment_type_id' => $tipo->id, 'subtype_id' => $subtipo->id, 'brand_id' => $marca->id];
    }

    private function producto(array $catalogo): Producto
    {
        return Producto::create($catalogo + ['modelo' => 'MODELO '.Str::uuid(), 'stock' => 0, 'precio' => 100]);
    }

    private function movimiento(Producto $producto, array $extra = []): InventoryMovement
    {
        return InventoryMovement::create($extra + ['folio' => 'FLT-'.Str::upper(Str::random(15)), 'movement_type' => 'entrada', 'item_type' => 'producto', 'item_id' => $producto->id, 'item_name' => $producto->modelo, 'warehouse' => 'PRUEBA FILTROS', 'quantity' => 1, 'movement_date' => '2026-11-03', 'condicion' => 'usado']);
    }

    private function admin(): void
    {
        $user = $this->usuarioApi([], admin: true);
        $user->setAttribute('ficha_completada', true);
        $this->actingAs($user);
    }

    public function test_productos_filtra_antes_de_paginar_y_combina_busqueda(): void
    {
        $a = $this->catalogo();
        $b = $this->catalogo();
        $elegido = $this->producto($a);
        for ($i = 0; $i < 22; $i++) $this->producto($b);
        $this->admin();
        $res = $this->get(route('inventory.productos.index', $a))->assertOk();
        $this->assertSame([$elegido->id], $res->viewData('productos')->getCollection()->modelKeys());
        $this->assertSame(1, $res->viewData('productos')->total());
        $this->get(route('inventory.productos.index', $a + ['search' => 'NO-EXISTE-'.Str::uuid()]))->assertOk()->assertViewHas('productos', fn ($p) => $p->total() === 0);
        $res->assertSee('Aplicar filtros')->assertSee('data-live="off"', false);
    }

    public function test_movimientos_filtra_antes_de_paginar_y_persisten_los_controles(): void
    {
        $a = $this->catalogo();
        $b = $this->catalogo();
        $elegido = $this->movimiento($this->producto($a), ['movement_date' => '2025-01-01']);
        $otro = $this->producto($b);
        for ($i = 0; $i < 22; $i++) $this->movimiento($otro);
        $this->admin();
        $res = $this->get(route('inventory.movimientos.index', $a))->assertOk();
        $this->assertSame([$elegido->id], $res->viewData('movements')->getCollection()->modelKeys());
        $this->assertSame(1, $res->viewData('movements')->total());
        $this->assertSame($a['equipment_type_id'], (int) $res->viewData('filters')['equipment_type_id']);
        $this->assertSame([], $res->viewData('catalogo')['modelos']);
        $res->assertSee('name="movement_types[]"', false)->assertSee('id="filtrosMovimientos"', false);
        $this->get(route('inventory.movimientos.index', $a + ['movement_types' => ['salida']]))->assertOk()->assertViewHas('movements', fn ($m) => $m->total() === 0);
    }

    public function test_cada_id_y_sus_combinaciones_se_aplican_exactamente(): void
    {
        $a = $this->catalogo();
        $b = $this->catalogo();
        $p = $this->producto($a);
        $this->producto($b);
        foreach ($a as $campo => $id) {
            $q = app(ProductosDeInventario::class)->filtrar(Producto::query(), null, null, [$campo => $id]);
            $this->assertSame([$p->id], $q->pluck('id')->all());
        }
        $this->assertSame(0, app(ProductosDeInventario::class)->filtrar(Producto::query(), null, null, ['equipment_type_id' => $a['equipment_type_id'], 'subtype_id' => $b['subtype_id']])->count());
    }

    public function test_movimientos_combina_tipo_condicion_fechas_almacen_registrador_y_serie(): void
    {
        $a = $this->catalogo();
        $producto = $this->producto($a);
        $user = $this->usuarioApi([], admin: true);
        $elegido = $this->movimiento($producto, ['created_by' => $user->id]);
        $this->movimiento($producto, ['condicion' => 'nuevo']);
        $serie = 'FILTRO-'.Str::uuid();
        ProductoSerial::create(['producto_id' => $producto->id, 'inventory_movement_id' => $elegido->id, 'no_serie' => $serie, 'codigo' => 'MB-'.Str::random(15), 'estado' => 'disponible', 'condicion' => 'usado', 'vendido' => false]);
        $s = app(EntradasDeInventario::class);
        $q = $s->filtrar(InventoryMovement::query(), null, '2026-11-01', '2026-11-04');
        $s->filtrarListado($q, $a + ['movement_types' => ['entrada'], 'condiciones' => ['usado'], 'almacenes' => ['PRUEBA FILTROS'], 'quienes' => [$user->name], 'search' => $serie]);
        $this->assertSame([$elegido->id], $q->pluck('id')->all());
    }

    public function test_vendidas_y_entregadas_se_distinguen_y_pueden_combinarse(): void
    {
        $a = $this->catalogo();
        $p = $this->producto($a);
        $pendiente = $this->movimiento($p, ['movement_type' => 'salida']);
        $entregada = $this->movimiento($p, ['movement_type' => 'salida', 'entregado_en' => now()]);
        foreach ([['vendido'], ['salida'], ['vendido', 'salida']] as $tipos) {
            $q = app(EntradasDeInventario::class)->filtrarListado(InventoryMovement::query(), $a + ['movement_types' => $tipos])->orderBy('id');
            $esperados = $tipos === ['vendido'] ? [$pendiente->id] : ($tipos === ['salida'] ? [$entregada->id] : [$pendiente->id, $entregada->id]);
            $this->assertSame($esperados, $q->pluck('id')->all());
        }
        $this->admin();
        $this->get(route('inventory.movimientos.index', $a + ['tipo' => 'salida']))->assertOk()->assertViewHas('movements', fn ($m) => $m->total() === 2);
    }

    public function test_paginacion_conserva_filtros_y_muestra_todos_los_resultados(): void
    {
        $a = $this->catalogo();
        for ($i = 0; $i < 22; $i++) $this->movimiento($this->producto($a));
        $this->admin();
        foreach (['inventory.productos.index' => 'productos', 'inventory.movimientos.index' => 'movements'] as $ruta => $dato) {
            $primera = $this->get(route($ruta, $a))->assertOk()->viewData($dato);
            $this->assertSame(22, $primera->total());
            $this->assertSame(20, $primera->count());
            $this->assertStringContainsString('equipment_type_id='.$a['equipment_type_id'], $primera->nextPageUrl());
            $this->assertStringContainsString('brand_id='.$a['brand_id'], $primera->nextPageUrl());
            $segunda = $this->get(route($ruta, $a + ['page' => 2]))->assertOk()->viewData($dato);
            $this->assertSame(2, $segunda->count());
            $this->assertSame([], array_intersect($primera->getCollection()->modelKeys(), $segunda->getCollection()->modelKeys()));
        }
    }

    public function test_permisos_y_parametros_invalidos_no_se_saltan(): void
    {
        $user = $this->usuarioApi();
        $user->setAttribute('ficha_completada', true);
        $this->actingAs($user)->get(route('inventory.productos.index'))->assertForbidden();
        $this->get(route('inventory.movimientos.index'))->assertForbidden();
        $this->admin();
        foreach (['inventory.productos.index', 'inventory.movimientos.index'] as $ruta) {
            $this->getJson(route($ruta, ['brand_id' => ['incorrecto']]))->assertUnprocessable()->assertJsonValidationErrors('brand_id');
        }
    }
}
