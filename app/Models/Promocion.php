<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promocion extends Model
{
    protected $table = 'promociones';

    protected $fillable = [
        'nombre',
        'mensaje',
        'imagen_path',
        'plantilla_whatsapp',
        'categoria_id',
        'producto_id',
        'paquete_id',
        'creado_por',
        'estado',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function paquete(): BelongsTo
    {
        return $this->belongsTo(Paquete::class, 'paquete_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function envios(): HasMany
    {
        return $this->hasMany(PromocionEnvio::class, 'promocion_id');
    }
}
