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
    /** Meses de garantía que se pueden elegir; 0 = el equipo se vende sin garantía. */
    public const GARANTIAS = [0, 6, 9, 12, 18, 36];

    use \App\Models\Concerns\ResumeProductos;
    use \App\Models\Concerns\VisiblePorAsesor;

    /** Quien no lo tiene, solo ve sus propias ventas (y su cobranza). */
    public const PERMISO_VER_TODAS = 'ventas.ver_todas';

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
    /** No todo el equipo lleva garantía (usado, refacciones, consumibles). */
    public function tieneGarantia(): bool
    {
        return (int) $this->garantia_meses > 0;
    }

    /** "12 meses" o "Sin garantía", para pantallas y documentos. */
    public function garantiaLabel(): string
    {
        return $this->tieneGarantia() ? $this->garantia_meses.' meses' : 'Sin garantía';
    }

    public function garantiaHasta(): ?\Illuminate\Support\Carbon
    {
        if (! $this->tieneGarantia()) {
            return null;
        }

        return $this->created_at?->copy()->addMonths((int) $this->garantia_meses);
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

    /** Lo que almacén tiene que preparar y firmar para que salga esta venta. */
    public function ordenSalida(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrdenSalida::class);
    }

    /** Las que cuentan: todo menos las canceladas. */
    public function scopeActivas($query)
    {
        return $query->where('estado', '!=', 'cancelada');
    }

    public function cancelada(): bool
    {
        return $this->estado === 'cancelada';
    }

    /**
     * ¿Alguna parcialidad ya tiene dinero encima? Entonces el plan de pagos
     * no se rehace al editar: se conserva y solo se ajustan las que siguen
     * sin cobrar. Un abono suelto (sin parcialidad) no bloquea: se reparte
     * después sobre el plan nuevo.
     */
    public function planBloqueado(): bool
    {
        return $this->pagos()->whereHas('cobros')->exists();
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
