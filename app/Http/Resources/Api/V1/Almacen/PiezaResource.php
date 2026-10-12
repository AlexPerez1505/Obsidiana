<?php

namespace App\Http\Resources\Api\V1\Almacen;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una pieza física (unidad con su etiqueta) vista desde almacén: dónde va
 * en su ruta de procesos y si está en un congreso. Sin precios.
 *
 * @mixin \App\Models\ProductoSerial
 */
class PiezaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'codigo' => $this->codigo,
            'no_serie' => $this->no_serie,
            'condicion' => $this->condicion,
            'estado' => $this->estado,
            'estado_label' => $this->estadoLabel(),
            'vendido' => $this->vendido,
            'congress_id' => $this->congress_id,
            'enviado_a_congreso_en' => $this->enviado_a_congreso_en,
            'foto_url' => $this->fotoUrl(),
            'updated_at' => $this->updated_at,
            // "Hojalatería y mantenimiento": lo que le falta para venderse.
            'falta' => $this->when($this->relationLoaded('procesos'), fn () => $this->faltaTexto()),
            'producto' => $this->whenLoaded('producto', fn () => $this->producto ? [
                'id' => $this->producto->id,
                'nombre' => $this->producto->nombreVisible(),
                'tipo_equipo' => $this->producto->tipo_equipo,
                'subtipo' => $this->producto->subtipo,
                'marca' => $this->producto->marca,
                'modelo' => $this->producto->modelo,
            ] : null),
            'procesos' => PiezaProcesoResource::collection($this->whenLoaded('procesos')),
        ];
    }
}
