<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'folio', 'venta_id', 'customer_id',
        'rfc', 'razon_social', 'uso_cfdi', 'forma_pago', 'metodo_pago',
        'subtotal', 'descuento_monto', 'envio', 'aplica_iva', 'iva_monto', 'total',
        'observaciones', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'aplica_iva' => 'boolean',
            'subtotal' => 'decimal:2',
            'descuento_monto' => 'decimal:2',
            'envio' => 'decimal:2',
            'iva_monto' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FacturaItem::class)->orderBy('orden');
    }

    public static function siguienteFolio(): string
    {
        $anio = now()->year;
        $ultimo = static::where('folio', 'like', "FAC-BORR-{$anio}-%")->count();

        return sprintf('FAC-BORR-%d-%04d', $anio, $ultimo + 1);
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'emitida' => 'Emitida',
            'cancelada' => 'Cancelada',
            default => 'Borrador',
        };
    }
}
