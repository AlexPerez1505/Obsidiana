<?php

namespace App\Http\Resources\Api\V1\Comercial;

use App\Services\Cotizaciones\BuscadorCotizador;
use Illuminate\Http\Request;

/**
 * Un cliente encontrado al armar una cotización o venta: sus datos y su
 * historial con nosotros, para saber con quién se está tratando.
 *
 * @mixin \App\Models\Customer
 */
class ClienteBuscadoResource extends ClienteResource
{
    public function toArray(Request $request): array
    {
        $historial = app(BuscadorCotizador::class)->historial($this->resource);

        return parent::toArray($request) + [
            'historial' => [
                // Ventas no canceladas.
                'compras' => $historial['compras'],
                'cotizaciones' => $historial['cotizaciones'],
                // Lo que todavía debe de esas ventas.
                'saldo' => (float) $historial['saldo'],
                'ultima_compra' => $historial['ultima_compra']?->toDateString(),
            ],
        ];
    }
}
