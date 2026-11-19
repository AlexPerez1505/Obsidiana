<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un producto, equipo o paquete promocionado en una campaña. */
class CampanaItem extends Model
{
    protected $table = 'campana_items';

    public $timestamps = false;

    protected $fillable = ['campana_id', 'tipo_item', 'ref_id', 'orden'];

    public const TIPOS = ['producto', 'equipo', 'paquete'];

    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class);
    }
}
