<?php

namespace App\Http\Resources\Api\V1\Comercial;

use App\Services\Cotizaciones\BuscadorCotizador;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un paquete activo que se puede agregar como un solo renglón. Su precio
 * es el que calcula el paquete con sus productos.
 *
 * @mixin \App\Models\Paquete
 */
class PaqueteCotizableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** paquete */
            'tipo_item' => 'paquete',
            'paquete_id' => $this->id,
            'nombre' => $this->nombre,
            'modelo' => 'PAQUETE',
            'marca' => app(BuscadorCotizador::class)->marcasDe($this->resource),
            'precio' => (float) $this->precioCalculado(),
            'imagen_url' => $this->imagen ? asset('storage/'.$this->imagen) : null,
        ];
    }
}
