<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Equipo de trabajo de la agenda: un grupo de personas con uno o más líderes. */
class AgendaEquipo extends Model
{
    protected $table = 'agenda_equipos';

    protected $fillable = ['nombre', 'descripcion', 'created_by'];

    public function miembros(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'agenda_equipo_usuario', 'equipo_id', 'user_id')->withPivot('es_lider');
    }
}
