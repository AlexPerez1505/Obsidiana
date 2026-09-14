<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una partida de la orden de salida y lo que ya se le hizo. */
class OrdenSalidaItem extends Model
{
    protected $table = 'orden_salida_items';

    protected $fillable = [
        'orden_salida_id', 'venta_item_id', 'nombre', 'descripcion', 'cantidad', 'no_series',
        'requiere_emplayado', 'preparado', 'preparado_en', 'emplayado', 'emplayado_en',
        'observaciones', 'orden',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'requiere_emplayado' => 'boolean',
            'preparado' => 'boolean',
            'emplayado' => 'boolean',
            'preparado_en' => 'datetime',
            'emplayado_en' => 'datetime',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenSalida::class, 'orden_salida_id');
    }

    public function ventaItem(): BelongsTo
    {
        return $this->belongsTo(VentaItem::class);
    }

    /** Preparado, y emplayado si es de los que se emplayan. */
    public function completo(): bool
    {
        return $this->preparado && (! $this->requiere_emplayado || $this->emplayado);
    }
}
