<?php

namespace App\Http\Resources\Api\V1\Servicios;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un paso recorrido (o por recorrer) de la orden: quién lo cerró, cuándo
 * y con qué evidencia. El token del QR no sale: es la llave para cerrar
 * el paso sin sesión y solo va impreso en la hoja del equipo.
 *
 * @mixin \App\Models\ServiceTracking
 */
class SeguimientoResource extends JsonResource
{
    use UrlDeArchivo;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_step_id' => $this->service_step_id,
            'service_step' => new PasoServicioResource($this->whenLoaded('serviceStep')),
            'status' => $this->status,
            'performed_by' => $this->performed_by,
            'performer' => new UsuarioResource($this->whenLoaded('performer')),
            'notes' => $this->notes,
            'evidence_1_url' => $this->urlDeArchivo($this->evidence_1_path),
            'evidence_2_url' => $this->urlDeArchivo($this->evidence_2_path),
            'evidence_3_url' => $this->urlDeArchivo($this->evidence_3_path),
            'video_url' => $this->urlDeArchivo($this->video_path),
            // Firma dibujada en el celular (data URL de imagen).
            'signature' => $this->signature,
            'qr_expires_at' => $this->qr_expires_at,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
        ];
    }
}
