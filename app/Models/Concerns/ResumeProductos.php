<?php

namespace App\Models\Concerns;

/**
 * Una línea que dice qué se cotizó o vendió, para reconocer el documento
 * de un vistazo en los listados sin tener que abrirlo.
 *
 * Lo usan Cotizacion y Venta: los dos tienen `items` con marca, modelo y
 * nombre congelados al momento de guardar.
 */
trait ResumeProductos
{
    /**
     * "STRYKER CAMARA 1588 +2 más", o null si no tiene renglones.
     *
     * Se toma el primer renglón (ya vienen ordenados) y se cuenta el resto.
     * Conviene traer `items` con with() en los listados para no disparar
     * una consulta por fila.
     */
    public function resumenProductos(): ?string
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            return null;
        }

        $primero = $items->first();
        $etiqueta = trim(($primero->marca ?? '').' '.($primero->modelo ?? '')) ?: ($primero->nombre ?? '');

        if ($etiqueta === '') {
            $etiqueta = 'Producto sin nombre';
        }

        $restantes = $items->count() - 1;

        return $restantes > 0 ? "{$etiqueta} +{$restantes} más" : $etiqueta;
    }
}
