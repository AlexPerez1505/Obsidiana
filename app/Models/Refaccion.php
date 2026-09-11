<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refaccion extends Model
{
    protected $table = 'refacciones';

    protected $fillable = ['subtype', 'name', 'description', 'stock', 'price', 'compatible_with', 'photo_path'];

    protected $casts = [
        'stock' => 'integer',
    ];
}
