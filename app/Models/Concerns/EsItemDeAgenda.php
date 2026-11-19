<?php

namespace App\Models\Concerns;

use App\Models\AgendaChecklist;
use App\Models\AgendaComentario;
use App\Models\AgendaHistorial;
use App\Models\AgendaRecordatorio;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Lo que comparten un seguimiento de Clientes y una actividad de la agenda:
 * historial, recordatorios, comentarios y checklist.
 */
trait EsItemDeAgenda
{
    public function historialAgenda(): MorphMany
    {
        return $this->morphMany(AgendaHistorial::class, 'item')->latest('id');
    }

    public function recordatoriosAgenda(): MorphMany
    {
        return $this->morphMany(AgendaRecordatorio::class, 'item');
    }

    public function comentariosAgenda(): MorphMany
    {
        return $this->morphMany(AgendaComentario::class, 'item')->oldest('id');
    }

    public function checklistAgenda(): MorphMany
    {
        return $this->morphMany(AgendaChecklist::class, 'item')->orderBy('orden')->orderBy('id');
    }

    /** "s-12" o "a-34": la llave con la que la pantalla identifica el item. */
    public function llaveAgenda(): string
    {
        return ($this->getMorphClass() === 'seguimiento' ? 's-' : 'a-').$this->getKey();
    }
}
