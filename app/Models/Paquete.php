<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Paquete extends Model
{
    protected $table = 'paquetes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'imagen',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'paquete_equipo')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'paquete_producto')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    /**
     * Precio efectivo: el fijado, o la suma de sus productos/equipos por cantidad.
     */
    public function precioCalculado(): float
    {
        if ((float) $this->precio > 0) {
            return (float) $this->precio;
        }

        $totalProductos = (float) $this->productos->sum(fn ($p) => (float) $p->precio * (int) $p->pivot->cantidad);

        return $totalProductos > 0
            ? $totalProductos
            : (float) $this->equipos->sum(fn ($e) => (float) $e->precio * (int) $e->pivot->cantidad);
    }

    /**
     * Lo que trae el paquete, ya multiplicado por cuántos paquetes son.
     * Lo usan Productos, Paquetes, el cotizador y los documentos.
     *
     * @return array<int, array{cantidad: int, nombre: string, modelo: ?string, marca: ?string, tipo: ?string}>
     */
    public function contenido(int $paquetes = 1): array
    {
        $productos = $this->productos->map(fn ($p) => [
            'cantidad' => (int) $p->pivot->cantidad * max(1, $paquetes),
            'nombre' => $p->nombreVisible() ?: 'Producto',
            'modelo' => $p->modelo,
            'marca' => $p->marca,
            'tipo' => $p->tipo_equipo,
        ]);

        $equipos = $this->equipos->map(fn ($e) => [
            'cantidad' => (int) $e->pivot->cantidad * max(1, $paquetes),
            'nombre' => trim(($e->subtipo ?: $e->tipo) ?? '') ?: 'Equipo',
            'modelo' => $e->modelo,
            'marca' => $e->marca,
            'tipo' => $e->tipo,
        ]);

        return $productos->concat($equipos)
            ->sortBy(fn ($i) => mb_strtolower($i['nombre'].' '.$i['modelo']))
            ->values()->all();
    }

    /** El contenido en una línea: "1× Cámara 1488 · 2× Monitor 4K". */
    public function contenidoTexto(int $paquetes = 1): string
    {
        return collect($this->contenido($paquetes))
            ->map(fn ($i) => $i['cantidad'].'× '.trim($i['nombre'].' '.($i['marca'] ?? '').' '.($i['modelo'] ?? '')))
            ->implode(' · ');
    }

    /**
     * Para agrupar: el tipo de equipo que más aparece en el paquete
     * (p. ej. "LAPAROSCOPÍA"). Sin contenido queda en "Sin tipo".
     */
    public function tipoPrincipal(): string
    {
        $tipo = collect($this->contenido())
            ->groupBy(fn ($i) => mb_strtoupper(trim((string) $i['tipo'])) ?: 'SIN TIPO')
            ->map(fn ($g) => $g->sum('cantidad'))
            ->sortDesc()
            ->keys()
            ->first();

        return $tipo ?: 'SIN TIPO';
    }
}
