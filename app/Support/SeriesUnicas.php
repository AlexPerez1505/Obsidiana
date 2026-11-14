<?php

namespace App\Support;

use App\Models\ProductoSerial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Un número de serie identifica UNA pieza en todo el inventario: no puede
 * repetirse ni dentro del mismo producto ni entre productos distintos
 * (aunque compartan subtipo, marca y modelo). Mayúsculas, minúsculas y
 * espacios a los lados no cuentan: "abc-1" y " ABC-1 " son la misma serie.
 *
 * Esta clase da el aviso claro antes de guardar (qué serie y en qué
 * producto está). La garantía final la da el índice único de la base sobre
 * producto_seriales.no_serie_unico, para cualquier camino que guarde series.
 */
class SeriesUnicas
{
    public static function normalizar(?string $serie): ?string
    {
        $limpia = mb_strtoupper(trim((string) $serie));

        return $limpia === '' ? null : $limpia;
    }

    /**
     * Las series de la lista que ya tiene otra pieza.
     *
     * @param  iterable<string|null>  $series
     * @param  array<int>  $ignorarIds  piezas que se están editando (no chocan consigo mismas)
     * @return Collection<string, array{serie: string, producto: string, etiqueta: ?string}> llave = serie normalizada
     */
    public static function enUso(iterable $series, array $ignorarIds = []): Collection
    {
        $buscar = collect($series)->map(fn ($s) => self::normalizar($s))->filter()->unique()->values();

        if ($buscar->isEmpty()) {
            return collect();
        }

        return ProductoSerial::query()
            ->with('producto:id,tipo_equipo,subtipo,marca,modelo')
            ->when($ignorarIds, fn ($q) => $q->whereNotIn('id', $ignorarIds))
            ->where(function ($q) use ($buscar) {
                foreach ($buscar->chunk(500) as $parte) {
                    $q->orWhereIn(DB::raw('UPPER(TRIM(no_serie))'), $parte->all());
                }
            })
            ->get(['id', 'producto_id', 'no_serie', 'codigo'])
            ->mapWithKeys(fn (ProductoSerial $p) => [self::normalizar($p->no_serie) => [
                'serie' => $p->no_serie,
                'producto' => $p->producto
                    ? (collect([$p->producto->nombreVisible(), $p->producto->marca, $p->producto->modelo])->filter()->implode(' ') ?: 'otro producto')
                    : 'otro producto',
                'etiqueta' => $p->codigo,
            ]]);
    }

    /** Las que se repiten dentro de la misma captura. */
    public static function repetidasEnLista(iterable $series): Collection
    {
        return collect($series)->map(fn ($s) => self::normalizar($s))->filter()->countBy()->filter(fn ($n) => $n > 1)->keys();
    }

    /**
     * Detiene el guardado si alguna serie ya existe o se repite en la lista.
     *
     * @throws ValidationException
     */
    public static function exigir(iterable $series, string $campo, array $ignorarIds = []): void
    {
        $series = collect($series);

        $repetidas = self::repetidasEnLista($series);
        if ($repetidas->isNotEmpty()) {
            throw ValidationException::withMessages([
                $campo => 'Hay números de serie repetidos en esta captura: '.$repetidas->implode(', ').'. Cada pieza debe tener el suyo.',
            ]);
        }

        $usadas = self::enUso($series, $ignorarIds);
        if ($usadas->isNotEmpty()) {
            throw ValidationException::withMessages([$campo => self::mensaje($usadas)]);
        }
    }

    public static function mensaje(Collection $usadas): string
    {
        $lista = $usadas->take(5)->map(fn ($u) => $u['serie'].' (ya la tiene '.$u['producto'].($u['etiqueta'] ? ', pieza '.$u['etiqueta'] : '').')')->implode('; ');

        return ($usadas->count() === 1 ? 'Ese número de serie ya está registrado en el inventario: ' : 'Esos números de serie ya están registrados en el inventario: ')
            .$lista.($usadas->count() > 5 ? ' y '.($usadas->count() - 5).' más' : '').'. No puede haber series duplicadas.';
    }

    /** Mensaje genérico cuando el índice de la base es quien detecta el choque. */
    public const MENSAJE_INDICE = 'Uno de esos números de serie ya está registrado en el inventario (en este u otro producto). No puede haber series duplicadas.';
}
