<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alguien que asiste a un congreso (ponente, distribuidor, asistente...).
 *
 * No es un cliente ni un usuario del sistema: es un registro libre, propio
 * de ese congreso, solo para saber quién estuvo y con qué rol.
 */
class CongresoParticipante extends Model
{
    protected $table = 'congreso_participantes';

    protected $fillable = [
        'congress_id',
        'nombre',
        'rol',
        'empresa',
    ];

    public function congress(): BelongsTo
    {
        return $this->belongsTo(Congress::class);
    }
}
