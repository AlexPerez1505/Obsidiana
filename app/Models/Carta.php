<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carta extends Model
{
    protected $table = 'cartas';

    protected $fillable = [
        'equipment_type_name',
        'subtype_name',
        'refaccion_name',
        'description',
        'file_path',
        'file_name',
    ];
}
