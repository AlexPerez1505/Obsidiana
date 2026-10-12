<?php

namespace App\Http\Resources\Api\V1\Servicios;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El equipo que entra a servicio, tal como se capturó en la orden (texto
 * libre: puede no existir en el catálogo), con sus evidencias de entrada.
 *
 * @mixin \App\Models\ServiceEquipment
 */
class EquipoDeServicioResource extends JsonResource
{
    use UrlDeArchivo;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_code' => $this->product_code,
            'type_text' => $this->type_text,
            'subtype_text' => $this->subtype_text,
            'brand_text' => $this->brand_text,
            'model_text' => $this->model_text,
            'serial_number' => $this->serial_number,
            'description' => $this->description,
            'observations' => $this->observations,
            'evidence_1_url' => $this->urlDeArchivo($this->evidence_1_path),
            'evidence_2_url' => $this->urlDeArchivo($this->evidence_2_path),
            'evidence_3_url' => $this->urlDeArchivo($this->evidence_3_path),
            'video_url' => $this->urlDeArchivo($this->video_path),
        ];
    }
}
