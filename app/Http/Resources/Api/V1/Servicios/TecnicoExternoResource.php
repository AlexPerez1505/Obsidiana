<?php

namespace App\Http\Resources\Api\V1\Servicios;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Técnico o taller externo al que se manda un equipo a mantenimiento.
 *
 * @mixin \App\Models\ExternalTechnician
 */
class TecnicoExternoResource extends JsonResource
{
    use UrlDeArchivo;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'company' => $this->company,
            'specialty' => $this->specialty,
            'address' => $this->address,
            'location' => $this->location,
            'description' => $this->description,
            'photo_url' => $this->urlDeArchivo($this->photo),
            'is_active' => (bool) ($this->is_active ?? true),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
