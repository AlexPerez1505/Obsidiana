<?php

namespace App\Http\Resources\Api\V1\Logistica;

use App\Http\Resources\Api\V1\Logistica\Concerns\UrlCompleta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Foto o remisión firmada que el chofer subió al cerrar una entrega.
 *
 * @mixin \App\Models\RutaEvidencia
 */
class EvidenciaResource extends JsonResource
{
    use UrlCompleta;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ruta_parada_id' => $this->ruta_parada_id,
            'tipo' => $this->tipo,
            'archivo_url' => self::urlCompleta($this->url()),
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'subida_por' => $this->subida_por,
            'created_at' => $this->created_at,
        ];
    }
}
