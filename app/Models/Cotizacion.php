<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'folio',
        'customer_id',
        'congreso_id',
        'seller_id',
        'lugar_propuesta',
        'nota_cliente',
        'modalidad',
        'aplica_iva',
        'subtotal',
        'descuento_tipo',
        'descuento_valor',
        'descuento_monto',
        'envio',
        'iva_monto',
        'valor_a_cuenta',
        'total',
        'total_contrato',
        'plan_nombre',
        'garantia_meses',
        'num_meses',
        'estado',
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

    public function items(): HasMany
    {
        return $this->hasMany(CotizacionItem::class)->orderBy('orden');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(CotizacionPago::class)->orderBy('orden');
    }

    public function fichas(): BelongsToMany
    {
        return $this->belongsToMany(FichaTecnica::class, 'cotizacion_ficha');
    }

    /**
     * Genera el siguiente folio COT-AAAA-####.
     */
    public static function siguienteFolio(): string
    {
        $anio = now()->year;
        $ultimo = static::where('folio', 'like', "COT-{$anio}-%")->count();

        return sprintf('COT-%d-%04d', $anio, $ultimo + 1);
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'enviada' => 'Enviada',
            'aceptada' => 'Aceptada',
            'rechazada' => 'Rechazada',
            'convertida' => 'Convertida a venta',
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
