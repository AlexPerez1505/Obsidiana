<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cuánto de un cobro se aplicó a un pago del calendario. */
class CobroAplicacion extends Model
{
    protected $table = 'cobro_aplicaciones';

    protected $fillable = ['cobro_id', 'venta_pago_id', 'monto'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2'];
    }

    public function cobro(): BelongsTo
    {
        return $this->belongsTo(Cobro::class);
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(VentaPago::class, 'venta_pago_id');
    }
}
