<?php

namespace App\Http\Resources\Api\V1\Administracion;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Services\Administracion\SolicitudesDeMaterial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una solicitud de material.
 *
 * @mixin \App\Models\MaterialRequest
 */
class SolicitudMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'category' => $this->category,
            'material_name' => $this->material_name,
            'quantity' => (int) $this->quantity,
            'unit' => $this->unit,
            'required_date' => $this->required_date?->format('Y-m-d'),
            'urgency' => $this->urgency,
            'justification' => $this->justification,
            'status' => $this->status,
            'status_label' => SolicitudesDeMaterial::etiquetaEstado($this->status),
            'requested_by' => $this->requested_by,
            'solicitante' => new UsuarioResource($this->whenLoaded('requester')),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'reviewed_by' => $this->reviewed_by,
            'revisor' => new UsuarioResource($this->whenLoaded('reviewer')),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'review_notes' => $this->review_notes,
            'approved_quantity' => $this->approved_quantity !== null ? (int) $this->approved_quantity : null,
            'delivered_by' => $this->delivered_by,
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'delivery_notes' => $this->delivery_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
