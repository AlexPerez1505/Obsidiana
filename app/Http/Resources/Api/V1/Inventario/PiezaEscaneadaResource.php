<?php

namespace App\Http\Resources\Api\V1\Inventario;

use App\Models\ProductoSerial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Lo que se enseña al escanear una etiqueta con la pistola: lo justo para
 * la lista. Nada de precios: la pistola la usa cualquiera en el almacén.
 *
 * Si la pieza se fue a un congreso es lo primero que hay que saber: no está
 * en el almacén (aunque allá mismo se puede vender).
 *
 * @mixin ProductoSerial
 */
class PiezaEscaneadaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $producto = $this->producto;

        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'codigo' => $this->codigo,
            'equipo' => trim(collect([$producto?->tipo_equipo, $producto?->subtipo])->filter()->implode(' · ')) ?: 'Equipo',
            'marca_modelo' => trim(collect([$producto?->marca, $producto?->modelo])->filter()->implode(' ')),
            'no_serie' => $this->no_serie,
            'estado' => $this->estado,
            'estado_label' => $this->estadoLabel(),
            'condicion' => $this->condicion,
            'vendible' => $this->vendible(),
            // La de la pieza; si no tiene, la del producto.
            'foto_url' => $this->fotoUrl()
                ?: ($producto?->imagen_path ? Storage::disk('public')->url($producto->imagen_path) : null),
            'url_publica' => $this->urlPublica(),
            'congreso' => $this->congress ? [
                'id' => $this->congress->id,
                'nombre' => $this->congress->nombre,
            ] : null,
            'enviado_a_congreso_en' => $this->enviado_a_congreso_en,
        ];
    }
}
