<?php

namespace App\Http\Resources\Api\V1\Ventas;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una venta con sus montos. Los saldos (`total_cobrado`, `saldo`,
 * `estado_pago`…) salen cuando la respuesta trae los cobros cargados, que
 * es en el listado y en el detalle.
 *
 * @mixin \App\Models\Venta
 */
class VentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $conCobros = $this->relationLoaded('cobros');
        $conPlan = $this->relationLoaded('pagos') && $this->pagos->every(fn ($p) => $p->relationLoaded('cobros'));

        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'customer_id' => $this->customer_id,
            'seller_id' => $this->seller_id,
            'congreso_id' => $this->congreso_id,
            'cotizacion_id' => $this->cotizacion_id,
            // Nombre del congreso tal como se llamaba el día de la venta.
            'lugar_propuesta' => $this->lugar_propuesta,
            'nota_cliente' => $this->nota_cliente,
            // contado | financiamiento
            'modalidad' => $this->modalidad,
            'aplica_iva' => (bool) $this->aplica_iva,
            'subtotal' => (float) $this->subtotal,
            'descuento_tipo' => $this->descuento_tipo,
            'descuento_valor' => (float) $this->descuento_valor,
            'descuento_monto' => (float) $this->descuento_monto,
            'envio' => (float) $this->envio,
            'iva_monto' => (float) $this->iva_monto,
            // Equipo que el cliente entrega a cambio.
            'valor_a_cuenta' => (float) $this->valor_a_cuenta,
            'total' => (float) $this->total,
            'total_contrato' => (float) $this->total_contrato,
            // Lo que se le cobra de verdad: el total del contrato si hay valor a cuenta.
            'monto_exigible' => $this->montoExigible(),
            'plan_nombre' => $this->plan_nombre,
            'num_meses' => (int) $this->num_meses,
            'garantia_meses' => (int) $this->garantia_meses,
            'garantia_hasta' => $this->garantiaHasta()?->toDateString(),
            // borrador | confirmada | facturada | cancelada
            'estado' => $this->estado,
            'estado_label' => $this->estadoLabel(),
            'productos' => $this->when($this->relationLoaded('items'), fn () => $this->resumenProductos()),

            'total_cobrado' => $this->when($conCobros, fn () => $this->totalCobrado()),
            'saldo' => $this->when($conCobros, fn () => $this->saldo()),
            'avance' => $this->when($conCobros, fn () => $this->avance()),
            // pendiente | parcial | pagado
            'estado_pago' => $this->when($conCobros, fn () => $this->estadoPago()),
            'estado_pago_label' => $this->when($conCobros, fn () => $this->estadoPagoLabel()),
            // Con dinero sobre alguna parcialidad, al editar el plan se conserva.
            'plan_bloqueado' => $this->when($conPlan, fn () => $this->pagos->contains(fn ($p) => $p->cobros->isNotEmpty())),

            'customer' => new ClienteResumenResource($this->whenLoaded('customer')),
            'seller' => new UsuarioResource($this->whenLoaded('seller')),
            'congreso' => $this->whenLoaded('congreso', fn () => $this->congreso ? [
                'id' => $this->congreso->id,
                'nombre' => $this->congreso->nombre,
            ] : null),
            'cotizacion' => $this->whenLoaded('cotizacion', fn () => $this->cotizacion ? [
                'id' => $this->cotizacion->id,
                'folio' => $this->cotizacion->folio,
                'estado' => $this->cotizacion->estado,
            ] : null),
            'orden_salida' => $this->whenLoaded('ordenSalida', fn () => $this->ordenSalida ? [
                'id' => $this->ordenSalida->id,
                'folio' => $this->ordenSalida->folio,
                'estado' => $this->ordenSalida->estado,
                'estado_label' => $this->ordenSalida->estadoLabel(),
            ] : null),
            'items' => VentaItemResource::collection($this->whenLoaded('items')),
            'pagos' => VentaPagoResource::collection($this->whenLoaded('pagos')),
            'cobros' => CobroResource::collection($this->whenLoaded('cobros')),
            'fichas' => $this->whenLoaded('fichas', fn () => $this->fichas->map(fn ($f) => [
                'id' => $f->id,
                'titulo' => $f->titulo,
            ])->values()),
            'bitacora' => VentaBitacoraResource::collection($this->whenLoaded('bitacora')),

            'pdf_url' => route('api.v1.ventas.pdf', $this->id),
            'contrato_url' => $this->requiereContrato() ? route('api.v1.ventas.contrato', $this->id) : null,
            'garantia_url' => $this->tieneGarantia() ? route('api.v1.ventas.garantia', $this->id) : null,
            // La liga que se le comparte al cliente (sin sesión).
            'consulta_publica_url' => $this->public_token ? route('publico.venta', $this->public_token) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
