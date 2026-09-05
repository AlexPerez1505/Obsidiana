<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceSparePart extends Model
{
    protected $fillable = [
        'service_id',
        'nombre',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
