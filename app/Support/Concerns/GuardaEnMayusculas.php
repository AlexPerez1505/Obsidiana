<?php

namespace App\Support\Concerns;

/**
 * Deja en MAYÚSCULAS ciertos campos justo antes de guardar.
 *
 * El problema que resuelve: unos capturan "Torre", otros "TORRE" y otros
 * "torre", así que el mismo dato aparecía distinto en los listados y el
 * catálogo terminaba viéndose incongruente. Normalizando al guardar, no
 * importa cómo lo escriban: siempre queda igual.
 *
 * Se aplica en el evento saving, así que cubre cualquier vía de alta o
 * edición (formulario, importador, alta al vuelo desde el cotizador), no
 * solo un controlador. Cada modelo declara qué campos normaliza:
 *
 *     protected array $mayusculas = ['name'];
 */
trait GuardaEnMayusculas
{
    public static function bootGuardaEnMayusculas(): void
    {
        static::saving(function ($model): void {
            foreach ($model->camposEnMayusculas() as $campo) {
                $valor = $model->getAttribute($campo);

                if (is_string($valor) && trim($valor) !== '') {
                    // mb_* para respetar acentos y la ñ (ENDOSCOPÍA, DISEÑO).
                    $model->setAttribute($campo, mb_strtoupper(trim($valor), 'UTF-8'));
                }
            }
        });
    }

    /** @return array<int, string> */
    protected function camposEnMayusculas(): array
    {
        return $this->mayusculas ?? [];
    }
}
