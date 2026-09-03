<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CobroEvidencia extends Model
{
    protected $table = 'cobro_evidencias';

    protected $fillable = ['cobro_id', 'archivo', 'nombre'];

    public function cobro(): BelongsTo
    {
        return $this->belongsTo(Cobro::class);
    }
}
