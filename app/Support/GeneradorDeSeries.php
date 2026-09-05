<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\ProductoSerial;
use App\Models\Subtype;

/**
 * Arma números de serie propios cuando el equipo no trae uno de fábrica.
 *
 * Muchos accesorios y equipo usado llegan sin serial, y sin él no hay forma
 * de referirse a una pieza concreta en una cotización o en una entrega. La
 * serie se construye con lo que ya se sabe del equipo —tipo, subtipo, marca
 * y modelo— más un consecutivo, para que se lea y se entienda:
 *
 *     LAP-CAM-STR-1588-0001
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
     * subtipo da LAP-STR-1588, no LAP--STR-1588.
     */
    public static function prefijo(?int $tipoId, ?int $subtipoId, ?int $marcaId, ?int $modeloId): string
    {
        $partes = [
            $tipoId ? EquipmentType::find($tipoId)?->name : null,
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

    /**
     * Genera $cantidad series consecutivas que no choquen con las que ya
     * existen para ese prefijo.
     *
     * @return array<int, string>
     */
    public static function generar(string $prefijo, int $cantidad): array
    {
        $cantidad = max(1, min($cantidad, 5000));
        $desde = static::ultimoConsecutivo($prefijo);

        return collect(range(1, $cantidad))
            ->map(fn (int $i) => $prefijo.'-'.str_pad((string) ($desde + $i), self::DIGITOS, '0', STR_PAD_LEFT))
            ->all();
    }

    /**
     * El consecutivo más alto ya usado con ese prefijo.
     *
     * Se busca en todas las piezas y no solo en las de un producto: el
     * prefijo describe al equipo, así que dos filas del mismo modelo no
     * deben repetir número.
     */
    private static function ultimoConsecutivo(string $prefijo): int
    {
        $usadas = ProductoSerial::query()
            ->where('no_serie', 'like', $prefijo.'-%')
            ->pluck('no_serie');

        $largo = mb_strlen($prefijo) + 1;

        return (int) $usadas
            ->map(fn (string $serie) => (int) mb_substr($serie, $largo))
            ->max();
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
