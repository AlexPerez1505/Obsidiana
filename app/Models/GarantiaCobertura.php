<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Condiciones de cobertura reutilizables. Al crear una garantía se copian
 * sus textos: si mañana se cambia la cobertura, las pólizas ya emitidas
 * conservan lo que se le prometió al cliente ese día.
 */
class GarantiaCobertura extends Model
{
    protected $table = 'garantia_coberturas';

    protected $fillable = ['nombre', 'tipo', 'meses', 'aplica_a', 'cubre', 'exclusiones', 'condiciones', 'activo'];

    protected function casts(): array
    {
        return ['meses' => 'integer', 'activo' => 'boolean'];
    }

    public function garantias(): HasMany
    {
        return $this->hasMany(Garantia::class, 'cobertura_id');
    }

    public function tipoLabel(): string
    {
        return Garantia::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function aplicaLabel(): string
    {
        return match ($this->aplica_a) {
            'nuevo' => 'Equipo nuevo',
            'usado' => 'Seminuevo o reacondicionado',
            default => 'Cualquier equipo',
        };
    }

    /** La cobertura que corresponde por defecto a la condición del equipo. */
    public static function paraCondicion(string $condicion): ?self
    {
        $clave = $condicion === 'nuevo' ? 'nuevo' : 'usado';

        return static::where('activo', true)->where('aplica_a', $clave)->orderBy('id')->first()
            ?? static::where('activo', true)->whereNull('aplica_a')->orderBy('id')->first();
    }
}
