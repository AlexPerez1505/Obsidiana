<?php

namespace App\Http\Resources\Api\V1\Servicios;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una orden de servicio (mantenimiento interno o externo), como se ve en
 * el historial.
 *
 * `status`: registrado (espera aprobación) → en_progreso → validado →
 * entregado, o cancelado si se denegó.
 *
 * El token del QR no sale en la API: es la llave para avanzar la orden
 * sin sesión y en la web solo se ve impreso en la hoja del equipo. Con
 * `qr_expires_at` se sabe si hace falta renovarlo.
 *
 * @mixin \App\Models\Service
 */
class ServicioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_number' => $this->service_number,
            'service_type' => $this->service_type,
            'status' => $this->status,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'nombre' => $this->customer->nombre,
                'apellido' => $this->customer->apellido,
                'telefono' => $this->customer->telefono,
                'gmail' => $this->customer->gmail,
            ] : null),
            'internal_technician_id' => $this->internal_technician_id,
            'internal_technician' => new UsuarioResource($this->whenLoaded('internalTechnician')),
            'external_technician_id' => $this->external_technician_id,
            'external_technician' => new TecnicoExternoResource($this->whenLoaded('externalTechnician')),
            'registered_by' => $this->registered_by,
            'registered_by_user' => new UsuarioResource($this->whenLoaded('registeredBy')),
            'current_step_id' => $this->current_step_id,
            'current_step' => new PasoServicioResource($this->whenLoaded('currentStep')),
            'qr_expires_at' => $this->qr_expires_at,
            // La hoja de mantenimiento ya se llenó y guardó al menos una vez.
            'reporte_guardado' => $this->reporte_data !== null,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
