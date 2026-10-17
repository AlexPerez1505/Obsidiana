<?php

namespace App\Services\Tienda;

use App\Models\Equipo;
use App\Models\FichaTecnica;
use App\Models\Paquete;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Lo que la tienda en línea muestra al público.
 *
 * Junta tres fuentes en una sola lista:
 *  - producto: lo que hay en almacén (solo con stock > 0) → "Disponible".
 *  - equipo:   el catálogo del cotizador (activos) → "Sobre pedido".
 *  - paquete:  los paquetes activos del cotizador.
 *
 * Nunca expone precios, series, proveedores ni notas internas: la página
 * es pública y el precio se da en la cotización.
 *
 * Cada artículo se identifica como "<fuente>-<id>" (p. ej. producto-24),
 * que es lo que la tienda manda de regreso al pedir cotización.
 */
class CatalogoTienda
{
    public const FUENTES = ['producto', 'equipo', 'paquete'];

    /** Todo el catálogo público, ya armado. */
    public function todo(): Collection
    {
        $productos = Producto::query()
            ->where('stock', '>', 0)
            ->with('fichaTecnica')
            ->get()
            ->map(fn (Producto $p) => $this->deProducto($p));

        $equipos = Equipo::query()
            ->where('activo', true)
            ->with('fichas')
            ->get()
            ->map(fn (Equipo $e) => $this->deEquipo($e));

        $paquetes = Paquete::query()
            ->where('activo', true)
            ->with(['productos', 'equipos'])
            ->get()
            ->map(fn (Paquete $p) => $this->dePaquete($p));

        return $productos->concat($equipos)->concat($paquetes)->values();
    }

    /**
     * Filtra y ordena: q (texto), categoria, marca, fuente, orden.
     */
    public function buscar(array $filtros): Collection
    {
        $q = Str::lower(Str::ascii(trim((string) ($filtros['q'] ?? ''))));

        return $this->todo()
            ->when($q !== '', fn ($c) => $c->filter(fn ($a) => str_contains($a['_buscar'], $q)))
            ->when($filtros['categoria'] ?? null, fn ($c, $v) => $c->where('categoria', $v))
            ->when($filtros['marca'] ?? null, fn ($c, $v) => $c->where('marca', $v))
            ->when($filtros['fuente'] ?? null, fn ($c, $v) => $c->where('fuente', $v))
            ->sortBy(match ($filtros['orden'] ?? 'recientes') {
                'nombre' => [['nombre', 'asc']],
                'marca' => [['marca', 'asc'], ['nombre', 'asc']],
                default => [['disponible', 'desc'], ['_fecha', 'desc']],
            })
            ->values();
    }

    /** Un artículo por su clave "fuente-id", o null si no es público. */
    public function buscarPorClave(string $clave): ?array
    {
        [$fuente, $id] = array_pad(explode('-', $clave, 2), 2, null);

        if (! in_array($fuente, self::FUENTES, true) || ! ctype_digit((string) $id)) {
            return null;
        }

        $articulo = match ($fuente) {
            'producto' => ($p = Producto::with('fichaTecnica')->where('stock', '>', 0)->find($id)) ? $this->deProducto($p) : null,
            'equipo' => ($e = Equipo::with('fichas')->where('activo', true)->find($id)) ? $this->deEquipo($e) : null,
            'paquete' => ($k = Paquete::with(['productos', 'equipos'])->where('activo', true)->find($id)) ? $this->dePaquete($k) : null,
        };

        return $articulo;
    }

    /** Categorías y marcas con cuántos artículos tiene cada una. */
    public function filtros(): array
    {
        $todo = $this->todo();

        $contar = fn (string $campo) => $todo->pluck($campo)->filter()
            ->countBy()->sortKeys()
            ->map(fn ($n, $nombre) => ['nombre' => $nombre, 'total' => $n])
            ->values();

        return [
            'categorias' => $contar('categoria'),
            'marcas' => $contar('marca'),
            'total' => $todo->count(),
        ];
    }

    /** Quita los campos internos que solo sirven para filtrar/ordenar. */
    public static function publico(array $articulo): array
    {
        return collect($articulo)->reject(fn ($v, $k) => str_starts_with($k, '_'))->all();
    }

