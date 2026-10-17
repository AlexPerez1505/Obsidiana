<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de garantías: quién hizo qué y cuándo. Solo se agregan
 * renglones; no hay pantalla ni ruta que los edite o borre.
 */
class GarantiaEvento extends Model
{
    protected $table = 'garantia_eventos';

    public const UPDATED_AT = null;

    protected $fillable = ['garantia_id', 'reclamacion_id', 'user_id', 'tipo', 'descripcion', 'datos'];

    protected function casts(): array
    {
        return ['datos' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public static function anotar(string $tipo, string $descripcion, array $datos = [], ?Garantia $garantia = null, ?GarantiaReclamacion $reclamacion = null): self
    {
        return static::create([
            'garantia_id' => $garantia?->id,
            'reclamacion_id' => $reclamacion?->id,
            'user_id' => auth()->id(),
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'datos' => $datos ?: null,
        ]);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function autor(): string
    {
        return $this->usuario?->name ?? ($this->tipo === 'portal' ? 'Cliente (portal)' : 'Sistema');
    }
}
