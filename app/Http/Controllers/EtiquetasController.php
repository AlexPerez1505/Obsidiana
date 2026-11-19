<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\ProductoSerial;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Imprimir etiquetas de varias piezas a la vez: de los productos o las
 * entradas que se marcaron en sus listas. En la hoja se puede quitar la
 * marca a las que no se quieran y se imprimen todas las marcadas juntas.
 */
class EtiquetasController extends Controller
{
    /** Tope de una sola hoja, para no colgar el navegador. */
    public const MAXIMO = 1000;

    public function lote(Request $request): View
    {
        $data = $request->validate([
            'productos' => ['nullable', 'array', 'max:500'],
            'productos.*' => ['integer'],
            'movimientos' => ['nullable', 'array', 'max:500'],
            'movimientos.*' => ['integer'],
            'piezas' => ['nullable', 'array', 'max:'.self::MAXIMO],
            'piezas.*' => ['integer'],
            'vendidas' => ['nullable', 'boolean'],
        ]);

        $productos = $data['productos'] ?? [];
        $movimientos = $data['movimientos'] ?? [];
        $piezasIds = $data['piezas'] ?? [];

        $ids = collect();

        if ($productos) {
            // De un producto, las piezas que siguen en el inventario (no vendidas).
            $ids = $ids->merge(ProductoSerial::whereIn('producto_id', $productos)
                ->when(empty($data['vendidas']), fn ($q) => $q->where('vendido', false))
                ->pluck('id'));
        }

        if ($movimientos) {
            InventoryMovement::whereIn('id', $movimientos)->get()
                ->each(function (InventoryMovement $m) use (&$ids) {
                    $ids = $ids->merge($m->seriales()->pluck('id'));
                });
        }

        $ids = $ids->merge($piezasIds)->unique()->take(self::MAXIMO);

        $piezas = ProductoSerial::with('producto')->whereIn('id', $ids)->get()
            ->sortBy(fn ($p) => mb_strtolower(implode('|', [
                $p->producto?->tipo_equipo, $p->producto?->subtipo, $p->producto?->marca, $p->producto?->modelo, $p->codigo,
            ])))
            ->values();

        return view('structure.gestion_Inventario.entrada_salida.etiquetas_lote', [
            'piezas' => $piezas,
            'grupos' => $piezas->groupBy(fn ($p) => trim(($p->producto?->nombreVisible() ?? 'Sin producto').' '.$p->producto?->marca.' '.$p->producto?->modelo)),
            'volver' => url()->previous() !== url()->current() ? url()->previous() : route('inventory.productos.index'),
            'vendidas' => ! empty($data['vendidas']),
            'conProductos' => (bool) $productos,
            'recortado' => $ids->count() >= self::MAXIMO,
        ]);
    }
}
