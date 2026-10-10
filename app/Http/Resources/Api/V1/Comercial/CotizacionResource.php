<?php

namespace App\Http\Resources\Api\V1\Comercial;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Una cotización. Los montos los calcula siempre el servidor
 * (CalculadoraCotizacion): subtotal − descuento + envío, más IVA si
 * aplica; `total_contrato` es el total menos lo que se recibe a cuenta y
 * es lo que se reparte en el plan de pagos cuando es a financiamiento.
 *
 * @mixin \App\Models\Cotizacion
 */
class CotizacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** COT-AAAA-#### */
            'folio' => $this->folio,
            'customer_id' => $this->customer_id,
            'congreso_id' => $this->congreso_id,
            'seller_id' => $this->seller_id,
            // Nombre del congreso como se llamaba al guardar.
            'lugar_propuesta' => $this->lugar_propuesta,
            'nota_cliente' => $this->nota_cliente,
            /** contado | financiamiento */
            'modalidad' => $this->modalidad,
            'aplica_iva' => (bool) $this->aplica_iva,
            'subtotal' => (float) $this->subtotal,
            /** porcentaje | monto | null */
            'descuento_tipo' => $this->descuento_tipo,
            'descuento_valor' => (float) $this->descuento_valor,
            'descuento_monto' => (float) $this->descuento_monto,
            'envio' => (float) $this->envio,
            'iva_monto' => (float) $this->iva_monto,
            'valor_a_cuenta' => (float) $this->valor_a_cuenta,
            'total' => (float) $this->total,
            'total_contrato' => (float) $this->total_contrato,
            'plan_nombre' => $this->plan_nombre,
            'num_meses' => (int) $this->num_meses,
            'garantia_meses' => (int) $this->garantia_meses,
            /** borrador | enviada | aceptada | rechazada | convertida */
            'estado' => $this->estado,
            'estado_label' => $this->estadoLabel(),
            // La consulta pública del cliente (la misma del QR del PDF).
            'public_url' => $this->public_token ? route('publico.cotizacion', $this->public_token) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // "STRYKER CAMARA 1588 +2 más"
            'resumen_productos' => $this->whenLoaded('items', fn () => $this->resumenProductos()),
            'customer' => ClienteResource::make($this->whenLoaded('customer')),
            'seller' => UsuarioResource::make($this->whenLoaded('seller')),
            'congreso' => $this->whenLoaded('congreso', fn () => $this->congreso ? [
                'id' => $this->congreso->id,
                'nombre' => $this->congreso->nombre,
            ] : null),
            'items' => CotizacionItemResource::collection($this->whenLoaded('items')),
            'pagos' => CotizacionPagoResource::collection($this->whenLoaded('pagos')),
            // Se anexan al final del PDF.
            'fichas' => $this->whenLoaded('fichas', fn () => $this->fichas->map(fn ($f) => [
                'id' => $f->id,
                'titulo' => $f->titulo,
                'archivo_url' => $f->archivo ? Storage::disk('public')->url($f->archivo) : null,
            ])->values()),
        ];
    }
}
