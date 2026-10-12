<?php

namespace App\Http\Resources\Api\V1\Administracion;

use App\Services\Administracion\Vehiculos;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Un vehículo de la empresa con sus fotos y documentos como URL.
 *
 * Los datos de operación (kilometraje, rendimiento, mantenimientos) los
 * lleva Logística; aquí salen solo para consulta.
 *
 * @mixin \App\Models\Vehicle
 */
class VehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $url = fn (?string $ruta) => $ruta ? Storage::disk('public')->url($ruta) : null;

        return [
            'id' => $this->id,
            'plate_number' => $this->plate_number,
            'vin' => $this->vin,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year !== null ? (int) $this->year : null,
            'color' => $this->color,
            'insurance_policy_number' => $this->insurance_policy_number,
            'status' => $this->status,
            'status_label' => Vehiculos::ESTADOS[$this->status] ?? $this->status,
            'engine_type' => $this->engine_type,
            'fuel_type' => $this->fuel_type,
            'load_capacity' => $this->load_capacity !== null ? (float) $this->load_capacity : null,
            'mileage' => $this->mileage !== null ? (int) $this->mileage : null,
            'fuel_efficiency' => $this->fuel_efficiency !== null ? (float) $this->fuel_efficiency : null,
            'tank_cost' => $this->tank_cost !== null ? (float) $this->tank_cost : null,
            'acquisition_date' => $this->fecha('acquisition_date'),
            'last_maintenance' => $this->fecha('last_maintenance'),
            'next_maintenance' => $this->fecha('next_maintenance'),
            'last_verification' => $this->fecha('last_verification'),
            'next_verification' => $this->fecha('next_verification'),
            'photos_url' => collect($this->photos ?? [])->map($url)->filter()->values(),
            'circulation_card_doc_url' => $url($this->circulation_card_doc),
            'verification_doc_url' => $url($this->verification_doc),
            'tenancy_doc_url' => $url($this->tenancy_doc),
            'insurance_doc_url' => $url($this->insurance_doc),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /** Las fechas del vehículo no tienen cast en el modelo: llegan como texto. */
    private function fecha(string $campo): ?string
    {
        $valor = $this->resource->getAttribute($campo);

        return $valor ? substr((string) $valor, 0, 10) : null;
    }
}
