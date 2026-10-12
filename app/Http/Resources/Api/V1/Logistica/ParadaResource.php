<?php

namespace App\Http\Resources\Api\V1\Logistica;

use App\Http\Resources\Api\V1\Logistica\Concerns\UrlCompleta;
use App\Models\RutaParadaItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una parada de la ruta: a dónde se va, qué se entrega, su geocerca
 * (`radio_m`) y cómo se cerró (receptor, firma, evidencias o el motivo
 * por el que no se entregó). Las horas de ventana van como "HH:MM".
 *
 * @mixin \App\Models\RutaParada
 */
class ParadaResource extends JsonResource
{
    use UrlCompleta;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ruta_id' => $this->ruta_id,
            'orden' => (int) $this->orden,
            'nombre' => $this->nombre,
            'direccion' => $this->direccion,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'radio_m' => (int) $this->radio_m,
            'customer_id' => $this->customer_id,
            'orden_salida_id' => $this->orden_salida_id,
            'contacto_nombre' => $this->contacto_nombre,
            'contacto_telefono' => $this->contacto_telefono,
            'ventana_desde' => $this->ventana_desde ? substr($this->ventana_desde, 0, 5) : null,
            'ventana_hasta' => $this->ventana_hasta ? substr($this->ventana_hasta, 0, 5) : null,
            'urgente' => (bool) $this->urgente,
            'instrucciones' => $this->instrucciones,
            'estado' => $this->estado,
            'estado_label' => $this->estadoLabel(),
            'distancia_desde_anterior_m' => $this->distancia_desde_anterior_m !== null ? (int) $this->distancia_desde_anterior_m : null,
            'duracion_desde_anterior_s' => $this->duracion_desde_anterior_s !== null ? (int) $this->duracion_desde_anterior_s : null,
            'eta' => $this->eta,
            'llegada_en' => $this->llegada_en,
            'llegada_origen' => $this->llegada_origen,
            'salida_en' => $this->salida_en,
            'completada_en' => $this->completada_en,
            'receptor_nombre' => $this->receptor_nombre,
            'firma_url' => self::urlCompleta($this->firmaUrl()),
            'motivo_no_entrega' => $this->motivo_no_entrega,
            'cierre_lat' => $this->cierre_lat,
            'cierre_lng' => $this->cierre_lng,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (RutaParadaItem $i) => [
                'id' => $i->id,
                'descripcion' => $i->descripcion,
                'cantidad' => (int) $i->cantidad,
                'orden' => (int) $i->orden,
            ])->values()),
            'evidencias' => EvidenciaResource::collection($this->whenLoaded('evidencias')),
            // Lo mínimo para enlazar con la orden; su detalle lo da la API de Almacén
            'orden_salida' => $this->whenLoaded('ordenSalida', fn () => $this->ordenSalida ? [
                'id' => $this->ordenSalida->id,
                'folio' => $this->ordenSalida->folio,
                'estado' => $this->ordenSalida->estado,
                'estado_label' => $this->ordenSalida->estadoLabel(),
            ] : null),
            'ruta' => new RutaResource($this->whenLoaded('ruta')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
