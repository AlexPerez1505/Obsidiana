<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'producto_id',
        'subtotal',
        'descuentos',
        'iva',
        'aplica_iva',
        'lugar',
        'costo_envio',
        'total',
        'plan_pago_id',
        'estado',
        'paquete_id',
        'regalo',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuentos' => 'decimal:2',
        'iva' => 'decimal:2',
        'aplica_iva' => 'boolean',
        'costo_envio' => 'decimal:2',
        'total' => 'decimal:2',
        'regalo' => 'boolean',
    ];

    /**
     * true si la cotización ya se convirtió en remisión (venta definitiva con seguimiento de pagos).
     */
    public function esRemision(): bool
    {
        return $this->estado === 'remision';
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'cliente_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }

    public function planPagos(): HasMany
    {
        return $this->hasMany(PlanPago::class, 'cotizacion_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CotizacionItem::class, 'cotizacion_id');
    }
}
