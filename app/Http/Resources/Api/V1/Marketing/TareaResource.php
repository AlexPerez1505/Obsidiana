<?php

namespace App\Http\Resources\Api\V1\Marketing;

use App\Http\Resources\Api\V1\Servicios\UrlDeArchivo;
use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una tarea (pieza) de marketing: flyer, carrusel, video…
 *
 * `status`: pendiente → en_proceso → revision → completada. Si el revisor
 * la devuelve, regresa a pendiente con `rejection_comment` y conserva en
 * `approval_checklist` los índices del checklist que ya se cumplieron
 * (ver `GET /marketing/tareas/opciones`).
 *
 * @mixin \App\Models\Task
 */
class TareaResource extends JsonResource
{
    use UrlDeArchivo;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'task_description' => $this->task_description,
            'delivery_link' => $this->delivery_link,
            'status' => $this->status,
            'priority' => $this->priority,
            'tags' => $this->tags ?? [],
            'category' => $this->category,
            'platform' => $this->platform ?? [],
            'has_video' => (bool) $this->has_video,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'review_date' => $this->review_date?->format('Y-m-d'),
            'progress' => (int) $this->progress,
            'user_id' => $this->user_id,
            'user' => new UsuarioResource($this->whenLoaded('user')),
            'reviewer_id' => $this->reviewer_id,
            'reviewer' => new UsuarioResource($this->whenLoaded('reviewer')),
            'created_by' => $this->created_by,
            'creator' => new UsuarioResource($this->whenLoaded('creator')),
            'linked_piece' => $this->linked_piece,
            'rejection_comment' => $this->rejection_comment,
            'approval_checklist' => $this->approval_checklist ?? [],
            'project_image_url' => $this->urlDeArchivo($this->project_image),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
