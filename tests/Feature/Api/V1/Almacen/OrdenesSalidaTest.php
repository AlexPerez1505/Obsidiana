<?php

namespace Tests\Feature\Api\V1\Almacen;

use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\InventoryMovement;
use App\Models\OrdenSalida;
use App\Models\Producto;
use App\Models\TokenApi;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsaApi;
use Tests\TestCase;

/**
 * Órdenes de salida por la API: mismo recorrido que en la web (marcar,
 * confirmar la preparación, firmar) y mismos "no" por estado.
 */
class OrdenesSalidaTest extends TestCase
{
    use RefreshDatabase, UsaApi;

    private function firma(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    }

    /** Una orden suelta con dos partidas que no se emplayan. */
    private function orden(): OrdenSalida
    {
        $vendedor = $this->usuarioApi();
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba', 'direccion' => 'Calle 1']);

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
            'estado' => OrdenSalida::PENDIENTE,
            'created_by' => $vendedor->id,
        ]);

        foreach (['Endoscopio', 'Monitor'] as $n => $nombre) {
            $orden->items()->create(['nombre' => $nombre, 'cantidad' => 1, 'requiere_emplayado' => false, 'orden' => $n]);
        }

        return $orden->fresh('items');
    }

    private function almacenista(): User
    {
        return $this->usuarioApi(['salidas.ver', 'salidas.preparar']);
    }

    public function test_lista_las_abiertas_con_conteos_y_el_detalle_trae_partidas(): void
    {
        $orden = $this->orden();
        $entregada = $this->orden();
        $entregada->forceFill(['estado' => OrdenSalida::ENTREGADA])->save();

        $api = $this->conToken($this->usuarioApi(['salidas.ver']));

        $api->getJson('/api/v1/ordenes-salida')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.folio', $orden->folio)
            ->assertJsonPath('data.0.venta.customer.nombre', 'Cliente')
            ->assertJsonPath('conteos.abiertas', 1)
            ->assertJsonPath('conteos.entregada', 1)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $api->getJson('/api/v1/ordenes-salida?estado=todas')->assertJsonCount(2, 'data');
        $api->getJson('/api/v1/ordenes-salida?estado=entregada')->assertJsonPath('data.0.id', $entregada->id);

        $api->getJson("/api/v1/ordenes-salida/{$orden->id}")
            ->assertOk()
            ->assertJsonPath('data.estado', OrdenSalida::PENDIENTE)
            ->assertJsonPath('data.puede_confirmar_preparacion', false)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.nombre', 'Endoscopio')
            ->assertJsonPath('data.preparada_por_usuario', null);
    }

    public function test_el_recorrido_completo_cambia_los_estados_como_en_la_web(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        $orden = $this->orden();
        $user = $this->almacenista();
        $api = $this->conToken($user);

        // Primera partida: en preparación.
        $api->patchJson("/api/v1/ordenes-salida/{$orden->id}/partidas/{$orden->items[0]->id}", ['preparado' => true])
            ->assertOk()
            ->assertJsonPath('data.estado', OrdenSalida::EN_PREPARACION)
            ->assertJsonPath('data.items.0.preparado', true)
            ->assertJsonPath('message', 'Partida actualizada.');

        // Sin terminar el checklist no se puede dejar lista.
        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/confirmar-preparacion")
            ->assertStatus(409)
            ->assertJsonPath('codigo', 'conflicto');

        $api->patchJson("/api/v1/ordenes-salida/{$orden->id}/partidas/{$orden->items[1]->id}", ['preparado' => true, 'observaciones' => ' Con caja '])
            ->assertJsonPath('data.items.1.observaciones', 'Con caja')
            // Terminar el checklist no la deja lista sola.
            ->assertJsonPath('data.estado', OrdenSalida::EN_PREPARACION)
            ->assertJsonPath('data.puede_confirmar_preparacion', true);

        // Firmar antes de confirmar la preparación: no.
        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/entregar", [
            'recibe_nombre' => 'Chofer', 'firma_entrega' => $this->firma(), 'firma_recibe' => $this->firma(),
        ])->assertStatus(409);

        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/confirmar-preparacion")
            ->assertOk()
            ->assertJsonPath('data.estado', OrdenSalida::LISTA)
            ->assertJsonPath('data.lista_para_firmar', true)
            ->assertJsonPath('data.preparada_por', $user->id);

        $api->putJson("/api/v1/ordenes-salida/{$orden->id}/notas", ['notas' => 'Frágil'])
            ->assertOk()->assertJsonPath('data.notas', 'Frágil');

        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/entregar", [
            'recibe_nombre' => 'Chofer de Prueba', 'firma_entrega' => $this->firma(), 'firma_recibe' => $this->firma(),
        ])
            ->assertOk()
            ->assertJsonPath('data.estado', OrdenSalida::ENTREGADA)
            ->assertJsonPath('data.recibe_nombre', 'Chofer de Prueba')
            ->assertJsonPath('data.entregada_por', $user->id);

        $orden->refresh();
        Storage::disk(config('filesystems.fotos_disk', 'public'))->assertExists($orden->firma_recibe_path);

        // Ya salió: no se toca.
        $api->patchJson("/api/v1/ordenes-salida/{$orden->id}/partidas/{$orden->items[0]->id}", ['preparado' => false])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Esta orden ya salió: no se puede modificar.');
    }

    public function test_desmarcar_anula_la_preparacion_confirmada(): void
    {
        $orden = $this->orden();
        $api = $this->conToken($this->almacenista());

        foreach ($orden->items as $item) {
            $api->patchJson("/api/v1/ordenes-salida/{$orden->id}/partidas/{$item->id}", ['preparado' => true]);
        }
        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/confirmar-preparacion")->assertOk();

        $api->patchJson("/api/v1/ordenes-salida/{$orden->id}/partidas/{$orden->items[0]->id}", ['preparado' => false])
            ->assertOk()
            ->assertJsonPath('data.estado', OrdenSalida::EN_PREPARACION)
            ->assertJsonPath('data.preparada_en', null);
    }

    public function test_firmar_por_api_cierra_la_salida_del_inventario(): void
    {
        Storage::fake(config('filesystems.fotos_disk', 'public'));

        // La venta se registra por la web: es la que crea la orden.
        $admin = $this->usuarioApi([], true);
        $tipo = EquipmentType::create(['name' => 'Torre '.uniqid()]);
        $producto = Producto::create([
            'equipment_type_id' => $tipo->id, 'tipo_equipo' => $tipo->name,
            'marca' => 'Olympus', 'modelo' => 'HD-900', 'precio' => 500, 'stock' => 0,
        ]);
        $producto->agregarUnidades(1, ['SN-A']);
        $customer = Customer::create(['nombre' => 'Cliente', 'apellido' => 'De Prueba']);

        $this->actingAs($admin)->post(route('commercial.ventas.store'), [
            'customer_id' => $customer->id,
            'modalidad' => 'contado',
            'items' => [[
                'tipo_item' => 'producto', 'producto_id' => $producto->id,
                'nombre' => 'Olympus HD-900', 'cantidad' => 1, 'precio_unitario' => 500,
            ]],
        ])->assertRedirect();

        $orden = OrdenSalida::where('venta_id', Venta::firstOrFail()->id)->firstOrFail();
        $salida = InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)->firstOrFail();
        $this->assertNull($salida->entregado_en);

        $api = $this->conToken($this->almacenista());

        foreach ($orden->items as $item) {
            $api->patchJson("/api/v1/ordenes-salida/{$orden->id}/partidas/{$item->id}", [
                'requiere_emplayado' => false, 'preparado' => true,
            ])->assertOk();
        }
        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/confirmar-preparacion")->assertOk();
        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/entregar", [
            'recibe_nombre' => 'Cliente', 'firma_entrega' => $this->firma(), 'firma_recibe' => $this->firma(),
        ])->assertOk();

        $this->assertNotNull($salida->fresh()->entregado_en, 'Al firmar, el movimiento pasa de "vendido" a salida consumada.');
    }

    public function test_validaciones_de_la_firma(): void
    {
        $orden = $this->orden();
        $api = $this->conToken($this->almacenista());

        foreach ($orden->items as $item) {
            $api->patchJson("/api/v1/ordenes-salida/{$orden->id}/partidas/{$item->id}", ['preparado' => true]);
        }
        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/confirmar-preparacion");

        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/entregar", [])
            ->assertStatus(422)
            ->assertJsonPath('errors.recibe_nombre.0', 'Escribe el nombre de quien se lleva el equipo.')
            ->assertJsonStructure(['errors' => ['firma_entrega', 'firma_recibe']]);

        $api->postJson("/api/v1/ordenes-salida/{$orden->id}/entregar", [
            'recibe_nombre' => 'Chofer', 'firma_entrega' => 'no-es-imagen', 'firma_recibe' => $this->firma(),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.firma_recibe.0', 'Alguna firma no es válida. Vuelve a firmar e intenta de nuevo.');

        $this->assertSame(OrdenSalida::LISTA, $orden->fresh()->estado);
    }

    public function test_la_partida_tiene_que_ser_de_la_orden(): void
    {
        $orden = $this->orden();
        $otra = $this->orden();

        $this->conToken($this->almacenista())
            ->patchJson("/api/v1/ordenes-salida/{$orden->id}/partidas/{$otra->items[0]->id}", ['preparado' => true])
            ->assertNotFound();
    }

    public function test_el_pdf_sale_igual_que_en_la_web(): void
    {
        $orden = $this->orden();

        $res = $this->conToken($this->usuarioApi(['salidas.ver']))
            ->get("/api/v1/ordenes-salida/{$orden->id}/pdf");

        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
    }

    public function test_permisos(): void
    {
        $orden = $this->orden();

        // Sin salidas.ver no ve nada.
        $this->conToken($this->usuarioApi())->getJson('/api/v1/ordenes-salida')->assertForbidden();

        // Con ver, pero sin preparar, no marca.
        $this->conToken($this->usuarioApi(['salidas.ver']))
            ->patchJson("/api/v1/ordenes-salida/{$orden->id}/partidas/{$orden->items[0]->id}", ['preparado' => true])
            ->assertForbidden()
            ->assertJsonPath('codigo', 'sin_permiso');

        // Una llave de integración de solo lectura tampoco, aunque el dueño pueda.
        $llave = $this->conToken($this->almacenista(), ['salidas.ver'], TokenApi::TIPO_INTEGRACION);
        $llave->getJson('/api/v1/ordenes-salida')->assertOk();
        $llave->postJson("/api/v1/ordenes-salida/{$orden->id}/confirmar-preparacion")->assertForbidden();

        $this->assertFalse($orden->fresh()->items[0]->preparado);
    }

    public function test_filtros_invalidos_dan_422(): void
    {
        $this->conToken($this->usuarioApi(['salidas.ver']))
            ->getJson('/api/v1/ordenes-salida?estado=otra&desde=10-10-2026')
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['estado', 'desde']]);
    }
}
