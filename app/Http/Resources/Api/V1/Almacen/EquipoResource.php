<?php

namespace App\Http\Resources\Api\V1\Almacen;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un equipo dado de alta en Configuración > Tipos de equipo: la
 * combinación tipo + subtipo + marca + modelo, con sus descripciones.
 *
 * @mixin \App\Models\Equipment
 */
class EquipoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipment_type_id' => $this->equipment_type_id,
            'subtype_id' => $this->subtype_id,
            'brand_id' => $this->brand_id,
            'equipment_model_id' => $this->equipment_model_id,
            'type_description' => $this->type_description,
            'subtype_description' => $this->subtype_description,
            'brand_description' => $this->brand_description,
            'model_description' => $this->model_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'equipment_type' => TipoEquipoResource::make($this->whenLoaded('equipmentType')),
            'subtype' => SubtipoResource::make($this->whenLoaded('subtype')),
            'brand' => MarcaResource::make($this->whenLoaded('brand')),
            'equipment_model' => ModeloResource::make($this->whenLoaded('equipmentModel')),
        ];
    }
}
