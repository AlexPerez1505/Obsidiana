<?php

namespace App\Http\Resources\Api\V1\Logistica;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\RutaParada;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * El estado vivo de una ruta, ligero para consultarlo cada pocos segundos:
 * dónde va el vehículo, cuál es la siguiente parada y cómo va cada una.
 *
 * @mixin \App\Models\Ruta
 */
class RutaEnVivoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $siguiente = $this->paradas->first(fn (RutaParada $p) => ! $p->resuelta());

        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'estado' => $this->estado,
            'estado_label' => $this->estadoLabel(),
            // false = la ruta ya no recibe GPS (se finalizó o canceló)
            'activa' => $this->activa(),
            'avance' => $this->avance(),
            'vehicle' => new VehiculoResource($this->whenLoaded('vehicle')),
            'chofer' => new UsuarioResource($this->whenLoaded('chofer')),
            'ultima_ubicacion' => new UbicacionResource($this->ultimaUbicacion),
            'siguiente_parada' => $siguiente ? [
                'id' => $siguiente->id,
                'orden' => (int) $siguiente->orden,
                'nombre' => $siguiente->nombre,
                'estado' => $siguiente->estado,
                'estado_label' => $siguiente->estadoLabel(),
                'eta' => $siguiente->eta,
            ] : null,
            'paradas' => $this->paradas->map(fn (RutaParada $p) => [
                'id' => $p->id,
                'orden' => (int) $p->orden,
                'nombre' => $p->nombre,
                'lat' => $p->lat,
                'lng' => $p->lng,
                'radio_m' => (int) $p->radio_m,
                'estado' => $p->estado,
                'estado_label' => $p->estadoLabel(),
                'eta' => $p->eta,
                'llegada_en' => $p->llegada_en,
                'salida_en' => $p->salida_en,
                'completada_en' => $p->completada_en,
            ])->values(),
        ];
    }
}
