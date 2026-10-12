<?php

namespace App\Http\Resources\Api\V1\Comercial;

use App\Http\Resources\Api\V1\UsuarioResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un cliente o prospecto del directorio comercial.
 *
 * `asesor_id` es quien lo registró: sin `clientes.ver_todos` solo se ven
 * los propios. `como_conocio` solo trae texto cuando no se levantó en un
 * congreso (si vino de uno, el dato está en `congreso`).
 *
 * @mixin \App\Models\Customer
 */
class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'telefono' => $this->telefono,
            'rfc' => $this->rfc,
            'gmail' => $this->gmail,
            'direccion' => $this->direccion,
            'comentarios' => $this->comentarios,
            'categoria_id' => $this->categoria_id,
            'congreso_id' => $this->congreso_id,
            'como_conocio' => $this->como_conocio,
            'recibe_promocion' => (bool) $this->recibe_promocion,
            // Consentimiento de promociones: lo mueve el módulo de
            // promociones, aquí solo se lee.
            'promocion_autorizada_en' => $this->fechaHora('promocion_autorizada_en'),
            'promocion_autorizada_por' => $this->promocion_autorizada_por,
            'promocion_confirmada_en' => $this->fechaHora('promocion_confirmada_en'),
            'promocion_revocada_en' => $this->fechaHora('promocion_revocada_en'),
            'activo' => (bool) $this->activo,
            /** cliente | prospecto */
            'etapa' => $this->etapa,
            'etapa_label' => $this->etapaLabel(),
            'asesor_id' => $this->asesor_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'asesor' => UsuarioResource::make($this->whenLoaded('asesor')),
            'categoria' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'nombre' => $this->category->nombre,
            ] : null),
            'congreso' => $this->whenLoaded('congress', fn () => $this->congress ? [
                'id' => $this->congress->id,
                'nombre' => $this->congress->nombre,
                'fecha_inicio' => $this->congress->fecha_inicio?->toDateString(),
                'fecha_finalizacion' => $this->congress->fecha_finalizacion?->toDateString(),
            ] : null),
            // El siguiente pendiente, para mostrarlo en la lista sin abrir la ficha.
            'proximo_seguimiento' => $this->whenLoaded('seguimientos', fn () => ($s = $this->proximoSeguimiento())
                ? new SeguimientoResource($s)
                : null),
            'seguimientos' => SeguimientoResource::collection($this->whenLoaded('seguimientos')),
            'cotizaciones' => CotizacionResource::collection($this->whenLoaded('cotizaciones')),
        ];
    }

    /** Estas columnas no tienen cast en el modelo: llegan como texto de MySQL. */
    private function fechaHora(string $columna): ?string
    {
        $valor = $this->resource->getAttribute($columna);

        return $valor ? Carbon::parse($valor)->toJSON() : null;
    }
}
