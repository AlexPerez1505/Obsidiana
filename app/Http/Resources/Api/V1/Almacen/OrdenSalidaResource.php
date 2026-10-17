<?php

namespace App\Http\Resources\Api\V1\Almacen;

use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Una orden de salida: lo que almacén prepara, emplaya y firma cuando se
 * vende. De la venta solo viaja lo que almacén necesita para entregar
 * (folio, cliente, dirección, asesor); los montos se quedan en Ventas.
 *
 * @mixin \App\Models\OrdenSalida
 */
class OrdenSalidaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $conItems = $this->relationLoaded('items');

        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'venta_id' => $this->venta_id,
            'estado' => $this->estado,
            'estado_label' => $this->estadoLabel(),
            'notas' => $this->notas,
            'preparada_por' => $this->preparada_por,
            'preparada_en' => $this->preparada_en,
            'entregada_por' => $this->entregada_por,
            'entregada_en' => $this->entregada_en,
            'recibe_nombre' => $this->recibe_nombre,
            'firma_entrega_url' => $this->firmaEntregaUrl(),
            'firma_recibe_url' => $this->firmaRecibeUrl(),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Lo que la app necesita para saber qué botón enseñar.
            'cerrada' => $this->cerrada(),
            'lista_para_firmar' => $this->listaParaFirmar(),
            'todo_listo' => $this->when($conItems, fn () => $this->todoListo()),
            'puede_confirmar_preparacion' => $this->when($conItems, fn () => $this->puedeConfirmarPreparacion()),
            'avance' => $this->when($conItems, fn () => $this->avance()),

            'venta' => $this->whenLoaded('venta', fn () => $this->venta ? [
                'id' => $this->venta->id,
                'folio' => $this->venta->folio,
                'estado' => $this->venta->estado,
                'customer' => $this->venta->relationLoaded('customer') && $this->venta->customer ? [
                    'id' => $this->venta->customer->id,
                    'nombre' => $this->venta->customer->nombre,
                    'apellido' => $this->venta->customer->apellido,
                    'telefono' => $this->venta->customer->telefono,
                    'direccion' => $this->venta->customer->direccion,
                ] : null,
                'seller' => $this->venta->relationLoaded('seller') && $this->venta->seller
                    ? new UsuarioResource($this->venta->seller)
                    : null,
            ] : null),
            'preparada_por_usuario' => new UsuarioResource($this->whenLoaded('preparadaPor')),
            'entregada_por_usuario' => new UsuarioResource($this->whenLoaded('entregadaPor')),
            'items' => OrdenSalidaItemResource::collection($this->whenLoaded('items')),

            // Fotos y videos del equipo preparado, emplayado o entregado.
            'evidencias' => OrdenSalidaEvidenciaResource::collection($this->whenLoaded('evidencias')),
            'conteo_evidencias' => $this->when($this->relationLoaded('evidencias'), fn () => $this->conteoEvidencias()),
        ];
    }
}
