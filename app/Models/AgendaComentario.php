<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaComentario extends Model
{
    protected $table = 'agenda_comentarios';

    protected $fillable = ['item_type', 'item_id', 'user_id', 'texto'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
