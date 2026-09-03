<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

/**
 * Reglas compartidas para capturar números de serie al dar de alta unidades
 * de un producto: separar el textarea, autocompletar la secuencia cuando
 * solo se da un serial base, y reconocer errores de serial duplicado.
 *
 * La usan ProductoController (alta directa/rápida) e
 * InventoryMovementController (alta vía entrada con evidencia), para que las
 * dos capturen exactamente igual.
 */
trait ManejaSeriesDeProducto
{
    /**
     * Separa el textarea de números de serie (uno por línea o por coma).
     */
    private function parsearSeries(string $texto): Collection
    {
        return collect(preg_split('/[\r\n,]+/', $texto))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->values();
    }

    /**
     * Si el usuario solo escribió un serial pero está dando de alta varias
     * unidades, ese serial se toma como base y el resto se genera solo,
     * incrementando la parte numérica final (23A12345, 23A12346, 23A12347...).
     * Si el serial no termina en dígitos no hay forma de incrementarlo, así
     * que se deja tal cual (se validará el conteo más adelante).
     */
    private function autocompletarSecuencia(Collection $series, int $cantidad): Collection
    {
        if ($series->count() !== 1 || $cantidad <= 1) {
            return $series;
        }

        $generadas = $this->generarSecuencia($series->first(), $cantidad);

        return $generadas ? collect($generadas) : $series;
    }

    /**
     * Genera $cantidad seriales consecutivos a partir de uno base,
     * conservando el prefijo (letras, año, guiones...) y el ancho de la
     * parte numérica (con ceros a la izquierda).
     */
    private function generarSecuencia(string $base, int $cantidad): ?array
    {
        if (! preg_match('/^(.*?)(\d+)$/', $base, $m)) {
            return null;
        }

        [, $prefijo, $numero] = $m;
        $ancho = strlen($numero);
        $inicio = (int) $numero;

        return collect(range(0, $cantidad - 1))
            ->map(fn ($i) => $prefijo.str_pad((string) ($inicio + $i), $ancho, '0', STR_PAD_LEFT))
            ->all();
    }

    private function esErrorDeDuplicado(QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }
}
