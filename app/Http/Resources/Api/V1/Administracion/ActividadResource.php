<?php

namespace App\Http\Resources\Api\V1\Administracion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un evento de la línea de tiempo (LineaDeTiempo): quién hizo qué y cuándo.
 * `url` lleva a la pantalla web del registro.
 */
class ActividadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $e = $this->resource;

        return [
            'cuando' => $e['cuando']->toIso8601String(),
            'usuario_id' => $e['usuario_id'],
            'usuario' => $e['usuario'],
            'modulo' => $e['modulo'],
            'modulo_label' => $e['modulo_label'],
            'titulo' => $e['titulo'],
            'detalle' => $e['detalle'],
            'url' => $e['url'],
        ];
    }
}
