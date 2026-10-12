<?php

namespace App\Http\Resources\Api\V1\Ventas;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un borrador de factura: datos fiscales del receptor y los montos y
 * renglones copiados de la venta el día que se generó. Sin validez fiscal.
 *
 * @mixin \App\Models\Factura
 */
class FacturaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'venta_id' => $this->venta_id,
            'customer_id' => $this->customer_id,
            'rfc' => $this->rfc,
            'razon_social' => $this->razon_social,
            'uso_cfdi' => $this->uso_cfdi,
            'forma_pago' => $this->forma_pago,
            'metodo_pago' => $this->metodo_pago,
            'subtotal' => (float) $this->subtotal,
            'descuento_monto' => (float) $this->descuento_monto,
            'envio' => (float) $this->envio,
            'aplica_iva' => (bool) $this->aplica_iva,
            'iva_monto' => (float) $this->iva_monto,
            'total' => (float) $this->total,
            'observaciones' => $this->observaciones,
            // borrador | emitida | cancelada
            'estado' => $this->estado,
            'estado_label' => $this->estadoLabel(),
            'customer' => new ClienteResumenResource($this->whenLoaded('customer')),
            'venta' => $this->whenLoaded('venta', fn () => $this->venta ? [
                'id' => $this->venta->id,
                'folio' => $this->venta->folio,
                'estado' => $this->venta->estado,
                'total' => (float) $this->venta->total,
            ] : null),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'nombre' => $i->nombre,
                'modelo' => $i->modelo,
                'marca' => $i->marca,
                'cantidad' => (int) $i->cantidad,
                'no_series' => $i->no_series,
                'precio_unitario' => (float) $i->precio_unitario,
                'sobreprecio' => (float) $i->sobreprecio,
                'es_regalo' => (bool) $i->es_regalo,
                'importe' => $i->importe(),
                'orden' => (int) $i->orden,
            ])->values()),
            'pdf_url' => route('api.v1.facturas.pdf', $this->id),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
