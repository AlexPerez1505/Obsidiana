<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bitácora inmutable del módulo: quién creó, editó, envió, pausó o canceló qué. */
class MktEvento extends Model
{
    protected $table = 'mkt_eventos';

    public const UPDATED_AT = null;

    protected $fillable = ['campana_id', 'cupon_id', 'user_id', 'accion', 'detalle', 'datos'];

    protected function casts(): array
    {
        return ['datos' => 'array', 'created_at' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class, 'campana_id');
    }

    public static function registrar(string $accion, string $detalle, ?Campana $campana = null, ?Cupon $cupon = null, array $datos = []): self
    {
        return self::create([
            'campana_id' => $campana?->id,
            'cupon_id' => $cupon?->id,
            'user_id' => auth()->id(),
            'accion' => $accion,
            'detalle' => mb_substr($detalle, 0, 500),
            'datos' => $datos ?: null,
        ]);
    }
}
