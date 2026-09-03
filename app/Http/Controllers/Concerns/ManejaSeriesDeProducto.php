<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ProductoSerial;
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

    /**
     * El siguiente serial de un modelo es consecutivo al último que se
     * registró, comparando numéricamente (no como texto, para que
     * 23A12350 no se confunda con "menor" que 23A9999). Funciona con
     * cualquier prefijo (letras, año, guiones...) siempre que el serial
     * termine en dígitos; si no, no se sugiere nada y se deja en blanco.
     */
    private function sugerirSiguienteSerial(int $productoId): ?string
    {
        $ultimo = ProductoSerial::where('producto_id', $productoId)
            ->whereNotNull('no_serie')
            ->where('no_serie', '!=', '')
            ->pluck('no_serie')
            ->map(function ($serie) {
                if (! preg_match('/^(.*?)(\d+)$/', $serie, $m)) {
                    return null;
                }

                return ['prefijo' => $m[1], 'numero' => (int) $m[2], 'ancho' => strlen($m[2])];
            })
            ->filter()
            ->sortByDesc(fn ($s) => $s['numero'])
            ->first();

        if ($ultimo === null) {
            return null;
        }

        $siguiente = $ultimo['numero'] + 1;

        return $ultimo['prefijo'].str_pad((string) $siguiente, $ultimo['ancho'], '0', STR_PAD_LEFT);
    }

    /**
     * Revisa una lista de series (por índice, para que cuadre con las fotos
     * de cada renglón) contra lo que ya existe en la base para ese producto
     * y contra el resto del mismo lote. Las que chocan no se descartan por
     * completo: se limpian a null (la unidad y su foto se conservan, solo
     * se pierde la captura de ESE serial) y se listan como rechazadas para
     * avisarle al usuario.
     *
     * @param  Collection<int, string|null>  $series
     * @return array{series: Collection<int, string|null>, rechazadas: Collection<int, string>}
     */
    private function depurarSeriesDuplicadas(int $productoId, Collection $series): array
    {
        $noVacias = $series->filter()->values();

        $existentes = $noVacias->isEmpty() ? [] : ProductoSerial::where('producto_id', $productoId)
            ->whereIn('no_serie', $noVacias->all())
            ->pluck('no_serie')
            ->all();

        $vistos = [];
        $limpias = collect();
        $rechazadas = collect();

        foreach ($series as $serie) {
            if ($serie === null || $serie === '') {
                $limpias->push(null);
                continue;
            }

            if (in_array($serie, $existentes, true) || in_array($serie, $vistos, true)) {
                $rechazadas->push($serie);
                $limpias->push(null);
                continue;
            }

            $vistos[] = $serie;
            $limpias->push($serie);
        }

        return ['series' => $limpias, 'rechazadas' => $rechazadas->values()];
    }
}
