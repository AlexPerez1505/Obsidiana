<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Venta extends Model
{
    /** Plazos de garantía que se pueden ofrecer, en meses. */
    public const GARANTIAS = [6, 9, 12, 18, 36];

    protected $table = 'ventas';

    protected $fillable = [
        'folio', 'customer_id', 'congreso_id', 'seller_id', 'cotizacion_id',
        'lugar_propuesta', 'nota_cliente', 'modalidad', 'aplica_iva',
        'subtotal', 'descuento_tipo', 'descuento_valor', 'descuento_monto',
        'envio', 'iva_monto', 'valor_a_cuenta', 'total', 'total_contrato',
        'plan_nombre', 'num_meses', 'garantia_meses', 'estado',
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
            'garantia_meses' => 'integer',
        ];
    }

    /** El contrato de compraventa solo aplica cuando se paga a plazos. */
    public function requiereContrato(): bool
    {
        return $this->modalidad === 'financiamiento';
    }

    /** Hasta cuándo cubre la garantía, contada desde la venta. */
    public function garantiaHasta(): ?\Illuminate\Support\Carbon
    {
        return $this->created_at?->copy()->addMonths($this->garantia_meses ?: 6);
    }

    public function garantiaVigente(): bool
    {
        return $this->garantiaHasta()?->isFuture() ?? false;
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

    /** Dinero que de verdad entró. */
    public function cobros(): HasMany
    {
        return $this->hasMany(Cobro::class)->orderBy('fecha')->orderBy('id');
    }

    public function bitacora(): HasMany
    {
        return $this->hasMany(VentaBitacora::class)->latest();
    }

    // ===================== Saldos =====================

    public function totalCobrado(): float
    {
        return (float) $this->cobros->sum('monto');
    }

    /** Lo que falta por cobrar. Nunca negativo. */
    public function saldo(): float
    {
        return max(0, round($this->montoExigible() - $this->totalCobrado(), 2));
    }

    /**
     * Cuánto se le va a cobrar al cliente en total.
     *
     * Con "valor a cuenta" (un equipo que entrega a cambio) lo exigible es
     * el total del contrato, no el total de la venta.
     */
    public function montoExigible(): float
    {
        return (float) ($this->valor_a_cuenta > 0 ? $this->total_contrato : $this->total);
    }

    public function estadoPago(): string
    {
        $cobrado = $this->totalCobrado();

        return match (true) {
            $cobrado <= 0 => 'pendiente',
            $this->saldo() <= 0.009 => 'pagado',
            default => 'parcial',
        };
    }

    public function estadoPagoLabel(): string
    {
        return match ($this->estadoPago()) {
            'pagado' => 'Pagado',
            'parcial' => 'Pago parcial',
            default => 'Sin pagos',
        };
    }

    /** Porcentaje cobrado, para la barra de avance. */
    public function avance(): int
    {
        $exigible = $this->montoExigible();

        return $exigible > 0 ? (int) min(100, round(($this->totalCobrado() / $exigible) * 100)) : 0;
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
    /** Congreso donde se levanto. Nulo cuando no vino de un congreso. */
    public function congreso(): BelongsTo
    {
        return $this->belongsTo(Congress::class, 'congreso_id');
    }

    /**
     * El nombre del congreso se guarda tambien como texto: el PDF de un
     * documento ya emitido debe conservar como se llamaba ese dia.
     */
    protected static function booted(): void
    {
        // Token del enlace publico: se genera una sola vez, al crear.
        static::creating(function ($doc) {
            $doc->public_token = $doc->public_token ?: (string) Str::uuid();
        });

        static::saving(function ($doc) {
            if ($doc->isDirty('congreso_id')) {
                $doc->lugar_propuesta = $doc->congreso_id
                    ? Congress::find($doc->congreso_id)?->nombre
                    : null;
            }
        });
    }
}
