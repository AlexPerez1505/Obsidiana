<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rastro de lo que se le cambió a una venta después de creada.
 */
class VentaBitacora extends Model
{
    protected $table = 'venta_bitacora';

    protected $fillable = ['venta_id', 'user_id', 'tipo', 'descripcion', 'datos'];

    protected function casts(): array
    {
        return ['datos' => 'array'];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Atajo para dejar constancia sin repetir el armado en cada lugar. */
    public static function registrar(Venta $venta, string $tipo, string $descripcion, array $datos = []): void
    {
        static::create([
            'venta_id' => $venta->id,
            'user_id' => auth()->id(),
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'datos' => $datos ?: null,
        ]);
    }
}
