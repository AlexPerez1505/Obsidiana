<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CartaGarantia extends Model
{
    use SoftDeletes;

    protected $table = 'cartas_garantia';

    protected $fillable = [
        'id_tipo_equipo',
        'id_subtipo',
        'nombre',
        'archivo_carta',
    ];

    public function tipoEquipo(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_tipo_equipo');
    }

    public function subtipo(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_subtipo');
    }
}
