<?php

namespace App\Http\Resources\Api\V1\Cuenta;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Una tarjeta del tablero tal como la tiene acomodada el usuario: qué es,
 * cuánto ocupa y sus datos ya calculados (DashboardWidgets).
 *
 * Si el usuario (o la llave) no tiene el permiso del módulo de la tarjeta,
 * sale con `disponible: false` y sin datos.
 *
 * Recibe un arreglo: id, w, h, definicion, disponible, datos.
 */
class TarjetaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $t = $this->resource;
        $def = $t['definicion'];

        return [
            'id' => $t['id'],
            'titulo' => $def['titulo'] ?? $t['id'],
            'descripcion' => $def['descripcion'] ?? null,
            'grupo' => $def['grupo'] ?? null,
            'w' => $t['w'],
            'h' => $t['h'],
            'disponible' => $t['disponible'],
            'datos' => $t['disponible'] ? $this->datos($t['id'], $t['datos'] ?? []) : null,
        ];
    }

    /** Las listas traen modelos: se mandan solo los campos que pinta la tarjeta. */
    private function datos(string $id, array $datos): array
    {
        if (! isset($datos['filas']) || ! $datos['filas'] instanceof Collection) {
            return $datos;
        }

        $datos['filas'] = $datos['filas']->map(fn (Model $m) => match ($id) {
            'ultimas_cotizaciones' => [
                'id' => $m->id,
                'folio' => $m->folio,
                'estado' => $m->estado,
                'total' => (float) $m->total,
                'cliente' => trim(($m->customer->nombre ?? '').' '.($m->customer->apellido ?? '')) ?: null,
                'productos' => $m->resumenProductos(),
                'created_at' => $m->created_at?->toIso8601String(),
            ],
            'ultimos_clientes' => [
                'id' => $m->id,
                'nombre' => $m->nombre,
                'apellido' => $m->apellido,
                'asesor' => $m->asesor?->name,
                'created_at' => $m->created_at?->toIso8601String(),
            ],
            'mis_tareas' => [
                'id' => $m->id,
                'title' => $m->title,
                'category' => $m->category,
                'status' => $m->status,
                'created_at' => $m->created_at?->toIso8601String(),
            ],
            default => ['id' => $m->getKey()],
        })->values()->all();

        return $datos;
    }
}