    private function deProducto(Producto $p): array
    {
        $nombre = $p->nombreVisible() ?: 'Equipo';

        return $this->armar([
            'fuente' => 'producto',
            'id' => $p->id,
            'nombre' => Str::title(Str::lower($nombre)),
            'categoria' => $this->titulo($p->tipo_equipo),
            'subcategoria' => $this->titulo($p->subtipo),
            'marca' => $this->marca($p->marca),
            'modelo' => $p->modelo,
            'descripcion' => $p->descripcion,
            'imagen' => $this->url($p->imagen_path),
            'disponible' => true,
            'existencias' => (int) $p->stock,
            'ficha' => $this->ficha($p->fichaTecnica),
            'incluye' => [],
            '_fecha' => $p->updated_at?->timestamp ?? 0,
        ]);
    }

    private function deEquipo(Equipo $e): array
    {
        return $this->armar([
            'fuente' => 'equipo',
            'id' => $e->id,
            'nombre' => Str::title(Str::lower($e->subtipo ?: $e->tipo ?: 'Equipo')),
            'categoria' => $this->titulo($e->tipo),
            'subcategoria' => $this->titulo($e->subtipo),
            'marca' => $this->marca($e->marca),
            'modelo' => $e->modelo,
            'descripcion' => $e->descripcion,
            'imagen' => $this->url($e->imagen),
            'disponible' => false,
            'existencias' => null,
            'ficha' => $this->ficha($e->fichas->firstWhere('activo', true) ?? $e->fichas->first()),
            'incluye' => [],
            '_fecha' => $e->updated_at?->timestamp ?? 0,
        ]);
    }

    private function dePaquete(Paquete $k): array
    {
        $incluye = $k->productos->map(fn ($p) => [
            'nombre' => Str::title(Str::lower($p->nombreVisible())),
            'marca' => $this->marca($p->marca),
            'modelo' => $p->modelo,
            'cantidad' => (int) $p->pivot->cantidad,
        ])->concat($k->equipos->map(fn ($e) => [
            'nombre' => Str::title(Str::lower($e->subtipo ?: $e->tipo)),
            'marca' => $this->marca($e->marca),
            'modelo' => $e->modelo,
            'cantidad' => (int) $e->pivot->cantidad,
        ]))->values()->all();

        return $this->armar([
            'fuente' => 'paquete',
            'id' => $k->id,
            'nombre' => $k->nombre,
            'categoria' => 'Paquetes',
            'subcategoria' => null,
            'marca' => collect($incluye)->pluck('marca')->filter()->unique()->count() === 1
                ? $incluye[0]['marca'] : null,
            'modelo' => null,
            'descripcion' => $k->descripcion,
            'imagen' => $this->url($k->imagen),
            'disponible' => false,
            'existencias' => null,
            'ficha' => null,
            'incluye' => $incluye,
            '_fecha' => $k->updated_at?->timestamp ?? 0,
        ]);
    }

    private function armar(array $a): array
    {
        $a['clave'] = $a['fuente'].'-'.$a['id'];
        $a['_buscar'] = Str::lower(Str::ascii(implode(' ', array_filter([
            $a['nombre'], $a['categoria'], $a['subcategoria'], $a['marca'], $a['modelo'], $a['descripcion'],
            ...array_map(fn ($i) => $i['nombre'].' '.$i['modelo'], $a['incluye']),
        ]))));

        return $a;
    }

    private function titulo(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : Str::title(Str::lower($texto));
    }

    /** Las marcas se dejan en mayúsculas, como se escriben (OLYMPUS, STRYKER). */
    private function marca(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        return $texto === '' ? null : Str::upper($texto);
    }

    private function url(?string $ruta): ?string
    {
        return $ruta ? asset('storage/'.ltrim($ruta, '/')) : null;
    }

    private function ficha(?FichaTecnica $ficha): ?array
    {
        if (! $ficha || ! $ficha->archivo) {
            return null;
        }

        return ['titulo' => $ficha->titulo ?: 'Ficha técnica', 'url' => $this->url($ficha->archivo)];
    }
}
