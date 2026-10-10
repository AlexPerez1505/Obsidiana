<?php

namespace App\Http\Resources\Api\V1\Administracion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Un documento del expediente del empleado (INE, CURP, acta, licencia…).
 *
 * @mixin \App\Models\EmployeeDocument
 */
class DocumentoEmpleadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size !== null ? (int) $this->file_size : null,
            'file_url' => $this->file_path ? Storage::disk('public')->url($this->file_path) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
