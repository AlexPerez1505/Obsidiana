<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FichaTecnica extends Model
{
    protected $table = 'fichas_tecnicas';

    protected $fillable = [
        'equipo_id',
        'producto_id',
        'paquete_id',
        'titulo',
        'archivo',
        'contenido',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class);
    }

    /** Nombre legible de lo que tenga relacionado (producto o paquete). */
    public function nombreRelacionado(): ?string
    {
        if ($this->producto) {
            return trim($this->producto->marca.' '.$this->producto->modelo) ?: null;
        }

        if ($this->paquete) {
            return $this->paquete->nombre;
        }

        return null;
    }
}
