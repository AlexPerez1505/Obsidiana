<?php

namespace App\Services\Almacen;

use App\Models\Refaccion;
use App\Models\Subtype;
use Illuminate\Support\Collection;

/**
 * Refacciones sueltas: qué hay, cuántas y con qué equipo son compatibles.
 *
 * El subtipo se guarda como texto (no como liga al catálogo) porque así
 * nació la tabla; se elige de la lista de subtipos del catálogo.
 */
class Refacciones
{
    /**
     * Reglas del alta. La web manda el subtipo como `subtype_name`; la API
     * lo manda como `subtype`, igual que la columna.
     */
    public static function reglas(string $campoSubtipo = 'subtype_name'): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            $campoSubtipo => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'compatible_with' => ['nullable', 'string'],
        ];
    }

    /** @param  array{name: string, subtype: string, description?: ?string, stock?: ?int, compatible_with?: ?string}  $data */
    public function crear(array $data): Refaccion
    {
        return Refaccion::create([
            'subtype' => $data['subtype'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'stock' => $data['stock'] ?? 0,
            'compatible_with' => $data['compatible_with'] ?? null,
        ]);
    }

    /**
     * Los números de arriba del listado.
     *
     * @return array{totalRefacciones: int, totalStock: int, totalSubtypes: int, totalCompatible: int}
     */
    public function resumen(): array
    {
        return [
            'totalRefacciones' => Refaccion::count(),
            'totalStock' => (int) Refaccion::sum('stock'),
            'totalSubtypes' => Refaccion::distinct('subtype')->count('subtype'),
            'totalCompatible' => Refaccion::whereNotNull('compatible_with')->where('compatible_with', '!=', '')->count(),
        ];
    }

    /** Los subtipos del catálogo, para elegir el de la refacción. */
    public function subtipos(): Collection
    {
        return Subtype::orderBy('name')->pluck('name');
    }
}
