<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'folio', 'customer_id', 'seller_id', 'cotizacion_id',
        'lugar_propuesta', 'nota_cliente', 'modalidad', 'aplica_iva',
        'subtotal', 'descuento_tipo', 'descuento_valor', 'descuento_monto',
        'envio', 'iva_monto', 'valor_a_cuenta', 'total', 'total_contrato',
        'plan_nombre', 'num_meses', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'aplica_iva' => 'boolean',
            'subtotal' => 'decimal:2',
            'descuento_valor' => 'decimal:2',
            'descuento_monto' => 'decimal:2',
            'envio' => 'decimal:2',
            'iva_monto' => 'decimal:2',
            'valor_a_cuenta' => 'decimal:2',
            'total' => 'decimal:2',
            'total_contrato' => 'decimal:2',
            'num_meses' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VentaItem::class)->orderBy('orden');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(VentaPago::class)->orderBy('orden');
    }

    public function fichas(): BelongsToMany
    {
        return $this->belongsToMany(FichaTecnica::class, 'venta_ficha');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public static function siguienteFolio(): string
    {
        $anio = now()->year;
        $ultimo = static::where('folio', 'like', "VEN-{$anio}-%")->count();

        return sprintf('VEN-%d-%04d', $anio, $ultimo + 1);
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'confirmada' => 'Confirmada',
            'facturada' => 'Facturada',
            'cancelada' => 'Cancelada',
            default => 'Borrador',
        };
    }
}
