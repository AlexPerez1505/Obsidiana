<?php

namespace App\Http\Resources\Api\V1\Inventario;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * La ficha del producto (su kardex): las piezas, la bitácora de entradas y
 * salidas y los conteos de los que sale el stock.
 *
 * @mixin Producto
 */
class ProductoDetalleResource extends ProductoResource
{
    /**
     * @param  array{piezas: Collection, movimientos: Collection, conteos: array<string, int>}  $kardex
     */
    public function __construct($resource, private array $kardex)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'conteos' => $this->kardex['conteos'],
            'piezas' => PiezaResource::collection($this->kardex['piezas']),
            'movimientos' => MovimientoResource::collection($this->kardex['movimientos']),
        ]);
    }
}
