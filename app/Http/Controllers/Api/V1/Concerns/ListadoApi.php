<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Convenciones de los listados de la API, para que todos se pidan igual:
 *
 *   ?per_page=25        tamaño de página (máx. config('api.por_pagina_max'))
 *   ?page=2             página
 *   ?orden=-created_at  campo de orden; con '-' al frente es descendente
 *
 * Los filtros propios de cada módulo (buscar, estado, desde, hasta…) los
 * aplica cada controlador.
 */
trait ListadoApi
{
    protected function porPagina(Request $request): int
    {
        $n = (int) $request->query('per_page', config('api.por_pagina'));

        return max(1, min($n, (int) config('api.por_pagina_max')));
    }

    /**
     * Ordena por ?orden= solo si el campo está en la lista permitida;
     * si no viene (o no se permite), usa el orden por defecto.
     *
     * @param  list<string>  $permitidos
     */
    protected function ordenar(Builder $query, Request $request, array $permitidos, string $porDefecto = '-id'): Builder
    {
        $orden = (string) $request->query('orden', $porDefecto);
        $campo = ltrim($orden, '-');

        if (! in_array($campo, $permitidos, true)) {
            $orden = $porDefecto;
            $campo = ltrim($orden, '-');
        }

        return $query->orderBy($query->getModel()->qualifyColumn($campo), str_starts_with($orden, '-') ? 'desc' : 'asc');
    }
}
