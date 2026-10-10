<?php

namespace App\Http\Resources\Api\V1\Servicios;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Carta de garantía por tipo de equipo, con sus archivos (PDF, fotos…).
 *
 * @mixin \App\Models\GarantiaDocumento
 */
class CartaGarantiaResource extends JsonResource
{
    use UrlDeArchivo;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'nombre' => $this->nombre,
            'tipo_equipo' => $this->tipo_equipo,
            'venta_id' => $this->venta_id,
            'archivos_url' => collect($this->archivos ?? [])->map(fn ($ruta) => $this->urlDeArchivo($ruta))->values(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
