<?php

namespace App\Http\Resources\Api\V1\Almacen;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Un paso de la ruta de una pieza (hojalatería, mantenimiento, limpieza):
 * quién lo trabaja, desde cuándo y con qué evidencia se cerró.
 *
 * @mixin \App\Models\PiezaProceso
 */
class PiezaProcesoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $disco = config('filesystems.fotos_disk', 'public');

        return [
            'id' => $this->id,
            'producto_serial_id' => $this->producto_serial_id,
            'proceso' => $this->proceso,
            'proceso_nombre' => $this->nombre(),
            'orden' => $this->orden,
            'estado' => $this->estado,
            'iniciado_en' => $this->iniciado_en,
            'terminado_en' => $this->terminado_en,
            'dias_detenida' => $this->diasDetenida(),
            'responsable_id' => $this->responsable_id,
            'cerrado_por' => $this->cerrado_por,
            'motivo' => $this->motivo,
            'notas' => $this->notas,
            'trabajo_realizado' => $this->trabajo_realizado,
            'checklist_salida' => $this->checklist_salida,
            // Las fotos se guardan en el disco de fotos al cerrar el paso.
            'evidencias_urls' => collect($this->evidencias ?? [])
                ->map(fn (string $p) => Storage::disk($disco)->url($p))
                ->values()
                ->all(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'responsable' => new UsuarioResource($this->whenLoaded('responsable')),
            'cerrado_por_usuario' => new UsuarioResource($this->whenLoaded('cerradoPor')),
            'pieza' => new PiezaResource($this->whenLoaded('pieza')),
        ];
    }
}
