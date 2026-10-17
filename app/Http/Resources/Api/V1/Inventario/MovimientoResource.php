<?php

namespace App\Http\Resources\Api\V1\Inventario;

use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\InventoryMovement;
use App\Support\EdicionPropia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un renglón de la bitácora de inventario: una entrada (con la firma de
 * quien recibió) o una salida por venta.
 *
 * `tipo_vista` es como lo enseña la web: una salida que todavía no se
 * entrega físicamente sale como "vendido", no como "salida".
 *
 * @mixin InventoryMovement
 */
class MovimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'movement_type' => $this->movement_type,
            'tipo_vista' => $this->tipoVista(),
            'tipo_vista_label' => $this->tipoVistaLabel(),
            'item_type' => $this->item_type,
            'item_id' => $this->item_id,
            'item_code' => $this->item_code,
            'item_name' => $this->item_name,
            'condicion' => $this->condicion,
            'warehouse' => $this->warehouse,
            'destination_warehouse' => $this->destination_warehouse,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'stock_before' => $this->stock_before,
            'stock_after' => $this->stock_after,
            // En una salida, el folio de la venta que la generó.
            'reference' => $this->reference,
            'supplier' => $this->supplier,
            'movement_date' => $this->movement_date?->toDateString(),
            'entregado_en' => $this->entregado_en,
            'notes' => $this->notes,
            'checklist_recepcion' => $this->checklist_recepcion,
            'estado_general' => $this->estado_general,
            'metadata' => $this->metadata,
            // La evidencia de una entrada vive en cada pieza; aquí solo
            // queda la de registros viejos y la firma de quien recibió.
            'evidence_urls' => $this->evidenceUrls(),
            'signature_url' => $this->signatureUrl(),
            'video_url' => $this->videoUrl(),
            'created_by' => $this->created_by,
            // Solo entradas: una salida la generó una venta.
            'puede_eliminar' => $this->movement_type === InventoryMovement::TYPE_ENTRY
                && EdicionPropia::puedeEliminarMovimiento($request->user(), $this->resource),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => new UsuarioResource($this->whenLoaded('creator')),
            'piezas' => PiezaResource::collection($this->whenLoaded('seriales')),
        ];
    }
}
