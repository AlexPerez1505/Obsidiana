<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\Venta;
use App\Models\VentaItem;
use Illuminate\Support\Collection;

/**
 * Lo que una venta le hace al inventario.
 *
 * Vivía dentro de VentaController; se sacó aquí para que la venta rápida
 * (mostrador / congreso) descuente stock exactamente igual que la venta
 * completa: mismas reglas de qué pieza sale, misma bitácora de salida,
 * misma forma de regresar piezas al cancelar.
 */
class InventarioDeVentas
{
    /**
     * ¿El equipo de esta venta ya salió físicamente del almacén?
     *
     * Lo dice la salida de inventario: nace como venta pendiente de entrega
     * y solo se marca entregada cuando se firma su orden de salida. A partir
     * de ese momento, editar o cancelar la venta regresaría al stock piezas
     * que el cliente ya tiene, y borraría del historial una salida firmada.
     */
    public function equipoYaSalio(Venta $venta): bool
    {
        $itemIds = $venta->items()->pluck('id');

        if ($itemIds->isEmpty()) {
            return false;
        }

        return InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)
            ->whereIn('metadata->venta_item_id', $itemIds->all())
            ->whereNotNull('entregado_en')
            ->exists();
    }

    /**
     * Cuando se edita o cancela una venta, las unidades que había vendido
     * regresan al inventario disponible: el serial deja de estar "vendido"
     * y su stock se vuelve a contar.
     */
    public function liberarSerialesDe(Venta $venta): void
    {
        $itemIds = $venta->items()->pluck('id');

        if ($itemIds->isEmpty()) {
            return;
        }

        $productoIds = ProductoSerial::whereIn('venta_item_id', $itemIds)->pluck('producto_id')->unique();

        // Al liberarlas vuelven a estar disponibles: si tenían pendiente
        // algún proceso no se habrían podido vender, así que no hay ruta
        // a la que regresarlas.
        ProductoSerial::whereIn('venta_item_id', $itemIds)->update([
            'vendido' => false,
            'vendido_en' => null,
            'venta_item_id' => null,
            'estado' => 'disponible',
        ]);

        Producto::whereIn('id', $productoIds)->get()->each->recalcularStock();

        // La salida que se había registrado para esos renglones ya no
        // corresponde: se retira de la bitácora junto con el renglón.
        InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)
            ->whereIn('metadata->venta_item_id', $itemIds->all())
            ->delete();
    }

    /**
     * Al registrar la venta se toman, de las unidades disponibles de ese
     * producto, las más antiguas (FIFO) hasta cubrir la cantidad vendida.
     * Quedan marcadas como vendidas y ligadas a este renglón, y el stock del
     * producto se recalcula a partir de las que sigan disponibles.
     */
    public function asignarSerialesVendidos(VentaItem $item, array $elegidas = []): void
    {
        $producto = Producto::find($item->producto_id);

        if (! $producto) {
            return;
        }

        $seriales = $this->descontarStockProducto($producto, (int) $item->cantidad, $item, $elegidas);

        $item->update([
            'no_series' => $seriales->pluck('no_serie')->filter()->implode(', ') ?: null,
        ]);
    }

    /**
     * Un paquete no tiene stock propio: al venderlo, cada producto que lo
     * compone se descuenta como si se hubiera vendido directamente
     * (mismo FIFO, misma bitácora de salida), multiplicando la cantidad
     * vendida del paquete por la cantidad de ese producto en el pivote.
     *
     * Las unidades quedan ligadas al mismo venta_item_id sin importar de
     * qué producto vinieron, para que editar o cancelar la venta las
     * libere todas juntas (liberarSerialesDe ya no filtra por producto).
     */
    public function descontarStockDePaquete(VentaItem $item): void
    {
        $paquete = Paquete::with('productos')->find($item->paquete_id);

        if (! $paquete) {
            return;
        }

        $todosLosSeriales = collect();

        foreach ($paquete->productos as $producto) {
            $necesaria = (int) $item->cantidad * (int) $producto->pivot->cantidad;

            $todosLosSeriales = $todosLosSeriales->merge(
                $this->descontarStockProducto($producto, $necesaria, $item)
            );
        }

        $item->update([
            'no_series' => $todosLosSeriales->pluck('no_serie')->filter()->implode(', ') ?: null,
        ]);
    }

    /**
     * Núcleo compartido: toma, FIFO, las unidades disponibles de un
     * producto, las marca vendidas ligadas a $item, recalcula su stock y
     * deja la salida en la bitácora. Lo usan tanto la venta de un producto
     * suelto como cada producto dentro de un paquete vendido.
     */
    public function descontarStockProducto(Producto $producto, int $cantidad, VentaItem $item, array $elegidas = []): Collection
    {
        $stockAntes = $producto->stock;

        /*
        | Solo se venden piezas que terminaron su ruta de procesos: una que
        | sigue en hojalatería o mantenimiento existe, pero no se puede
        | entregar todavía.
        */
        $disponibles = ProductoSerial::where('producto_id', $producto->id)
            ->where('vendido', false)
            ->whereNotIn('estado', ProductoSerial::NO_VENDIBLES);

        /*
        | Si el asesor eligió piezas concretas, esas son las que salen. Se
        | vuelven a filtrar contra las disponibles porque entre que armó la
        | venta y la guardó, alguna pudo venderse o irse a mantenimiento.
        |
        | Si eligió menos de las que vende, el resto se completa con las más
        | antiguas; si no eligió ninguna, es el comportamiento de siempre.
        */
        $seriales = collect();

        if ($elegidas) {
            $seriales = (clone $disponibles)
                ->whereIn('id', $elegidas)
                ->orderBy('id')
                ->take($cantidad)
                ->get();
        }

        $faltan = $cantidad - $seriales->count();

        if ($faltan > 0) {
            /*
            | Al completar solo, primero las que están en el almacén.
            |
            | Una pieza que se fue a un congreso sí se puede vender (allá
            | mismo la venden), pero si el sistema la toma por ser la más
            | antigua, almacén va a buscarla al anaquel y no está. Si de
            | verdad se vendió la del congreso, el asesor la elige a mano.
            */
            $seriales = $seriales->merge(
                (clone $disponibles)
                    ->whereNotIn('id', $seriales->pluck('id')->all() ?: [0])
                    ->orderByRaw('CASE WHEN congress_id IS NULL THEN 0 ELSE 1 END')
                    ->orderBy('id')
                    ->take($faltan)
                    ->get()
            );
        }

        foreach ($seriales as $serial) {
            $serial->update([
                'vendido' => true,
                'vendido_en' => now(),
                'venta_item_id' => $item->id,
                // El estado también lo dice, para que la ficha del QR y las
                // colas de proceso no la sigan mostrando como disponible.
                'estado' => 'vendido',
            ]);
        }

        $producto->recalcularStock();

        InventoryMovement::create([
            'folio' => InventoryMovement::siguienteFolio(InventoryMovement::TYPE_EXIT),
            'movement_type' => InventoryMovement::TYPE_EXIT,
            'item_type' => InventoryMovement::ITEM_PRODUCT,
            'item_id' => $producto->id,
            'item_code' => (string) $producto->id,
            'item_name' => trim($producto->marca.' '.$producto->modelo) ?: $producto->tipo_equipo,
            'warehouse' => 'Almacen Central',
            'quantity' => $cantidad,
            'unit' => 'Pza',
            'stock_before' => $stockAntes,
            'stock_after' => $producto->stock,
            'reference' => $item->venta->folio ?? null,
            'movement_date' => now(),
            'metadata' => ['venta_item_id' => $item->id],
            'created_by' => auth()->id(),
        ]);

        return $seriales;
    }

    /**
     * Cierra la salida de inventario de una venta como ya entregada.
     *
     * En una venta de mostrador o congreso el cliente se lleva la pieza en
     * la mano: no hay orden que preparar ni firma que esperar. Es lo mismo
     * que hace la firma de la orden de salida, solo que aquí ocurre en el
     * momento de vender.
     */
    public function marcarEntregada(Venta $venta, $cuando = null): void
    {
        $itemIds = $venta->items()->pluck('id');

        if ($itemIds->isEmpty()) {
            return;
        }

        InventoryMovement::where('movement_type', InventoryMovement::TYPE_EXIT)
            ->whereIn('metadata->venta_item_id', $itemIds->all())
            ->whereNull('entregado_en')
            ->update(['entregado_en' => $cuando ?? now()]);
    }
}
