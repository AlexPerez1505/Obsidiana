<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    public const TYPE_CATEGORY = 'category';
    public const TYPE_SUBTYPE = 'subtype';
    public const TYPE_BRAND = 'brand';
    public const TYPE_MODEL = 'model';
    public const TYPE_UNIT = 'unit';

    protected $fillable = [
        'type',
        'value',
    ];
}
