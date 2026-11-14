<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\ProductoSerial;
use App\Models\SerieReserva;
use App\Models\Subtype;
use Illuminate\Support\Facades\Cache;

/**
 * Arma números de serie propios cuando el equipo no trae uno de fábrica.
 *
 * Muchos accesorios y equipo usado llegan sin serial, y sin él no hay forma
 * de referirse a una pieza concreta en una cotización o en una entrega. La
 * serie se construye con lo que ya se sabe del equipo —subtipo, marca y
 * modelo— más un consecutivo, para que se lea y se entienda:
 *
 *     CAM-STR-1588-0001
 *
 * El tipo ya no va al frente: lo pidió así almacén, porque la abreviatura
 * del tipo (LAP-, TOR-…) alargaba la serie sin decir nada que no dijera ya
 * la ficha del equipo. Si no se eligió subtipo, marca ni modelo, la serie
 * arranca con EQ.
 *
 * Es distinta de la etiqueta interna (MB-000147), que es la del QR: esa
 * identifica la pieza dentro del sistema, y esta se escribe en el equipo y
 * sale en los documentos.
 */
class GeneradorDeSeries
{
    /** Cuántas letras se toman de cada parte del nombre. */
    private const LETRAS = 3;

    /** Ancho del consecutivo. */
    private const DIGITOS = 4;

    /**
     * Prefijo legible a partir del catálogo elegido.
     *
     * Las partes que no se eligieron simplemente no aparecen: un equipo sin
     * subtipo da STR-1588, no -STR-1588. El tipo se recibe porque así lo
     * mandan las pantallas y la API, pero ya no forma parte de la serie.
     */
    public static function prefijo(?int $tipoId, ?int $subtipoId, ?int $marcaId, ?int $modeloId): string
    {
        $partes = [
            $subtipoId ? Subtype::find($subtipoId)?->name : null,
            $marcaId ? Brand::find($marcaId)?->name : null,
            $modeloId ? EquipmentModel::find($modeloId)?->name : null,
        ];

        $abreviadas = collect($partes)
            ->filter()
            ->map(fn (string $texto) => static::abreviar($texto))
            ->filter();

        return $abreviadas->isEmpty() ? 'EQ' : $abreviadas->implode('-');
    }

    /** Cuánto dura apartado un número generado que todavía no se guarda. */
    private const HORAS_RESERVA = 12;

    /**
     * Genera $cantidad series consecutivas que no choquen con ninguna que
     * ya exista en el inventario (de cualquier producto) ni con las que se
     * generaron en otra captura que aún no se guarda: cada número entregado
     * queda reservado unas horas, así dos personas (o dos equipos con el
     * mismo subtipo, marca y modelo capturados a la vez) nunca reciben el
     * mismo.
     *
     * @return array<int, string>
     */
    public static function generar(string $prefijo, int $cantidad): array
    {
        $cantidad = max(1, min($cantidad, 5000));

        // Un solo generador a la vez por prefijo (entre procesos).
        return Cache::lock('generador-series:'.$prefijo, 15)->block(10, function () use ($prefijo, $cantidad) {
            SerieReserva::where('expira_en', '<', now())->delete();

            // Se arranca después del número más alto en uso, guardado o
            // reservado (la búsqueda no distingue mayúsculas), así que ninguna
            // de las nuevas puede chocar.
            $desde = max(static::ultimoConsecutivo($prefijo), static::ultimaReserva($prefijo));
            $series = array_map(
                fn (int $i) => $prefijo.'-'.str_pad((string) ($desde + $i), self::DIGITOS, '0', STR_PAD_LEFT),
                range(1, $cantidad)
            );

            $ahora = now();
            SerieReserva::insert(array_map(fn (string $serie) => [
                'serie' => $serie,
                'prefijo' => $prefijo,
                'user_id' => auth()->id(),
                'expira_en' => $ahora->copy()->addHours(self::HORAS_RESERVA),
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ], $series));

            return $series;
        });
    }

    /**
     * El consecutivo más alto ya usado con ese prefijo, en todas las piezas
     * (no solo las de un producto): el prefijo describe al equipo, así que
     * dos filas del mismo modelo no deben repetir número. Solo cuenta lo que
     * es exactamente PREFIJO-número (CAM-STR no se confunde con CAM-STR-1588).
     */
    private static function ultimoConsecutivo(string $prefijo): int
    {
        return static::mayorNumero(ProductoSerial::query()->where('no_serie', 'like', static::comoLike($prefijo).'-%')->pluck('no_serie'), $prefijo);
    }

    private static function ultimaReserva(string $prefijo): int
    {
        return static::mayorNumero(SerieReserva::where('prefijo', $prefijo)->pluck('serie'), $prefijo);
    }

    private static function mayorNumero($series, string $prefijo): int
    {
        $patron = '/^'.preg_quote(mb_strtoupper($prefijo), '/').'-(\d+)$/u';

        return (int) collect($series)
            ->map(fn ($serie) => preg_match($patron, mb_strtoupper(trim((string) $serie)), $m) ? (int) $m[1] : 0)
            ->max();
    }

    private static function comoLike(string $texto): string
    {
        return addcslashes($texto, '\\%_');
    }

    /** Deja las primeras letras útiles de una palabra, sin acentos ni signos. */
    private static function abreviar(string $texto): string
    {
        $limpio = preg_replace(
            '/[^A-Z0-9]/',
            '',
            mb_strtoupper(static::sinAcentos($texto))
        );

        if ($limpio === '') {
            return '';
        }

        // Un modelo suele ser corto y numérico (1588, CV-190): se respeta
        // completo hasta 6 caracteres en vez de recortarlo a tres letras.
        if (preg_match('/\d/', $limpio) && mb_strlen($limpio) <= 6) {
            return $limpio;
        }

        return mb_substr($limpio, 0, self::LETRAS);
    }

    private static function sinAcentos(string $texto): string
    {
        return strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }
}
