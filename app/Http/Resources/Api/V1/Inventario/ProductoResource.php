<?php

namespace App\Http\Resources\Api\V1\Inventario;

use App\Models\Producto;
use App\Support\EdicionPropia;
use App\Support\PrecioVisible;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Un producto del inventario (un modelo de equipo) con su stock.
 *
 * El stock no se captura: es el conteo de piezas vendibles. `precio` solo
 * aparece si el usuario (y su token) tiene `precios.ver`, igual que en la
 * web, donde la columna ni se dibuja.
 *
 * @mixin Producto
 */
class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipment_type_id' => $this->equipment_type_id,
            'subtype_id' => $this->subtype_id,
            'brand_id' => $this->brand_id,
            'equipment_model_id' => $this->equipment_model_id,
            'tipo_equipo' => $this->tipo_equipo,
            'subtipo' => $this->subtipo,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            // Cómo se nombra en cotizaciones y ventas: por su subtipo.
            'nombre' => $this->nombreVisible(),
            'precio' => $this->when(
                PrecioVisible::para($request->user()),
                fn () => $this->precio === null ? null : (float) $this->precio,
            ),
            'stock' => (int) $this->stock,
            'descripcion' => $this->descripcion,
            // Opcional: la REF o número de parte del fabricante.
            'referencia' => $this->referencia,
            'proveedor' => $this->proveedor,
            // Caché de búsqueda: las series de las piezas sin vender.
            'no_serie' => $this->no_serie,
            'es_serializado' => (bool) $this->es_serializado,
            'imagen_url' => $this->imagen_path ? Storage::disk('public')->url($this->imagen_path) : null,
            'created_by' => $this->created_by,
            // Lo que este usuario (con este token) puede hacer ahora: con
            // permiso, o porque lo dio de alta hace menos de 8 horas.
            'puede_editar' => EdicionPropia::puedeEditarProducto($request->user(), $this->resource),
            'puede_eliminar' => EdicionPropia::puedeEliminarProducto($request->user(), $this->resource),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'piezas' => PiezaResource::collection($this->whenLoaded('seriales')),
            'piezas_disponibles' => PiezaResource::collection($this->whenLoaded('serialesDisponibles')),
            'ficha_tecnica' => new FichaTecnicaResource($this->whenLoaded('fichaTecnica')),
            // En un paquete: cuántas piezas de este producto lleva.
            'cantidad' => $this->whenPivotLoaded('paquete_producto', fn () => (int) $this->pivot->cantidad),
        ];
    }
}
