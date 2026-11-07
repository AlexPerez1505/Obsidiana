<?php

namespace App\Support;

/**
 * El equipo que el cliente entrega a cuenta en una cotización o venta:
 * una lista de renglones con tipo, subtipo, marca, modelo y serie.
 *
 * Aquí viven las reglas de validación, la limpieza de lo que llega del
 * formulario y cómo se escribe cada renglón, para que cotización, venta,
 * PDF y contrato digan exactamente lo mismo.
 */
class EquiposACuenta
{
    public const CAMPOS = ['tipo', 'subtipo', 'marca', 'modelo', 'no_serie'];

    public const ETIQUETAS = [
        'tipo' => 'Tipo',
        'subtipo' => 'Subtipo',
        'marca' => 'Marca',
        'modelo' => 'Modelo',
        'no_serie' => 'Núm. de serie',
    ];

    /** Reglas para FormRequest / validate(). */
    public static function reglas(): array
    {
        $r = ['equipos_a_cuenta' => ['nullable', 'array', 'max:20']];
        foreach (self::CAMPOS as $c) {
            $r["equipos_a_cuenta.*.{$c}"] = ['nullable', 'string', 'max:150'];
        }

        return $r;
    }

    /**
     * Lo que llegó, sin renglones vacíos y con texto recortado en mayúsculas
     * (el catálogo de equipo se guarda así).
     *
     * @return array<int, array<string, string>>
     */
    public static function limpiar(mixed $renglones): array
    {
        return collect(is_array($renglones) ? $renglones : [])
            ->map(fn ($r) => collect(self::CAMPOS)
                ->mapWithKeys(fn ($c) => [$c => mb_strtoupper(trim((string) (is_array($r) ? ($r[$c] ?? '') : '')))])
                ->all())
            ->filter(fn ($r) => implode('', $r) !== '')
            ->values()
            ->all();
    }

    /** "LAPAROSCOPIA · TORRE · STORZ IMAGE1 · Serie 123" */
    public static function texto(array $r): string
    {
        $equipo = collect([$r['tipo'] ?? '', $r['subtipo'] ?? ''])->filter()->implode(' · ');
        $marcaModelo = trim(($r['marca'] ?? '').' '.($r['modelo'] ?? ''));

        return collect([$equipo, $marcaModelo, ! empty($r['no_serie']) ? 'Serie '.$r['no_serie'] : ''])
            ->filter()->implode(' · ');
    }

    /** Todos los renglones en una frase, para el contrato. */
    public static function frase(?array $renglones): string
    {
        return collect($renglones ?? [])->map(fn ($r) => self::texto($r))->filter()->implode('; ');
    }

    /** Valores del catálogo de productos para sugerir al escribir. */
    public static function sugerencias(): array
    {
        $col = fn (string $c) => \App\Models\Producto::query()->whereNotNull($c)->where($c, '!=', '')
            ->distinct()->orderBy($c)->limit(300)->pluck($c)->all();

        return [
            'tipo' => $col('tipo_equipo'),
            'subtipo' => $col('subtipo'),
            'marca' => $col('marca'),
            'modelo' => $col('modelo'),
        ];
    }
}
