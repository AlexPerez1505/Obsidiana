<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nota u observación sobre una ficha técnica, dirigida a un usuario
 * ("la ficha trae el modelo equivocado"). Se da por atendida cuando ese
 * usuario (o quien puede editar fichas) la marca así.
 */
class FichaObservacion extends Model
{
    protected $table = 'ficha_observaciones';

    protected $fillable = ['ficha_tecnica_id', 'autor_id', 'para_id', 'texto', 'atendida_en', 'atendida_por'];

    protected function casts(): array
    {
        return ['atendida_en' => 'datetime'];
    }

    public function ficha(): BelongsTo
    {
        return $this->belongsTo(FichaTecnica::class, 'ficha_tecnica_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function para(): BelongsTo
    {
        return $this->belongsTo(User::class, 'para_id');
    }

    public function atendio(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atendida_por');
    }

    public function atendida(): bool
    {
        return $this->atendida_en !== null;
    }
}
