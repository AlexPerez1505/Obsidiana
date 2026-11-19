<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgendaChecklist extends Model
{
    protected $table = 'agenda_checklist';

    protected $fillable = ['item_type', 'item_id', 'texto', 'hecho', 'hecho_por', 'orden'];

    protected function casts(): array
    {
        return ['hecho' => 'boolean'];
    }
}
