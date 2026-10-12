<?php

namespace App\Services\Inventario;

use App\Models\Paquete;

/**
 * Paquetes armados a partir de productos: un nombre y qué productos lleva,
 * con cuántas piezas de cada uno.
 */
class Paquetes
{
    public const REGLAS = [
        'nombre' => ['required', 'string', 'max:255'],
        'productos' => ['required', 'array'],
        'productos.*.id' => ['required', 'exists:productos,id'],
        'productos.*.cantidad' => ['required', 'integer', 'min:1'],
    ];

    /**
     * Crea el paquete (o actualiza el que se pase) y deja sus productos
     * exactamente como vienen: los que no estén en la lista se quitan.
     */
    public function guardar(array $data, ?Paquete $paquete = null): Paquete
    {
        if ($paquete) {
            $paquete->update(['nombre' => $data['nombre']]);
        } else {
            $paquete = Paquete::create(['nombre' => $data['nombre']]);
        }

        $syncData = [];
        foreach ($data['productos'] as $producto) {
            $syncData[$producto['id']] = ['cantidad' => $producto['cantidad']];
        }
        $paquete->productos()->sync($syncData);

        return $paquete;
    }
}
