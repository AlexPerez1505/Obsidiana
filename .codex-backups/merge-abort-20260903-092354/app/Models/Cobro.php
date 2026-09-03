<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un cobro es dinero que ya entró. No se edita: si algo salió mal se
 * cancela y se registra de nuevo, para que el recibo entregado al cliente
 * siempre corresponda con lo que dice el sistema.
 */
class Cobro extends Model
{
    public const METODOS = [
        'transferencia' => 'Transferencia',
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'deposito' => 'Depósito',
        'cheque' => 'Cheque',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'venta_id', 'venta_pago_id', 'folio', 'fecha', 'monto',
        'metodo', 'referencia', 'nota', 'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /** Parcialidad del plan a la que se aplicó, si aplica a alguna. */
    public function parcialidad(): BelongsTo
    {
        return $this->belongsTo(VentaPago::class, 'venta_pago_id');
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(CobroEvidencia::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function metodoLabel(): string
    {
        return self::METODOS[$this->metodo] ?? ucfirst($this->metodo);
    }

    /** Folio consecutivo por año: REC-2026-0001 */
    public static function siguienteFolio(): string
    {
        $anio = now()->year;

        $ultimo = static::where('folio', 'like', "REC-{$anio}-%")
            ->orderByDesc('folio')
            ->value('folio');

        $consecutivo = $ultimo ? ((int) substr($ultimo, -4)) + 1 : 1;

        return sprintf('REC-%d-%04d', $anio, $consecutivo);
    }
}
