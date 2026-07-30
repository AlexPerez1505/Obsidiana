<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'apellido',
        'telefono',
        'correo',
        'rfc',
        'comentarios',
        'seller_id',
        'customer_category_id',
        'congress_id',
        'receives_promotion',
    ];

    protected function casts(): array
    {
        return [
            'receives_promotion' => 'boolean',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomerCategory::class, 'customer_category_id');
    }

    public function congress(): BelongsTo
    {
        return $this->belongsTo(Congress::class, 'congress_id');
    }
}
