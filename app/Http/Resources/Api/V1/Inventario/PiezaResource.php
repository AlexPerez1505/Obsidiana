<?php

namespace App\Http\Resources\Api\V1\Inventario;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\ProductoSerial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una pieza física de un producto (fila de producto_seriales): su etiqueta
 * interna (la del QR), su serie, cómo llegó y por dónde va. Sin precios.
 *
 * @mixin ProductoSerial
 */
class PiezaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'codigo' => $this->codigo,
            'no_serie' => $this->no_serie,
            'condicion' => $this->condicion,
            'estado' => $this->estado,
            'estado_label' => $this->estadoLabel(),
            // Una pieza en hojalatería o mantenimiento todavía no se vende.
            'vendible' => $this->vendible(),
            'vendido' => (bool) $this->vendido,
            'vendido_en' => $this->vendido_en,
            'venta_item_id' => $this->venta_item_id,
            'inventory_movement_id' => $this->inventory_movement_id,
            // Sigue vendible mientras está en un congreso, pero no está en el anaquel.
            'congress_id' => $this->congress_id,
            'enviado_a_congreso_en' => $this->enviado_a_congreso_en,
            'capturado_por' => $this->capturado_por,
            'editado_por' => $this->editado_por,
            'foto_url' => $this->fotoUrl(),
            'evidence_urls' => $this->evidenceUrls(),
            'video_url' => $this->videoUrl(),
            // A dónde lleva el QR pegado a la pieza: su ficha pública.
            'url_publica' => $this->urlPublica(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // "Hojalatería y mantenimiento": lo que le falta para venderse.
            'falta' => $this->when($this->relationLoaded('procesos'), fn () => $this->faltaTexto()),
            'procesos' => $this->whenLoaded('procesos', fn () => $this->procesos->map(fn ($p) => [
                'id' => $p->id,
                'proceso' => $p->proceso,
                'proceso_nombre' => $p->nombre(),
                'orden' => $p->orden,
                'estado' => $p->estado,
                'motivo' => $p->motivo,
                'iniciado_en' => $p->iniciado_en,
                'terminado_en' => $p->terminado_en,
            ])->values()),
            'producto' => new ProductoResource($this->whenLoaded('producto')),
            'entrada' => $this->whenLoaded('entrada', fn () => $this->entrada ? [
                'id' => $this->entrada->id,
                'folio' => $this->entrada->folio,
                'movement_date' => $this->entrada->movement_date?->toDateString(),
            ] : null),
            'congreso' => $this->whenLoaded('congress', fn () => $this->congress ? [
                'id' => $this->congress->id,
                'nombre' => $this->congress->nombre,
            ] : null),
            'venta' => $this->whenLoaded('ventaItem', fn () => $this->ventaItem?->relationLoaded('venta') && $this->ventaItem->venta ? [
                'id' => $this->ventaItem->venta->id,
                'folio' => $this->ventaItem->venta->folio,
            ] : null),
            'capturado_por_usuario' => new UsuarioResource($this->whenLoaded('capturadoPor')),
        ];
    }
}
