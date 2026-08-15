<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refaccion extends Model
{
    protected $table = 'refacciones';

    protected $fillable = ['subtype', 'name', 'description', 'stock', 'compatible_with', 'price', 'photo'];

    protected $casts = [
        'stock' => 'integer',
        'price' => 'decimal:2',
    ];
}
