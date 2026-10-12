<?php

namespace App\Http\Resources\Api\V1\Almacen;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Alguien que asistió a un congreso (ponente, distribuidor...). No es
 * cliente ni usuario: es un registro libre del propio congreso.
 *
 * @mixin \App\Models\CongresoParticipante
 */
class CongresoParticipanteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'congress_id' => $this->congress_id,
            'nombre' => $this->nombre,
            'rol' => $this->rol,
            'empresa' => $this->empresa,
            'created_at' => $this->created_at,
        ];
    }
}
