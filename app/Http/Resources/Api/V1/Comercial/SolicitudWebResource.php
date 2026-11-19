<?php

namespace App\Http\Resources\Api\V1\Comercial;

use App\Models\TiendaSolicitud;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudWebResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'tipo' => $this->tipo,
            'tipo_label' => TiendaSolicitud::TIPOS[$this->tipo] ?? $this->tipo,
            'motivo' => $this->motivo,
            'motivo_label' => $this->motivoTexto(),
            'nombre' => $this->nombre,
            'empresa' => $this->empresa,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'ciudad' => $this->ciudad,
            'especialidad' => $this->especialidad,
            'mensaje' => $this->mensaje,
            'estado' => $this->estado,
            'estado_label' => $this->estadoTexto(),
            'resumen' => $this->resumen(),
            'total_piezas' => $this->totalPiezas(),
            'articulos' => collect($this->articulos ?? [])->map(fn ($a) => [
                'nombre' => $a['nombre'] ?? '',
                'marca' => $a['marca'] ?? null,
                'modelo' => $a['modelo'] ?? null,
                'fuente' => $a['fuente'] ?? null,
                'disponible' => (bool) ($a['disponible'] ?? false),
                'cantidad' => (int) ($a['cantidad'] ?? 0),
            ])->values(),
            'customer_id' => $this->customer_id,
            'cliente' => $this->whenLoaded('cliente', fn () => $this->cliente ? [
                'id' => $this->cliente->id,
                'nombre' => trim($this->cliente->nombre.' '.$this->cliente->apellido),
            ] : null),
            'nota_interna' => $this->nota_interna,
            'atendida_por' => $this->whenLoaded('atendidaPor', fn () => $this->atendidaPor ? [
                'id' => $this->atendidaPor->id,
                'nombre' => $this->atendidaPor->name,
            ] : null),
            'atendida_en' => $this->atendida_en?->toJSON(),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
