<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Préstamo temporal (el cliente lo regresa) o sustitución definitiva (la
 * serie nueva reemplaza a la anterior en la garantía).
 */
class GarantiaSustitucion extends Model
{
    protected $table = 'garantia_sustituciones';

    public const TIPOS = [
        'prestamo' => 'Préstamo temporal',
        'definitiva' => 'Sustitución definitiva',
    ];

    protected $fillable = [
        'reclamacion_id', 'garantia_id', 'tipo', 'equipo', 'no_serie_entregado', 'no_serie_anterior',
        'entregado_en', 'devolver_antes', 'devuelto_en', 'condiciones', 'created_by',
    ];

    protected function casts(): array
    {
        return ['entregado_en' => 'date', 'devolver_antes' => 'date', 'devuelto_en' => 'date'];
    }

    public function reclamacion(): BelongsTo
    {
        return $this->belongsTo(GarantiaReclamacion::class, 'reclamacion_id');
    }

    public function tipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function pendienteDeDevolver(): bool
    {
        return $this->tipo === 'prestamo' && ! $this->devuelto_en;
    }
}
