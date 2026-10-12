<?php

namespace App\Http\Resources\Api\V1\Logistica;

use App\Http\Resources\Api\V1\Logistica\Concerns\UrlCompleta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un servicio, reparación, verificación, póliza… del vehículo. Con
 * `proximo_fecha` o `proximo_km` genera avisos en la flota.
 *
 * @mixin \App\Models\VehiculoMantenimiento
 */
class MantenimientoResource extends JsonResource
{
    use UrlCompleta;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'user_id' => $this->user_id,
            'tipo' => $this->tipo,
            'tipo_label' => $this->tipoLabel(),
            'fecha' => $this->fecha?->format('Y-m-d'),
            'odometro_km' => $this->odometro_km !== null ? (int) $this->odometro_km : null,
            'costo' => (float) $this->costo,
            'proveedor' => $this->proveedor,
            'descripcion' => $this->descripcion,
            'proximo_fecha' => $this->proximo_fecha?->format('Y-m-d'),
            'proximo_km' => $this->proximo_km !== null ? (int) $this->proximo_km : null,
            'comprobante_url' => self::urlCompleta($this->comprobanteUrl()),
            'created_at' => $this->created_at,
        ];
    }
}
