<?php

namespace App\Models;

use App\Support\Concerns\GuardaEnMayusculas;
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
    use GuardaEnMayusculas;

    protected $table = 'congreso_participantes';

    protected $fillable = [
        'congress_id',
        'nombre',
        'rol',
        'empresa',
    ];

    /** Nombre, rol y empresa se guardan en mayúsculas para verse parejo. */
    protected array $mayusculas = ['nombre', 'rol', 'empresa'];

    public function congress(): BelongsTo
    {
        return $this->belongsTo(Congress::class);
    }
}
