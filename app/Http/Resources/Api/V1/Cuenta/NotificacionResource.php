<?php

namespace App\Http\Resources\Api\V1\Cuenta;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una notificación de la campana. `datos` trae todo lo que guardó el aviso;
 * `url` es la pantalla web a la que lleva.
 *
 * @mixin \Illuminate\Notifications\DatabaseNotification
 */
class NotificacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => class_basename($this->type),
            // Los avisos no guardan todos las mismas llaves (titulo/texto o
            // title/message): aquí salen siempre igual.
            'titulo' => $this->data['titulo'] ?? $this->data['title'] ?? 'Notificación',
            'texto' => $this->data['texto'] ?? $this->data['message'] ?? null,
            'url' => $this->data['url'] ?? null,
            'datos' => $this->data,
            'leida' => $this->read_at !== null,
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
