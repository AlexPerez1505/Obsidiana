<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'gmail',
        'direccion',
        'comentarios',
        'congreso_id',
        'categoria_id',
        'recibe_promocion',
        'activo',
        'asesor_id',
    ];

    protected $casts = [
        'recibe_promocion' => 'boolean',
        'activo' => 'boolean',
    ];

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function congress(): BelongsTo
    {
        return $this->belongsTo(Congress::class, 'congreso_id');
    }

    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class, 'cliente_id');
    }

    public function planPagos(): HasMany
    {
        return $this->hasMany(PlanPago::class, 'cliente_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'cliente_id');
    }
}
