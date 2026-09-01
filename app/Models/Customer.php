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
        'rfc',
        'gmail',
        'direccion',
        'comentarios',
        'congreso_id',
        'como_conocio',
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

    /**
     * Cómo se conoció al cliente, para mostrar en un solo dato: el nombre
     * del congreso si se levantó en uno, o lo que se haya escrito a mano
     * si no. Si no hay ninguno de los dos, no se sabe y se deja vacío.
     */
    public function comoConocio(): ?string
    {
        return $this->congress?->nombre ?: $this->como_conocio ?: null;
    }

    // Ojo: en cotizaciones la llave es customer_id, no cliente_id como en el resto.
    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class, 'customer_id');
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
