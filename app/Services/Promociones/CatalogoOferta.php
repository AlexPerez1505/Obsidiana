<?php

namespace App\Services\Promociones;

use App\Models\Campana;
use App\Models\Paquete;
use App\Models\Producto;
use Illuminate\Support\Collection;

/**
 * Los productos y paquetes de una oferta, con el precio que dice la base
 * (nunca el del navegador). Usa los mismos datos que el cotizador.
 */
class CatalogoOferta
{
    /** @return Collection<int, array{clave: string, tipo_item: string, id: int, nombre: string, modelo: ?string, marca: ?string, imagen: ?string, precio: float}> */
    public function itemsDe(Campana $campana): Collection
    {
        return $this->resolver($campana->items->map(fn ($i) => $i->tipo_item.'-'.$i->ref_id)->all());
    }

    /**
     * @param  array<int, string>  $claves  "producto-12", "paquete-3"
     */
    public function resolver(array $claves): Collection
    {
        $ids = ['producto' => [], 'paquete' => []];
        foreach ($claves as $clave) {
            [$tipo, $id] = array_pad(explode('-', (string) $clave, 2), 2, null);
            if (isset($ids[$tipo]) && ctype_digit((string) $id)) {
                $ids[$tipo][] = (int) $id;
            }
        }

        $productos = Producto::whereIn('id', $ids['producto'])->get()->keyBy('id');
        $paquetes = Paquete::with(['productos', 'equipos'])->whereIn('id', $ids['paquete'])->get()->keyBy('id');

        $filas = collect();
        foreach ($claves as $clave) {
            [$tipo, $id] = array_pad(explode('-', (string) $clave, 2), 2, null);
            $id = (int) $id;

            if ($tipo === 'producto' && ($p = $productos->get($id))) {
                $filas->push([
                    'clave' => $clave, 'tipo_item' => 'producto', 'id' => $p->id,
                    'nombre' => $p->nombreVisible() ?: 'Producto', 'modelo' => $p->modelo, 'marca' => $p->marca,
                    'imagen' => $p->imagen_path ? asset('storage/'.$p->imagen_path) : null,
                    'precio' => round((float) $p->precio, 2),
                ]);
            } elseif ($tipo === 'paquete' && ($k = $paquetes->get($id))) {
                $filas->push([
                    'clave' => $clave, 'tipo_item' => 'paquete', 'id' => $k->id,
                    'nombre' => $k->nombre, 'modelo' => 'PAQUETE',
                    'marca' => $k->productos->pluck('marca')->filter()->unique()->implode(', ') ?: null,
                    'imagen' => $k->imagen ? asset('storage/'.$k->imagen) : null,
                    'precio' => round($k->precioCalculado(), 2),
                ]);
            }
        }

        return $filas->unique('clave')->values();
    }

    /** Renglones con la forma del cotizador, a partir de lo que eligió el cliente. */
    public function renglones(Collection $catalogo, array $cantidades): array
    {
        $max = (int) config('promociones.oferta.max_cantidad', 20);
        $items = [];

        foreach ($catalogo as $fila) {
            $cant = (int) ($cantidades[$fila['clave']] ?? 0);
            if ($cant < 1 || $fila['precio'] <= 0) {
                continue;
            }
            $items[] = [
                'tipo_item' => $fila['tipo_item'],
                'producto_id' => $fila['tipo_item'] === 'producto' ? $fila['id'] : null,
                'paquete_id' => $fila['tipo_item'] === 'paquete' ? $fila['id'] : null,
                'equipo_id' => null,
                'nombre' => $fila['nombre'],
                'modelo' => $fila['modelo'],
                'marca' => $fila['marca'],
                'imagen' => $fila['imagen'],
                'cantidad' => min($cant, $max),
                'precio_unitario' => $fila['precio'],
                'sobreprecio' => 0,
                'es_regalo' => false,
            ];
        }

        return $items;
    }
}
