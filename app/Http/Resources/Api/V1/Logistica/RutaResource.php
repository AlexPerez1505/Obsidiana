<?php

namespace App\Http\Resources\Api\V1\Logistica;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una ruta de reparto. Distancias en metros, duración en segundos y
 * `km_recorridos` con lo que de verdad marcó el GPS al cerrar la ruta.
 * `avance` cuenta las paradas ya resueltas.
 *
 * En el detalle (`conMapa()`) trae además los trazos para pintar el mapa:
 * `trazo_planeado` (el calculado al optimizar) y `trazo_real` (los puntos
 * GPS), ambos como `[[lat, lng], ...]`.
 *
 * @mixin \App\Models\Ruta
 */
class RutaResource extends JsonResource
{
    private bool $conMapa = false;

    /** Incluir los trazos del mapa (solo en el detalle: pueden ser miles de puntos). */
    public function conMapa(): static
    {
        $this->conMapa = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'estado' => $this->estado,
            'estado_label' => $this->estadoLabel(),
            'fecha_programada' => $this->fecha_programada?->format('Y-m-d'),
            'hora_salida' => $this->hora_salida ? substr($this->hora_salida, 0, 5) : null,
            'vehicle_id' => $this->vehicle_id,
            'chofer_id' => $this->chofer_id,
            'origen_nombre' => $this->origen_nombre,
            'origen_direccion' => $this->origen_direccion,
            'origen_lat' => $this->origen_lat,
            'origen_lng' => $this->origen_lng,
            'regresa_a_origen' => (bool) $this->regresa_a_origen,
            'exigir_evidencia' => (bool) $this->exigir_evidencia,
            'distancia_m' => $this->distancia_m !== null ? (int) $this->distancia_m : null,
            'duracion_s' => $this->duracion_s !== null ? (int) $this->duracion_s : null,
            'km_recorridos' => $this->km_recorridos !== null ? (float) $this->km_recorridos : null,
            'optimizada_con' => $this->optimizada_con,
            'optimizada_en' => $this->optimizada_en,
            'notas' => $this->notas,
            'iniciada_en' => $this->iniciada_en,
            'finalizada_en' => $this->finalizada_en,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'avance' => $this->when($this->relationLoaded('paradas'), fn () => $this->avance()),

            'vehicle' => new VehiculoResource($this->whenLoaded('vehicle')),
            'chofer' => new UsuarioResource($this->whenLoaded('chofer')),
            'creador' => new UsuarioResource($this->whenLoaded('creador')),
            'ultima_ubicacion' => new UbicacionResource($this->whenLoaded('ultimaUbicacion')),
            'paradas' => ParadaResource::collection($this->whenLoaded('paradas')),
            'eventos' => EventoResource::collection($this->whenLoaded('eventos')),
            'desviaciones' => DesviacionResource::collection($this->whenLoaded('desviaciones')),
            'incidencias' => IncidenciaResource::collection($this->whenLoaded('incidencias')),

            'trazo_planeado' => $this->when($this->conMapa, fn () => $this->trazo()),
            'trazo_real' => $this->when($this->conMapa, fn () => $this->ubicaciones()->select('lat', 'lng')->get()
                ->map(fn ($u) => [$u->lat, $u->lng])->all()),
        ];
    }
}
