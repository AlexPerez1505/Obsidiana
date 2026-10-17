<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un gasto de una reclamación y quién lo absorbe. Lo que absorbe el
 * fabricante se puede ir marcando como recuperado.
 */
class GarantiaCosto extends Model
{
    protected $table = 'garantia_costos';

    public const CONCEPTOS = [
        'refaccion' => 'Refacciones',
        'mano_obra' => 'Mano de obra',
        'transporte' => 'Envío y logística',
        'prestamo' => 'Equipo de préstamo',
        'otro' => 'Otro',
    ];

    public const ABSORBE = [
        'medibuy' => 'MediBuy',
        'fabricante' => 'Recuperable del fabricante',
        'cliente' => 'Cliente',
        'provision' => 'Provisión pendiente',
    ];

    protected $fillable = [
        'reclamacion_id', 'garantia_id', 'concepto', 'descripcion', 'monto', 'absorbe',
        'recuperado', 'estado', 'aprobado_por', 'aprobado_en', 'created_by',
    ];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'recuperado' => 'decimal:2', 'aprobado_en' => 'datetime'];
    }

    public function reclamacion(): BelongsTo
    {
        return $this->belongsTo(GarantiaReclamacion::class, 'reclamacion_id');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function conceptoLabel(): string
    {
        return self::CONCEPTOS[$this->concepto] ?? ucfirst($this->concepto);
    }

    public function absorbeLabel(): string
    {
        return self::ABSORBE[$this->absorbe] ?? ucfirst($this->absorbe);
    }

    public function estadoLabel(): string
    {
        return ['aprobado' => 'Aprobado', 'pendiente' => 'Por aprobar', 'rechazado' => 'Rechazado'][$this->estado] ?? $this->estado;
    }

    public function estadoBadge(): string
    {
        return ['aprobado' => 'badge--ok', 'pendiente' => 'badge--warn', 'rechazado' => 'badge--danger'][$this->estado] ?? '';
    }
}
