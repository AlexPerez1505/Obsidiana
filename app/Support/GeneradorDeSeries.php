<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\ProductoSerial;
use App\Models\Subtype;
use Illuminate\Support\Facades\DB;

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
 * arranca con EQ. El número final es un consecutivo global del inventario.
 *
 * Es distinta de la etiqueta interna (MB-000147), que es la del QR: esa
 * identifica la pieza dentro del sistema, y esta se escribe en el equipo y
 * sale en los documentos.
 */
class GeneradorDeSeries
{
    /** Registro único que reserva el consecutivo para todo el inventario. */
    private const CLAVE_SECUENCIA_GLOBAL = '__global__';

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

    /**
     * Reserva y genera $cantidad series consecutivas para todo el inventario.
     *
     * La reserva vive en la base de datos y se bloquea en un registro global.
     * Así dos usuarios que generen series al mismo tiempo no reciben el mismo
     * lote, aunque ninguno haya guardado todavía la entrada.
     *
     * @return array<int, string>
     */
    public static function generar(string $prefijo, int $cantidad): array
    {
        $cantidad = max(1, min($cantidad, 5000));

        return DB::transaction(function () use ($prefijo, $cantidad) {
            DB::table('serial_sequences')->insertOrIgnore([
                'prefijo' => self::CLAVE_SECUENCIA_GLOBAL,
                'siguiente' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $secuencia = DB::table('serial_sequences')
                ->where('prefijo', self::CLAVE_SECUENCIA_GLOBAL)
                ->lockForUpdate()
                ->firstOrFail();

            // También considera series antiguas o capturadas manualmente que
            // todavía no hayan pasado por esta tabla de secuencias.
            $desde = max((int) $secuencia->siguiente - 1, static::ultimoConsecutivoGlobal());
            $siguiente = $desde + $cantidad + 1;

            DB::table('serial_sequences')
                ->where('id', $secuencia->id)
                ->update(['siguiente' => $siguiente, 'updated_at' => now()]);

            return collect(range(1, $cantidad))
                ->map(fn (int $i) => $prefijo.'-'.str_pad((string) ($desde + $i), self::DIGITOS, '0', STR_PAD_LEFT))
                ->all();
        });
    }

    /** El consecutivo numérico más alto ya usado en cualquier serie. */
    private static function ultimoConsecutivoGlobal(): int
    {
        $usadas = ProductoSerial::query()
            ->whereNotNull('no_serie')
            ->where('no_serie', '!=', '')
            ->pluck('no_serie');

        return (int) $usadas
            ->map(function (string $serie) {
                return preg_match('/(\d+)$/', trim($serie), $coincidencia)
                    ? (int) $coincidencia[1]
                    : null;
            })
            ->filter()
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
